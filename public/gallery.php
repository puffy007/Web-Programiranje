<?php
// ── gallery.php - Ocjenjivanje fotografija (LV4 Zadatak 2) ───
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();

// ── AJAX: Spremi ocjenu ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija'])) {
    header('Content-Type: application/json');

    if (!jePrijavljen()) {
        echo json_encode(['ok' => false, 'poruka' => 'Morate biti prijavljeni za ocjenjivanje.']);
        exit;
    }

    $akcija  = $_POST['akcija'];
    $slikaId = (int)($_POST['slika_id'] ?? 0);
    $ocjena  = (int)($_POST['ocjena']   ?? 0);
    $korId   = $_SESSION['korisnik_id'];

    if ($akcija === 'ocijeni') {
        if ($ocjena < 1 || $ocjena > 5) {
            echo json_encode(['ok' => false, 'poruka' => 'Ocjena mora biti između 1 i 5.']);
            exit;
        }

        // INSERT or UPDATE (upsert)
        $stmt = $pdo->prepare(
            "INSERT INTO ocjene_slike (korisnik_id, slika_id, ocjena)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE ocjena = VALUES(ocjena), vrijeme = NOW()"
        );
        $stmt->execute([$korId, $slikaId, $ocjena]);

        // Dohvat nove prosječne ocjene
        $avg = $pdo->prepare(
            "SELECT ROUND(AVG(ocjena), 2) as avg, COUNT(*) as cnt FROM ocjene_slike WHERE slika_id = ?"
        );
        $avg->execute([$slikaId]);
        $avgData = $avg->fetch();

        echo json_encode([
            'ok'     => true,
            'avg'    => $avgData['avg'],
            'cnt'    => $avgData['cnt'],
            'poruka' => 'Ocjena spremljena!',
        ]);
        exit;
    }

    // ── Admin: Upload slike ───────────────────────────────────
    if ($akcija === 'upload' && jeAdmin()) {
        $naziv = trim($_POST['naziv'] ?? '');
        $opis  = trim($_POST['opis']  ?? '');

        if (!isset($_FILES['slika']) || $_FILES['slika']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'poruka' => 'Greška pri uploadu.']);
            exit;
        }

        $file     = $_FILES['slika'];
        $maxSize  = 5 * 1024 * 1024; // 5MB
        $dozvoljeni = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($file['size'] > $maxSize) {
            echo json_encode(['ok' => false, 'poruka' => 'Slika je prevelika (max 5MB).']);
            exit;
        }
        if (!in_array($mimeType, $dozvoljeni)) {
            echo json_encode(['ok' => false, 'poruka' => 'Dozvoljeni formati su JPEG i PNG.']);
            exit;
        }

        $ext      = $mimeType === 'image/png' ? 'png' : 'jpg';
        $filename = uniqid('slika_') . '.' . $ext;
        $uploadDir = 'images/gallery/';

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        $stmt = $pdo->prepare(
            "INSERT INTO slike (naziv, opis, putanja, izvor) VALUES (?, ?, ?, 'lokalno')"
        );
        $stmt->execute([$naziv ?: $filename, $opis, $uploadDir . $filename]);
        $noviId = $pdo->lastInsertId();

        echo json_encode(['ok' => true, 'poruka' => 'Slika uploadana!', 'id' => $noviId]);
        exit;
    }

    // ── Admin: Obriši sliku ───────────────────────────────────
    if ($akcija === 'obrisi' && jeAdmin()) {
        $slikaId = (int)($_POST['slika_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT putanja FROM slike WHERE id = ?");
        $stmt->execute([$slikaId]);
        $s = $stmt->fetch();

        if ($s && file_exists($s['putanja'])) {
            unlink($s['putanja']);
        }
        $pdo->prepare("DELETE FROM slike WHERE id = ?")->execute([$slikaId]);
        echo json_encode(['ok' => true, 'poruka' => 'Slika obrisana.']);
        exit;
    }
}

// ── Automatski uvezi slike iz foldera images/gallery/ ─────────
$galleryDir = __DIR__ . '/images/gallery/';
if (is_dir($galleryDir)) {
    $dozvoljeni = ['jpg', 'jpeg', 'png'];
    $files = scandir($galleryDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $dozvoljeni)) continue;

        $putanja = 'images/gallery/' . $file;
        $apsolutna = __DIR__ . '/images/gallery/' . $file;
        $naziv   = pathinfo($file, PATHINFO_FILENAME);
        // Čitljiviji naziv - zamijeni _ i - s razmakom
        $naziv   = ucwords(str_replace(['_', '-'], ' ', $naziv));

        // Dodaj u bazu samo ako već ne postoji
        $check = $pdo->prepare("SELECT id FROM slike WHERE putanja = ?");
        $check->execute([$putanja]);
        if (!$check->fetch()) {
            $pdo->prepare(
                "INSERT INTO slike (naziv, putanja, izvor) VALUES (?, ?, 'lokalno')"
            )->execute([$naziv, $putanja]);
        }
    }
}
// ── Dohvat svih slika s prosječnim ocjenama ───────────────────
$slike = $pdo->query(
    "SELECT s.*,
            ROUND(AVG(o.ocjena), 2) AS avg_ocjena,
            COUNT(o.id) AS br_ocjena
     FROM slike s
     LEFT JOIN ocjene_slike o ON o.slika_id = s.id
     GROUP BY s.id
     ORDER BY s.id DESC"
)->fetchAll();

// ── Dohvat mojih ocjena (ako je prijavljen) ───────────────────
$mojeOcjene = [];
if (jePrijavljen()) {
    $stmt = $pdo->prepare(
        "SELECT slika_id, ocjena FROM ocjene_slike WHERE korisnik_id = ?"
    );
    $stmt->execute([$_SESSION['korisnik_id']]);
    foreach ($stmt->fetchAll() as $r) {
        $mojeOcjene[$r['slika_id']] = (int)$r['ocjena'];
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📸 Ocjenjivanje fotografija – LV4</title>
    <link rel="stylesheet" href="style/style.css">
    <style>
        /* ── Galerija s ocjenjivanjem ── */
        .gallery-lv4 {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .slika-kartica {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.09);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .slika-kartica:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.14);
        }

        .slika-img-wrap {
            position: relative;
            aspect-ratio: 4/3;
            overflow: hidden;
            cursor: zoom-in;
        }
        .slika-img-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }
        .slika-img-wrap:hover img { transform: scale(1.05); }

        .slika-body { padding: 14px 16px 16px; }
        .slika-naziv {
            font-weight: bold; font-size: 0.95rem;
            margin: 0 0 4px; color: #222;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .slika-opis { font-size: 0.82rem; color: #888; margin: 0 0 10px; }

        /* Prosječna ocjena */
        .avg-wrap {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 10px;
        }
        .avg-stars { color: #f5a623; font-size: 1rem; }
        .avg-broj { font-size: 0.88rem; color: #666; }

        /* Zvjezdice za ocjenjivanje */
        .zvjezdice {
            display: flex; gap: 4px;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .zvjezdice input { display: none; }
        .zvjezdice label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            transition: color 0.15s;
        }
        /* Hover i selected stilovi (RTL trick) */
        .zvjezdice label:hover,
        .zvjezdice label:hover ~ label { color: #f5a623; }
        .zvjezdice input:checked ~ label { color: #f5a623; }

        .ocjeni-info { font-size: 0.78rem; color: #aaa; margin-top: 6px; }

        /* Admin gumbi */
        .slika-admin {
            display: flex; gap: 8px; padding: 10px 16px;
            border-top: 1px solid #f0f0f0;
        }
        .btn-obrisi-s {
            padding: 4px 12px; background: #c0392b; color: white;
            border: none; border-radius: 5px; cursor: pointer;
            font-size: 0.82rem;
        }

        /* Upload forma */
        .upload-forma {
            background: white; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 22px; margin-bottom: 30px;
        }
        .upload-forma h3 { margin-top: 0; }
        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .upload-polje { display: flex; flex-direction: column; gap: 5px; }
        .upload-polje label { font-size: 0.85rem; font-weight: bold; color: #444; }
        .upload-polje input, .upload-polje textarea {
            padding: 9px 12px; border: 1.5px solid #ddd;
            border-radius: 7px; font-size: 0.9rem;
        }
        .upload-polje input:focus { border-color: orangered; outline: none; }
        .span-2 { grid-column: 1 / -1; }

        /* Lightbox */
        .lightbox-lv4 {
            display: none; position: fixed;
            inset: 0; background: rgba(0,0,0,0.92);
            z-index: 9999; justify-content: center; align-items: center;
        }
        .lightbox-lv4.aktivan { display: flex; }
        .lightbox-lv4 img {
            max-width: 90vw; max-height: 85vh;
            border-radius: 8px; box-shadow: 0 0 40px rgba(255,255,255,0.1);
        }
        .lightbox-zatvori {
            position: fixed; top: 20px; right: 30px;
            color: white; font-size: 2rem; cursor: pointer;
            background: none; border: none;
        }

        /* Sekcija header */
        .lv4-header-bar {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
            margin-bottom: 24px; padding-bottom: 16px;
            border-bottom: 3px solid orangered;
        }
        .lv4-header-bar h2 { margin: 0; }
        .korisnik-info { display: flex; align-items: center; gap: 12px; font-size: 0.9rem; }
        .korisnik-info a { color: orangered; text-decoration: none; font-weight: bold; }
        .badge-admin {
            background: orangered; color: white;
            padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold;
        }
        .prazno-slike { text-align: center; color: #bbb; padding: 60px; font-style: italic; }
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

        <div class="lv4-header-bar">
            <h2>📸 Ocjenjivanje fotografija – LV4</h2>
            <div class="korisnik-info">
                <?php if (jePrijavljen()): ?>
                    <span>👤 <?= htmlspecialchars($_SESSION['korisnik_ime']) ?></span>
                    <?php if (jeAdmin()): ?>
                        <span class="badge-admin">ADMIN</span>
                    <?php endif; ?>
                    <a href="logout.php">Odjava</a>
                <?php else: ?>
                    <a href="auth.php">Prijavi se</a> za ocjenjivanje
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Admin: Upload slike ── -->
        <?php if (jeAdmin()): ?>
        <div class="upload-forma">
            <h3>📤 Dodaj novu sliku <small style="font-weight:normal;color:#888;">(Admin – max 5MB, JPEG/PNG)</small></h3>
            <div class="upload-grid">
                <div class="upload-polje">
                    <label>Naziv slike</label>
                    <input type="text" id="u-naziv" placeholder="npr. Zalazak sunca">
                </div>
                <div class="upload-polje">
                    <label>Odaberi sliku (JPEG/PNG, max 5MB)</label>
                    <input type="file" id="u-slika" accept=".jpg,.jpeg,.png">
                </div>
                <div class="upload-polje span-2">
                    <label>Opis (opcionalno)</label>
                    <textarea id="u-opis" rows="2" placeholder="Kratki opis slike..."></textarea>
                </div>
            </div>
            <div style="margin-top:14px;">
                <button class="btn-filter" onclick="uploadSliku()">📤 Uploadaj sliku</button>
                <span id="upload-status" style="margin-left:12px;font-size:0.9rem;color:#2e7d32;"></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Galerija s ocjenama ── -->
        <?php if (empty($slike)): ?>
            <div class="prazno-slike">
                Nema slika. <?= jeAdmin() ? 'Dodaj slike putem forme iznad.' : 'Admin treba dodati slike.' ?>
            </div>
        <?php else: ?>
        <div class="gallery-lv4">
            <?php foreach ($slike as $s):
                $myOcjena = $mojeOcjene[$s['id']] ?? 0;
                $avgOcj   = $s['avg_ocjena'] ? number_format($s['avg_ocjena'], 1) : '–';
                $zvjAvg   = $s['avg_ocjena'] ? str_repeat('★', round($s['avg_ocjena'])) . str_repeat('☆', 5 - round($s['avg_ocjena'])) : '☆☆☆☆☆';
            ?>
            <div class="slika-kartica" id="sk-<?= $s['id'] ?>">
                <!-- Slika -->
                <div class="slika-img-wrap" onclick="otvoriLightbox('<?= htmlspecialchars($s['putanja']) ?>')">
                    <img src="<?= htmlspecialchars($s['putanja']) ?>"
                         alt="<?= htmlspecialchars($s['naziv']) ?>"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/400x300?text=Slika'">
                </div>

                <div class="slika-body">
                    <p class="slika-naziv"><?= htmlspecialchars($s['naziv']) ?></p>
                    <?php if ($s['opis']): ?>
                        <p class="slika-opis"><?= htmlspecialchars($s['opis']) ?></p>
                    <?php endif; ?>

                    <!-- Prosječna ocjena -->
                    <div class="avg-wrap">
                        <span class="avg-stars" id="avg-zv-<?= $s['id'] ?>"><?= $zvjAvg ?></span>
                        <span class="avg-broj" id="avg-br-<?= $s['id'] ?>">
                            <?= $avgOcj ?> / 5
                            (<?= $s['br_ocjena'] ?> <?= $s['br_ocjena'] === 1 ? 'ocjena' : 'ocjena' ?>)
                        </span>
                    </div>

                    <!-- Ocjenjivanje (samo prijavljeni) -->
                    <?php if (jePrijavljen()): ?>
                    <div class="zvjezdice" data-slika="<?= $s['id'] ?>">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="z<?= $s['id'] ?>-<?= $i ?>"
                                   name="ocjena-<?= $s['id'] ?>"
                                   value="<?= $i ?>"
                                   <?= $myOcjena === $i ? 'checked' : '' ?>>
                            <label for="z<?= $s['id'] ?>-<?= $i ?>" title="<?= $i ?> zvjezdic<?= $i === 1 ? 'a' : 'e' ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <p class="ocjeni-info" id="oci-<?= $s['id'] ?>">
                        <?= $myOcjena ? "Tvoja ocjena: $myOcjena ★" : 'Ocijeni klikanjem na zvjezdicu' ?>
                    </p>
                    <?php else: ?>
                        <p class="ocjeni-info">
                            <a href="auth.php" style="color:orangered;">Prijavi se</a> za ocjenjivanje
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Admin akcije -->
                <?php if (jeAdmin()): ?>
                <div class="slika-admin">
                    <button class="btn-obrisi-s" onclick="obrisiSliku(<?= $s['id'] ?>)">🗑️ Obriši</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /lv3-sekcija -->
</main>

<footer>
    <p>&copy; 2025. Web Programiranje. Sva prava pridržana.</p>
</footer>

<!-- Lightbox -->
<div class="lightbox-lv4" id="lightbox-lv4" onclick="zatvoriLightbox()">
    <button class="lightbox-zatvori" onclick="zatvoriLightbox()">✕</button>
    <img id="lightbox-img" src="" alt="Uvećana slika">
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
// ── Ocjenjivanje ─────────────────────────────────────────────
document.querySelectorAll('.zvjezdice').forEach(group => {
    group.querySelectorAll('input').forEach(input => {
        input.addEventListener('change', async function () {
            const slikaId = group.dataset.slika;
            const ocjena  = this.value;

            const fd = new FormData();
            fd.append('akcija',   'ocijeni');
            fd.append('slika_id', slikaId);
            fd.append('ocjena',   ocjena);

            try {
                const res  = await fetch('gallery.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.ok) {
                    // Ažuriraj prikaz prosječne ocjene
                    const zvj = '★'.repeat(Math.round(data.avg)) + '☆'.repeat(5 - Math.round(data.avg));
                    document.getElementById('avg-zv-' + slikaId).textContent = zvj;
                    document.getElementById('avg-br-' + slikaId).textContent =
                        `${parseFloat(data.avg).toFixed(1)} / 5 (${data.cnt} ocjena)`;
                    document.getElementById('oci-' + slikaId).textContent =
                        `Tvoja ocjena: ${ocjena} ★`;
                    prikaziToast('Ocjena ' + ocjena + ' ★ spremljena!', 'ok');
                } else {
                    prikaziToast(data.poruka || 'Greška!', 'warn');
                }
            } catch (e) {
                prikaziToast('Mrežna greška.', 'warn');
            }
        });
    });
});

// ── Upload slike (admin) ──────────────────────────────────────
async function uploadSliku() {
    const naziv = document.getElementById('u-naziv').value;
    const opis  = document.getElementById('u-opis').value;
    const file  = document.getElementById('u-slika').files[0];

    if (!file) { prikaziToast('Odaberi sliku!', 'warn'); return; }

    const fd = new FormData();
    fd.append('akcija', 'upload');
    fd.append('naziv',  naziv);
    fd.append('opis',   opis);
    fd.append('slika',  file);

    const status = document.getElementById('upload-status');
    status.textContent = 'Uploading...';

    const res  = await fetch('gallery.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
        status.textContent = '✅ ' + data.poruka;
        setTimeout(() => location.reload(), 1000);
    } else {
        status.style.color = 'orangered';
        status.textContent = '⚠️ ' + data.poruka;
    }
}

// ── Obriši sliku (admin) ──────────────────────────────────────
async function obrisiSliku(id) {
    if (!confirm('Obrisati ovu sliku i sve ocjene?')) return;

    const fd = new FormData();
    fd.append('akcija',   'obrisi');
    fd.append('slika_id', id);

    const res  = await fetch('gallery.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
        document.getElementById('sk-' + id)?.remove();
        prikaziToast('Slika obrisana.', 'ok');
    } else {
        prikaziToast(data.poruka || 'Greška.', 'warn');
    }
}

// ── Lightbox ──────────────────────────────────────────────────
function otvoriLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-lv4').classList.add('aktivan');
}
function zatvoriLightbox() {
    document.getElementById('lightbox-lv4').classList.remove('aktivan');
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') zatvoriLightbox();
});

// ── Toast ─────────────────────────────────────────────────────
function prikaziToast(poruka, tip = 'ok') {
    const t = document.getElementById('toast');
    t.textContent = poruka;
    t.className = 'toast ' + tip + ' vidljiv';
    clearTimeout(t._tmr);
    t._tmr = setTimeout(() => t.classList.remove('vidljiv'), 3500);
}
</script>
</body>
</html>
