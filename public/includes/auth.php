<?php
// ── AUTENTIFIKACIJA ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jePrijavljen(): bool {
    return isset($_SESSION['korisnik_id']);
}

function jeAdmin(): bool {
    return isset($_SESSION['uloga']) && $_SESSION['uloga'] === 'admin';
}

function zahtijevajPrijavu(): void {
    if (!jePrijavljen()) {
        header('Location: auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function zahtijevajAdmina(): void {
    zahtijevajPrijavu();
    if (!jeAdmin()) {
        header('Location: index.php?greska=pristup');
        exit;
    }
}

function trenutniKorisnik(): ?array {
    if (!jePrijavljen()) return null;
    return [
        'id'    => $_SESSION['korisnik_id'],
        'ime'   => $_SESSION['korisnik_ime'],
        'uloga' => $_SESSION['uloga'] ?? 'korisnik',
    ];
}