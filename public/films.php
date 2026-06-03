<?php
// ── films.php - Pregled, filtriranje i upravljanje filmovima ─
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo       = getDB();
$korisnik  = trenutniKorisnik();
$poruka    = '';
$tip_poruke = '';

// ── AJAX: Dohvat filmova za filtriranje (JSON) ────────────────
if (isset($_GET['api']) && $_GET['api'] === 'filmovi') {
    header('Content-Type: application/json');

    $zanr      = $_GET['zanr']      ?? '';
    $godinaOd  = (int)($_GET['god_od']   ?? 1888);
    $godinaDo  = (int)($_GET['god_do']   ?? 2030);
    $minOcjena = (float)($_GET['ocjena'] ?? 0);
    $zemlja    = $_GET['zemlja']    ?? '';
    $sort      = $_GET['sort']      ?? 'naslov';

    // Bijela lista za sortiranje
    $sortMap = [
        'naslov'  => 'naslov ASC',
        'godina'  => 'godina DESC',
        'ocjena'  => 'ocjena DESC',
        'trajanje'=> 'trajanje ASC',
    ];
    $orderBy = $sortMap[$sort] ?? 'naslov ASC';

    $sql    = "SELECT * FROM filmovi WHERE 1=1";
    $params = [];

    if ($zanr) {
        $sql .= " AND zanr LIKE ?";
        $params[] = "%$zanr%";
    }
    if ($zemlja) {
        $sql .= " AND zemlja LIKE ?";
        $params[] = "%$zemlja%";
    }
    $sql .= " AND godina BETWEEN ? AND ?";
    $params[] = $godinaOd;
    $params[] = $godinaDo;

    $sql .= " AND ocjena >= ?";
    $params[] = $minOcjena;

    $sql .= " ORDER BY $orderBy LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

// ── AJAX: Dodaj u košaricu ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija'])) {
    header('Content-Type: application/json');

    if (!jePrijavljen()) {
        echo json_encode(['ok' => false, 'poruka' => 'Morate biti prijavljeni.']);
        exit;
    }

    $akcija  = $_POST['akcija'];
    $filmId  = (int)($_POST['film_id'] ?? 0);
    $korId   = $_SESSION['korisnik_id'];

    if ($akcija === 'dodaj') {
        // Provjeri ocjenu filma
        $stmtF = $pdo->prepare("SELECT ocjena, naslov FROM filmovi WHERE id = ?");
        $stmtF->execute([$filmId]);
        $film = $stmtF->fetch();

        if (!$film) {
            echo json_encode(['ok' => false, 'poruka' => 'Film ne postoji.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO zeljeni_filmovi (korisnik_id, film_id) VALUES (?, ?)"
            );
            $stmt->execute([$korId, $filmId]);

            $upozorenje = '';
            if ((float)$film['ocjena'] < 5.0) {
                $upozorenje = "⚠️ Ovaj film ima nisku ocjenu ({$film['ocjena']}) – jeste li sigurni da ga želite dodati?";
            }

            echo json_encode([
                'ok'        => true,
                'poruka'    => "Film \"{$film['naslov']}\" dodan u vašu videoteku!",
                'upozorenje'=> $upozorenje,
                'niска_ocjena' => (float)$film['ocjena'] < 5.0,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'poruka' => 'Greška pri dodavanju.']);
        }
        exit;
    }

    if ($akcija === 'ukloni') {
        $stmt = $pdo->prepare(
            "DELETE FROM zeljeni_filmovi WHERE korisnik_id = ? AND film_id = ?"
        );
        $stmt->execute([$korId, $filmId]);
        echo json_encode(['ok' => true, 'poruka' => 'Film uklonjen iz videoteke.']);
        exit;
    }
}

// ── ADMIN: Brisanje filma ─────────────────────────────────────
if (isset($_GET['brisi']) && jeAdmin()) {
    $id = (int)$_GET['brisi'];
    $pdo->prepare("DELETE FROM filmovi WHERE id = ?")->execute([$id]);
    header('Location: films.php?poruka=obrisan');
    exit;
}

// ── Dohvat žanrova i zemalja za filtere ──────────────────────
$zanrovi = $pdo->query("SELECT DISTINCT zanr FROM filmovi ORDER BY zanr")->fetchAll(PDO::FETCH_COLUMN);
$zemlje  = $pdo->query("SELECT DISTINCT zemlja FROM filmovi ORDER BY zemlja")->fetchAll(PDO::FETCH_COLUMN);

// ── Dohvat videoteke prijavljenog korisnika ──────────────────
$mojaVideoteka = [];
if (jePrijavljen()) {
    $stmt = $pdo->prepare(
        "SELECT film_id FROM zeljeni_filmovi WHERE korisnik_id = ?"
    );
    $stmt->execute([$_SESSION['korisnik_id']]);
    $mojaVideoteka = array_column($stmt->fetchAll(), 'film_id');
}

// ── Dohvat košarice (moja videoteka + detalji) ───────────────
$kosaricaDetalji = [];
if (jePrijavljen() && !empty($mojaVideoteka)) {
    $in = implode(',', array_fill(0, count($mojaVideoteka), '?'));
    $stmt = $pdo->prepare("SELECT * FROM filmovi WHERE id IN ($in)");
    $stmt->execute($mojaVideoteka);
    $kosaricaDetalji = $stmt->fetchAll();
}

$urlPoruka = $_GET['poruka'] ?? '';
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Videoteka – LV4</title>
    <link rel="stylesheet" href="style/style.css">
    <style>
        /* ── LV4 specifični stilovi ── */
        .lv4-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 3px solid orangered;
        }
        .lv4-header-bar h2 { margin: 0; font-size: 1.4rem; }
        .korisnik-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .korisnik-info a {
            color: orangered;
            text-decoration: none;
            font-weight: bold;
        }
        .badge-admin {
            background: orangered;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Filteri */
        .filteri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            background: #f9f9f9;
            padding: 18px;
            border-radius: 10px;
            border: 1px solid #eee;
            margin-bottom: 22px;
        }
        .filter-item { display: flex; flex-direction: column; gap: 5px; }
        .filter-item label { font-size: 0.83rem; font-weight: bold; color: #555; }
        .filter-item select,
        .filter-item input[type="number"] {
            padding: 8px 10px;
            border: 1.5px solid #ddd;
            border-radius: 7px;
            font-size: 0.9rem;
        }
        .filter-item input[type="range"] { accent-color: orangered; }
        .filteri-btns { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .btn-filter {
            padding: 10px 20px;
            background: linear-gradient(to right, orange, orangered);
            color: white; border: none; border-radius: 7px;
            font-weight: bold; cursor: pointer; font-size: 0.9rem;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-filter:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-reset {
            padding: 10px 16px;
            background: #eee; color: #333;
            border: none; border-radius: 7px;
            cursor: pointer; font-size: 0.9rem;
        }

        /* Tablica filmova */
        #filmovi-tablica tbody tr:hover { background: #fff8f5; }
        .btn-dodaj-lv4 {
            padding: 5px 12px;
            background: #222; color: white;
            border: none; border-radius: 5px;
            cursor: pointer; font-size: 0.82rem;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-dodaj-lv4:hover { background: orangered; }
        .btn-dodaj-lv4.vec-dodan {
            background: #aaa; cursor: default;
        }
        .btn-admin-brisi {
            padding: 4px 10px; background: #c0392b; color: white;
            border: none; border-radius: 5px; cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-admin-uredi {
            padding: 4px 10px; background: #2980b9; color: white;
            border: none; border-radius: 5px; cursor: pointer;
            font-size: 0.8rem; text-decoration: none; display: inline-block;
        }

        /* Upozorenje niska ocjena */
        .alert-niska-ocjena {
            display: none;
            background: #fff3cd;
            border: 2px solid orangered;
            border-radius: 8px;
            padding: 14px 18px;
            color: #856404;
            font-weight: bold;
            margin-bottom: 16px;
        }

        /* Košarica */
        #kosarica-lv4 {
            position: fixed; top: 0; right: -340px;
            width: 320px; height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0,0,0,0.15);
            z-index: 2000; display: flex; flex-direction: column;
            transition: right 0.3s ease;
            border-left: 4px solid orangered;
        }
        #kosarica-lv4.otvorena { right: 0; }
        .kosarica-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 16px;
            background: linear-gradient(to right, orange, orangered);
            color: white;
        }
        .kosarica-header h2 { margin: 0; font-size: 1.05rem; }
        .btn-zatvori-k {
            background: none; border: none; color: white;
            font-size: 1.2rem; cursor: pointer; padding: 4px 8px;
        }
        #kosarica-body { flex: 1; overflow-y: auto; padding: 10px; }
        .kosarica-item {
            display: flex; justify-content: space-between;
            align-items: flex-start; gap: 8px;
            padding: 10px; border-bottom: 1px solid #eee;
        }
        .k-naslov { font-weight: bold; font-size: 0.88rem; }
        .k-meta { font-size: 0.75rem; color: #888; }
        .btn-ukloni-k {
            border: 1px solid #ddd; background: none; color: #999;
            border-radius: 4px; padding: 2px 8px; cursor: pointer;
            font-size: 0.82rem; flex-shrink: 0;
        }
        .btn-ukloni-k:hover { background: #fff0f0; color: orangered; border-color: orangered; }
        .kosarica-empty { color: #bbb; text-align: center; padding: 40px; font-style: italic; }

        /* Admin forma */
        .admin-forma {
            background: white; border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 24px; margin-bottom: 30px;
        }
        .admin-forma h3 { margin-top: 0; color: #222; }
        .forma-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }
        .forma-polje { display: flex; flex-direction: column; gap: 5px; }
        .forma-polje label { font-size: 0.85rem; font-weight: bold; color: #444; }
        .forma-polje input,
        .forma-polje select,
        .forma-polje textarea {
            padding: 9px 12px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: 0.9rem;
        }
        .forma-polje input:focus,
        .forma-polje select:focus { border-color: orangered; outline: none; }
        .forma-greska { color: #c0392b; font-size: 0.82rem; margin-top: 3px; }

        .toggle-btn {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px;
            background: linear-gradient(to right, orange, orangered);
            color: white; border: none; border-radius: 50%;
            font-size: 1.5rem; cursor: pointer; z-index: 1000;
            box-shadow: 0 4px 15px rgba(255,80,0,0.4);
            transition: transform 0.2s;
        }
        .toggle-btn:hover { transform: scale(1.1); }
        #k-count {
            position: absolute; top: -4px; right: -4px;
            background: #222; color: white;
            font-size: 0.68rem; font-weight: bold;
            width: 20px; height: 20px; border-radius: 50%;
            display: none; align-items: center; justify-content: center;
        }
        .sort-select {
            padding: 7px 10px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: 0.88rem;
        }
        .prijavljen-label { font-size: 0.9rem; color: #555; }
        .link-prijava { color: orangered; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<header>
    <h1>Dobrodošli na moju web stranicu o filmovima</h1>
</header>

<nav class="menu" aria-label="Primarna navigacija">
    ☰ Menu
    <div class="dropdown">
        <h3>Primarna navigacija</h3>
        <ul>
            <li><a href="index.html">Početna</a></li>
            <li><a href="grafikon.html">Grafikoni</a></li>
            <li><a href="slike.html">Galerija</a></li>
            <li><a href="films.php">🎬 Videoteka (LV4)</a></li>
            <li><a href="gallery.php">📸 Ocjeni slike (LV4)</a></li>
            <?php if (jePrijavljen()): ?>
                <li><a href="logout.php">Odjava (<?= htmlspecialchars($_SESSION['korisnik_ime']) ?>)</a></li>
            <?php else: ?>
                <li><a href="auth.php">Prijava</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<main>
    <div class="lv3-sekcija">

        <!-- Header sekcije -->
        <div class="lv4-header-bar">
            <h2>🎬 Videoteka filmova – LV4</h2>
            <div class="korisnik-info">
                <?php if (jePrijavljen()): ?>
                    <span>👤 <?= htmlspecialchars($_SESSION['korisnik_ime']) ?></span>
                    <?php if (jeAdmin()): ?>
                        <span class="badge-admin">ADMIN</span>
                    <?php endif; ?>
                    <a href="logout.php">Odjava</a>
                <?php else: ?>
                    <span class="prijavljen-label">
                        <a href="auth.php" class="link-prijava">Prijavi se</a> za dodavanje filmova
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upozorenje niska ocjena -->
        <div class="alert-niska-ocjena" id="alert-niska">
            ⚠️ <span id="alert-niska-tekst"></span>
        </div>

        <?php if ($urlPoruka === 'obrisan'): ?>
            <div class="alert-greska" style="background:#fff0f0;border-left:4px solid orangered;padding:12px 16px;border-radius:6px;color:#c0392b;margin-bottom:16px;">
                ✅ Film uspješno obrisan.
            </div>
        <?php endif; ?>

        <!-- ── ADMIN: Forma za dodavanje filma ── -->
        <?php if (jeAdmin()): ?>
        <div class="admin-forma">
            <h3>➕ Dodaj novi film <small style="font-weight:normal;color:#888;">(Admin)</small></h3>
            <form id="forma-dodaj-film" method="POST" action="film_save.php">
                <div class="forma-grid">
                    <div class="forma-polje">
                        <label>Naslov *</label>
                        <input type="text" name="naslov" required maxlength="255" placeholder="npr. Inception">
                        <span class="forma-greska" id="err-naslov"></span>
                    </div>
                    <div class="forma-polje">
                        <label>Žanr *</label>
                        <input type="text" name="zanr" required maxlength="100" placeholder="npr. Akcija, Drama">
                    </div>
                    <div class="forma-polje">
                        <label>Godina * (1888–2030)</label>
                        <input type="number" name="godina" required min="1888" max="2030" placeholder="npr. 2010">
                        <span class="forma-greska" id="err-godina"></span>
                    </div>
                    <div class="forma-polje">
                        <label>Trajanje (min) * (30–300)</label>
                        <input type="number" name="trajanje" required min="30" max="300" placeholder="npr. 148">
                        <span class="forma-greska" id="err-trajanje"></span>
                    </div>
                    <div class="forma-polje">
                        <label>Ocjena * (0.0–10.0)</label>
                        <input type="number" name="ocjena" required min="0" max="10" step="0.1" placeholder="npr. 8.8">
                    </div>
                    <div class="forma-polje">
                        <label>Redatelj *</label>
                        <input type="text" name="redatelj" required maxlength="150" placeholder="npr. Christopher Nolan">
                    </div>
                    <div class="forma-polje">
                        <label>Zemlja *</label>
                        <input type="text" name="zemlja" required maxlength="100" placeholder="npr. USA/UK">
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <button type="submit" class="btn-filter">💾 Spremi film</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ── FILTERI ── -->
        <div class="filteri-grid">
            <div class="filter-item">
                <label>Žanr</label>
                <select id="f-zanr">
                    <option value="">-- Svi žanrovi --</option>
                    <?php foreach ($zanrovi as $z): ?>
                        <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Zemlja</label>
                <select id="f-zemlja">
                    <option value="">-- Sve zemlje --</option>
                    <?php foreach ($zemlje as $z): ?>
                        <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Godina od</label>
                <input type="number" id="f-god-od" min="1888" max="2030" placeholder="1888">
            </div>
            <div class="filter-item">
                <label>Godina do: <span id="f-god-do-val">2030</span></label>
                <input type="range" id="f-god-do" min="1888" max="2030" value="2030">
            </div>
            <div class="filter-item">
                <label>Min. ocjena: <span id="f-ocjena-val">0.0</span></label>
                <input type="range" id="f-ocjena" min="0" max="10" step="0.1" value="0">
            </div>
            <div class="filter-item">
                <label>Sortiranje</label>
                <select id="f-sort" class="sort-select">
                    <option value="naslov">Naslov A–Z</option>
                    <option value="godina">Godina (novo→staro)</option>
                    <option value="ocjena">Ocjena (visoka→niska)</option>
                    <option value="trajanje">Trajanje (kratko→dugo)</option>
                </select>
            </div>
        </div>

        <div class="filteri-btns" style="margin-bottom:20px;">
            <button class="btn-filter" onclick="ucitajFilmove()">🔍 Filtriraj</button>
            <button class="btn-reset" onclick="resetFiltre()">↺ Reset</button>
        </div>

        <!-- Tablica filmova -->
        <div class="tablica-wrapper">
            <table id="filmovi-tablica">
                <thead>
                    <tr>
                        <th>Naslov</th>
                        <th>Žanr</th>
                        <th>Godina</th>
                        <th>Trajanje</th>
                        <th>Zemlja</th>
                        <th>Ocjena</th>
                        <th>Dodaj</th>
                        <?php if (jeAdmin()): ?><th>Admin</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="<?= jeAdmin() ? 8 : 7 ?>" class="prazno">Učitavanje...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2025. Web Programiranje. Sva prava pridržana.</p>
</footer>

<!-- ── Gumb košarica ── -->
<button class="toggle-btn" onclick="toggleKosarica()" aria-label="Moja videoteka">
    🎬
    <span id="k-count"><?= count($mojaVideoteka) ?></span>
</button>

<!-- ── Košarica aside ── -->
<aside id="kosarica-lv4" aria-label="Moja videoteka">
    <div class="kosarica-header">
        <h2>🎬 Moja videoteka</h2>
        <button class="btn-zatvori-k" onclick="toggleKosarica()">✕</button>
    </div>
    <div id="kosarica-body">
        <?php if (!jePrijavljen()): ?>
            <p class="kosarica-empty">
                <a href="auth.php" style="color:orangered;">Prijavi se</a> za upravljanje videotekom.
            </p>
        <?php elseif (empty($kosaricaDetalji)): ?>
            <p class="kosarica-empty">Videoteka je prazna.</p>
        <?php else: ?>
            <?php foreach ($kosaricaDetalji as $f): ?>
            <div class="kosarica-item" id="ki-<?= $f['id'] ?>">
                <div>
                    <div class="k-naslov"><?= htmlspecialchars($f['naslov']) ?></div>
                    <div class="k-meta"><?= $f['godina'] ?> · <?= htmlspecialchars($f['zanr']) ?></div>
                </div>
                <button class="btn-ukloni-k" onclick="ukloniFilm(<?= $f['id'] ?>)">✕</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</aside>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
// ── Globalno stanje ──────────────────────────────────────────
const jeAdmin  = <?= jeAdmin() ? 'true' : 'false' ?>;
const prijavljen = <?= jePrijavljen() ? 'true' : 'false' ?>;
let mojaVideoteka = <?= json_encode($mojaVideoteka) ?>;

// ── Filteri: live prikaz vrijednosti ─────────────────────────
document.getElementById('f-god-do').addEventListener('input', function () {
    document.getElementById('f-god-do-val').textContent = this.value;
});
document.getElementById('f-ocjena').addEventListener('input', function () {
    document.getElementById('f-ocjena-val').textContent = parseFloat(this.value).toFixed(1);
});

// ── Dohvat filmova iz PHP API-ja ─────────────────────────────
async function ucitajFilmove() {
    const params = new URLSearchParams({
        api:    'filmovi',
        zanr:   document.getElementById('f-zanr').value,
        zemlja: document.getElementById('f-zemlja').value,
        god_od: document.getElementById('f-god-od').value || 1888,
        god_do: document.getElementById('f-god-do').value,
        ocjena: document.getElementById('f-ocjena').value,
        sort:   document.getElementById('f-sort').value,
    });

    const tbody = document.querySelector('#filmovi-tablica tbody');
    tbody.innerHTML = '<tr><td colspan="<?= jeAdmin() ? 8 : 7 ?>" class="prazno">Učitavanje...</td></tr>';

    try {
        const res  = await fetch('films.php?' + params);
        const data = await res.json();
        prikaziFilmove(data);
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="<?= jeAdmin() ? 8 : 7 ?>" class="prazno">Greška pri učitavanju.</td></tr>';
    }
}

function prikaziFilmove(filmovi) {
    const tbody = document.querySelector('#filmovi-tablica tbody');
    const cols  = jeAdmin ? 8 : 7;

    if (!filmovi.length) {
        tbody.innerHTML = `<tr><td colspan="${cols}" class="prazno">Nema filmova za odabrane filtere.</td></tr>`;
        return;
    }

    tbody.innerHTML = filmovi.map(f => {
        const vecDodan = mojaVideoteka.includes(parseInt(f.id));
        const ocjBadge = `<span class="ocjena-badge">${parseFloat(f.ocjena).toFixed(1)}</span>`;
        const btnTekst = vecDodan ? '✓ U videoteci' : '+ Dodaj';
        const btnClass = vecDodan ? 'btn-dodaj-lv4 vec-dodan' : 'btn-dodaj-lv4';
        const btnAttr  = vecDodan ? 'disabled' : `onclick="dodajFilm(${f.id}, '${f.naslov.replace(/'/g,"\\'")}', '${f.zanr}', ${f.godina})"`;

        let adminCol = '';
        if (jeAdmin) {
            adminCol = `<td>
                <a class="btn-admin-uredi" href="film_edit.php?id=${f.id}">✏️</a>
                <button class="btn-admin-brisi" onclick="brisiFilm(${f.id})">🗑️</button>
            </td>`;
        }

        return `<tr>
            <td>${escHtml(f.naslov)}</td>
            <td>${escHtml(f.zanr)}</td>
            <td>${f.godina}</td>
            <td>${f.trajanje} min</td>
            <td>${escHtml(f.zemlja)}</td>
            <td>${ocjBadge}</td>
            <td><button class="${btnClass}" ${prijavljen ? btnAttr : 'onclick="alert(\'Morate se prijaviti!\')"'}>${btnTekst}</button></td>
            ${adminCol}
        </tr>`;
    }).join('');
}

// ── Dodaj film u videoteku ───────────────────────────────────
async function dodajFilm(id, naslov, zanr, godina) {
    const fd = new FormData();
    fd.append('akcija', 'dodaj');
    fd.append('film_id', id);

    const res  = await fetch('films.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
        mojaVideoteka.push(id);
        prikaziToast(data.poruka, 'ok');

        // Upozorenje niska ocjena
        if (data.niska_ocjena || data.upozorenje) {
            const alert = document.getElementById('alert-niska');
            document.getElementById('alert-niska-tekst').textContent = data.upozorenje;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 6000);
        }

        // Dodaj u košaricu prikaz
        dodajUPrikazKosarice(id, naslov, zanr, godina);
        // Osvježi gumb u tablici
        ucitajFilmove();
        osvjeziCount();
    } else {
        prikaziToast(data.poruka || 'Greška!', 'warn');
    }
}

// ── Ukloni film iz videoteke ─────────────────────────────────
async function ukloniFilm(id) {
    const fd = new FormData();
    fd.append('akcija', 'ukloni');
    fd.append('film_id', id);

    const res  = await fetch('films.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
        mojaVideoteka = mojaVideoteka.filter(x => x !== id);
        document.getElementById('ki-' + id)?.remove();
        if (!mojaVideoteka.length) {
            document.getElementById('kosarica-body').innerHTML =
                '<p class="kosarica-empty">Videoteka je prazna.</p>';
        }
        prikaziToast('Film uklonjen iz videoteke.', 'warn');
        ucitajFilmove();
        osvjeziCount();
    }
}

function dodajUPrikazKosarice(id, naslov, zanr, godina) {
    const body = document.getElementById('kosarica-body');
    // Ukloni "prazna" poruku
    const prazna = body.querySelector('.kosarica-empty');
    if (prazna) prazna.remove();

    const div = document.createElement('div');
    div.className = 'kosarica-item';
    div.id = 'ki-' + id;
    div.innerHTML = `
        <div>
            <div class="k-naslov">${escHtml(naslov)}</div>
            <div class="k-meta">${godina} · ${escHtml(zanr)}</div>
        </div>
        <button class="btn-ukloni-k" onclick="ukloniFilm(${id})">✕</button>
    `;
    body.appendChild(div);
}

function osvjeziCount() {
    const el = document.getElementById('k-count');
    el.textContent = mojaVideoteka.length;
    el.style.display = mojaVideoteka.length > 0 ? 'flex' : 'none';
}

function brisiFilm(id) {
    if (confirm('Obrisati ovaj film?')) {
        window.location.href = 'films.php?brisi=' + id;
    }
}

function toggleKosarica() {
    document.getElementById('kosarica-lv4').classList.toggle('otvorena');
}

function resetFiltre() {
    document.getElementById('f-zanr').value = '';
    document.getElementById('f-zemlja').value = '';
    document.getElementById('f-god-od').value = '';
    document.getElementById('f-god-do').value = 2030;
    document.getElementById('f-god-do-val').textContent = '2030';
    document.getElementById('f-ocjena').value = 0;
    document.getElementById('f-ocjena-val').textContent = '0.0';
    document.getElementById('f-sort').value = 'naslov';
    ucitajFilmove();
}

function prikaziToast(poruka, tip = 'ok') {
    const t = document.getElementById('toast');
    t.textContent = poruka;
    t.className = 'toast ' + tip + ' vidljiv';
    clearTimeout(t._tmr);
    t._tmr = setTimeout(() => t.classList.remove('vidljiv'), 3500);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Inicijalni dohvat ─────────────────────────────────────────
ucitajFilmove();
osvjeziCount();
</script>
</body>
</html>
