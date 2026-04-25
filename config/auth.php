<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Fungsi-fungsi berikut merupakan wrapper dari class User
 * agar file login.php / register.php tetap kompatibel.
 */

function requireLogin(string $redirect = '../login.php'): void {
    User::requireLogin($redirect);
}

function currentUser(): array {
    return User::currentUser();
}

function redirectByRole(string $role): void {
    User::redirectByRole($role);
}
