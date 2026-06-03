<?php
// ── film_save.php - Spremi ili uredi film (samo admin) ───────
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
zahtijevajAdmina();

$pdo    = getDB();
$greske = [];
$filmId = (int)($_POST['film_id'] ?? 0); // 0 = novi, >0 = uredi

// ── Dohvat i validacija polja ─────────────────────────────────
$naslov   = trim($_POST['naslov']   ?? '');
$zanr     = trim($_POST['zanr']     ?? '');
$godina   = (int)($_POST['godina']  ?? 0);
$trajanje = (int)($_POST['trajanje']?? 0);
$ocjena   = (float)($_POST['ocjena']?? 0);
$redatelj = trim($_POST['redatelj'] ?? '');
$zemlja   = trim($_POST['zemlja']   ?? '');
$opis     = trim($_POST['opis']     ?? '');

// ── Validacija (serverska) ────────────────────────────────────
if (strlen($naslov) < 1 || strlen($naslov) > 255) {
    $greske[] = 'Naslov je obvezan (max 255 znakova).';
}
if (strlen($zanr) < 1 || strlen($zanr) > 100) {
    $greske[] = 'Žanr je obvezan (max 100 znakova).';
}
if ($godina < 1888 || $godina > 2030) {
    $greske[] = 'Godina mora biti između 1888 i 2030.';
}
if ($trajanje < 30 || $trajanje > 300) {
    $greske[] = 'Trajanje mora biti između 30 i 300 minuta.';
}
if ($ocjena < 0 || $ocjena > 10) {
    $greske[] = 'Ocjena mora biti između 0.0 i 10.0.';
}
if (strlen($redatelj) < 1) {
    $greske[] = 'Redatelj je obvezan.';
}
if (strlen($zemlja) < 1) {
    $greske[] = 'Zemlja je obvezna.';
}

if (!empty($greske)) {
    // Vrati na formu s greškom (u produkciji koristiti flash session)
    $greskaStr = implode(' | ', $greske);
    header("Location: " . ($filmId ? "film_edit.php?id=$filmId&greska=" : "films.php?greska=") . urlencode($greskaStr));
    exit;
}

// ── Spremi u bazu ─────────────────────────────────────────────
try {
    if ($filmId > 0) {
        // UPDATE
        $stmt = $pdo->prepare(
            "UPDATE filmovi SET naslov=?, zanr=?, godina=?, trajanje=?, ocjena=?, redatelj=?, zemlja=?, opis=?
             WHERE id=?"
        );
        $stmt->execute([$naslov, $zanr, $godina, $trajanje, $ocjena, $redatelj, $zemlja, $opis, $filmId]);
        header('Location: films.php?poruka=azuriran');
    } else {
        // INSERT
        $stmt = $pdo->prepare(
            "INSERT INTO filmovi (naslov, zanr, godina, trajanje, ocjena, redatelj, zemlja, opis)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$naslov, $zanr, $godina, $trajanje, $ocjena, $redatelj, $zemlja, $opis]);
        header('Location: films.php?poruka=dodan');
    }
} catch (PDOException $e) {
    header('Location: films.php?greska=' . urlencode('Greška pri spremanju: ' . $e->getMessage()));
}
exit;
