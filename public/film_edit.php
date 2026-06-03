<?php
// ── film_edit.php - Forma za uređivanje filma (admin) ────────
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
zahtijevajAdmina();

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: films.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM filmovi WHERE id = ?");
$stmt->execute([$id]);
$film = $stmt->fetch();

if (!$film) {
    header('Location: films.php');
    exit;
}

$greska = htmlspecialchars($_GET['greska'] ?? '');
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uredi film – LV4</title>
    <link rel="stylesheet" href="style/style.css">
    <style>
        .edit-wrapper { max-width: 700px; margin: 40px auto; background: white; border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .edit-header { background: linear-gradient(to right, orange, orangered); padding: 24px 28px; color: white; }
        .edit-header h2 { margin: 0; font-size: 1.3rem; }
        .edit-body { padding: 28px; }
        .forma-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .forma-polje { display: flex; flex-direction: column; gap: 5px; }
        .forma-polje label { font-size: 0.85rem; font-weight: bold; color: #444; }
        .forma-polje input, .forma-polje textarea {
            padding: 9px 12px; border: 1.5px solid #ddd; border-radius: 7px; font-size: 0.9rem; }
        .forma-polje input:focus { border-color: orangered; outline: none; }
        .span-2 { grid-column: 1 / -1; }
        .btns { display: flex; gap: 12px; margin-top: 20px; }
        .btn-spremi { padding: 11px 24px; background: linear-gradient(to right, orange, orangered);
            color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 0.95rem; }
        .btn-odustani { padding: 11px 20px; background: #eee; color: #333;
            border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .alert-greska { background: #fff0f0; border-left: 4px solid orangered;
            padding: 12px 16px; border-radius: 6px; color: #c0392b; margin-bottom: 16px; }
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
            <li><a href="films.php">🎬 Videoteka (LV4)</a></li>
            <li><a href="logout.php">Odjava</a></li>
        </ul>
    </div>
</nav>

<main>
<div class="edit-wrapper">
    <div class="edit-header">
        <h2>✏️ Uredi film: <?= htmlspecialchars($film['naslov']) ?></h2>
    </div>
    <div class="edit-body">
        <?php if ($greska): ?>
            <div class="alert-greska">⚠️ <?= $greska ?></div>
        <?php endif; ?>

        <form method="POST" action="film_save.php">
            <input type="hidden" name="film_id" value="<?= $film['id'] ?>">
            <div class="forma-grid">
                <div class="forma-polje span-2">
                    <label>Naslov *</label>
                    <input type="text" name="naslov" value="<?= htmlspecialchars($film['naslov']) ?>" required maxlength="255">
                </div>
                <div class="forma-polje">
                    <label>Žanr *</label>
                    <input type="text" name="zanr" value="<?= htmlspecialchars($film['zanr']) ?>" required maxlength="100">
                </div>
                <div class="forma-polje">
                    <label>Redatelj *</label>
                    <input type="text" name="redatelj" value="<?= htmlspecialchars($film['redatelj']) ?>" required maxlength="150">
                </div>
                <div class="forma-polje">
                    <label>Godina (1888–2030) *</label>
                    <input type="number" name="godina" value="<?= $film['godina'] ?>" required min="1888" max="2030">
                </div>
                <div class="forma-polje">
                    <label>Trajanje min (30–300) *</label>
                    <input type="number" name="trajanje" value="<?= $film['trajanje'] ?>" required min="30" max="300">
                </div>
                <div class="forma-polje">
                    <label>Ocjena (0.0–10.0) *</label>
                    <input type="number" name="ocjena" value="<?= $film['ocjena'] ?>" required min="0" max="10" step="0.1">
                </div>
                <div class="forma-polje">
                    <label>Zemlja *</label>
                    <input type="text" name="zemlja" value="<?= htmlspecialchars($film['zemlja']) ?>" required maxlength="100">
                </div>
                <div class="forma-polje span-2">
                    <label>Opis</label>
                    <textarea name="opis" rows="3" style="resize:vertical;"><?= htmlspecialchars($film['opis'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="btns">
                <button type="submit" class="btn-spremi">💾 Spremi izmjene</button>
                <a href="films.php" class="btn-odustani">Odustani</a>
            </div>
        </form>
    </div>
</div>
</main>

<footer><p>&copy; 2025. Web Programiranje. Sva prava pridržana.</p></footer>
</body>
</html>
