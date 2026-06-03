<?php
// ── auth.php - Prijava i registracija ────────────────────────
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Ako je već prijavljen, preusmjeri
if (jePrijavljen()) {
    header('Location: films.php');
    exit;
}

$greska  = '';
$uspjeh  = '';
$akcija  = $_GET['akcija'] ?? 'prijava'; // 'prijava' | 'registracija'

// ── OBRADA FORME ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcija = $_POST['akcija'] ?? 'prijava';

    if ($akcija === 'registracija') {
        // ── REGISTRACIJA ─────────────────────────────────────
        $korime = trim($_POST['korisnicko_ime'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $loz    = $_POST['lozinka'] ?? '';
        $loz2   = $_POST['lozinka2'] ?? '';

        // Validacija
        if (strlen($korime) < 3 || strlen($korime) > 50) {
            $greska = 'Korisničko ime mora biti između 3 i 50 znakova.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $greska = 'Neispravna email adresa.';
        } elseif (strlen($loz) < 6) {
            $greska = 'Lozinka mora imati najmanje 6 znakova.';
        } elseif ($loz !== $loz2) {
            $greska = 'Lozinke se ne podudaraju.';
        } else {
            try {
                $pdo  = getDB();
                $hash = password_hash($loz, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO korisnici (korisnicko_ime, lozinka, email) VALUES (?, ?, ?)"
                );
                $stmt->execute([$korime, $hash, $email]);
                $uspjeh = 'Registracija uspješna! Možeš se prijaviti.';
                $akcija = 'prijava';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $greska = 'Korisničko ime ili email već postoji.';
                } else {
                    $greska = 'Greška pri registraciji. Pokušaj ponovo.';
                }
            }
        }

    } else {
        // ── PRIJAVA ──────────────────────────────────────────
        $korime = trim($_POST['korisnicko_ime'] ?? '');
        $loz    = $_POST['lozinka'] ?? '';

        if (empty($korime) || empty($loz)) {
            $greska = 'Molim unesite korisničko ime i lozinku.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                "SELECT id, korisnicko_ime, lozinka, uloga FROM korisnici WHERE korisnicko_ime = ?"
            );
            $stmt->execute([$korime]);
            $korisnik = $stmt->fetch();

            if ($korisnik && password_verify($loz, $korisnik['lozinka'])) {
                $_SESSION['korisnik_id']  = $korisnik['id'];
                $_SESSION['korisnik_ime'] = $korisnik['korisnicko_ime'];
                $_SESSION['uloga']        = $korisnik['uloga'];

                $redirect = $_GET['redirect'] ?? 'films.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $greska = 'Neispravno korisničko ime ili lozinka.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava – Web Programiranje</title>
    <link rel="stylesheet" href="style/style.css">
    <style>
        /* ── Auth specifični stilovi ── */
        .auth-wrapper {
            max-width: 440px;
            margin: 60px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(to right, orange, orangered);
            padding: 32px 32px 24px;
            color: white;
            text-align: center;
        }
        .auth-header h1 { margin: 0; font-size: 1.5rem; }
        .auth-header p  { margin: 8px 0 0; opacity: 0.9; font-size: 0.95rem; }

        .auth-tabs {
            display: flex;
            border-bottom: 2px solid #eee;
        }
        .auth-tab {
            flex: 1;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            color: #888;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .auth-tab.aktivan {
            color: orangered;
            border-bottom: 3px solid orangered;
            margin-bottom: -2px;
        }
        .auth-tab:hover { background: #fff8f5; }

        .auth-body { padding: 28px 32px 32px; }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 18px;
        }
        .form-group label {
            font-weight: bold;
            font-size: 0.88rem;
            color: #444;
        }
        .form-group input {
            padding: 10px 14px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
        }
        .form-group input:focus { border-color: orangered; }

        .btn-prijava {
            width: 100%;
            padding: 13px;
            background: linear-gradient(to right, orange, orangered);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-prijava:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert-greska {
            background: #fff0f0;
            border-left: 4px solid orangered;
            padding: 12px 16px;
            border-radius: 6px;
            color: #c0392b;
            font-size: 0.9rem;
            margin-bottom: 18px;
        }
        .alert-uspjeh {
            background: #f0fff4;
            border-left: 4px solid #2e7d32;
            padding: 12px 16px;
            border-radius: 6px;
            color: #2e7d32;
            font-size: 0.9rem;
            margin-bottom: 18px;
        }
        .natrag-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        .natrag-link a { color: orangered; text-decoration: none; }
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
        </ul>
    </div>
</nav>

<main>
    <div class="auth-wrapper">
        <div class="auth-header">
            <h1>🎬 Videoteka</h1>
            <p><?= $akcija === 'registracija' ? 'Kreiraj novi račun' : 'Prijavi se za pristup' ?></p>
        </div>

        <div class="auth-tabs">
            <a href="?akcija=prijava"
               class="auth-tab <?= $akcija === 'prijava' ? 'aktivan' : '' ?>">
                Prijava
            </a>
            <a href="?akcija=registracija"
               class="auth-tab <?= $akcija === 'registracija' ? 'aktivan' : '' ?>">
                Registracija
            </a>
        </div>

        <div class="auth-body">

            <?php if ($greska): ?>
                <div class="alert-greska">⚠️ <?= htmlspecialchars($greska) ?></div>
            <?php endif; ?>

            <?php if ($uspjeh): ?>
                <div class="alert-uspjeh">✅ <?= htmlspecialchars($uspjeh) ?></div>
            <?php endif; ?>

            <?php if ($akcija === 'registracija'): ?>
            <!-- ── FORMA: Registracija ── -->
            <form method="POST">
                <input type="hidden" name="akcija" value="registracija">
                <div class="form-group">
                    <label>Korisničko ime</label>
                    <input type="text" name="korisnicko_ime"
                           value="<?= htmlspecialchars($_POST['korisnicko_ime'] ?? '') ?>"
                           maxlength="50" required placeholder="npr. ivan123">
                </div>
                <div class="form-group">
                    <label>Email adresa</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required placeholder="npr. ivan@email.com">
                </div>
                <div class="form-group">
                    <label>Lozinka (min. 6 znakova)</label>
                    <input type="password" name="lozinka" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Potvrdi lozinku</label>
                    <input type="password" name="lozinka2" required minlength="6">
                </div>
                <button type="submit" class="btn-prijava">Registriraj se</button>
            </form>

            <?php else: ?>
            <!-- ── FORMA: Prijava ── -->
            <form method="POST">
                <input type="hidden" name="akcija" value="prijava">
                <div class="form-group">
                    <label>Korisničko ime</label>
                    <input type="text" name="korisnicko_ime"
                           value="<?= htmlspecialchars($_POST['korisnicko_ime'] ?? '') ?>"
                           required autofocus placeholder="Unesi korisničko ime">
                </div>
                <div class="form-group">
                    <label>Lozinka</label>
                    <input type="password" name="lozinka" required placeholder="••••••">
                </div>
                <button type="submit" class="btn-prijava">Prijavi se</button>
            </form>
            <?php endif; ?>

            <div class="natrag-link">
                <a href="index.html">← Natrag na početnu</a>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2025. Web Programiranje. Sva prava pridržana.</p>
</footer>

</body>
</html>