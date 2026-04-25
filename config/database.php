<?php
// ── Konfigurasi koneksi database ──────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // sesuaikan
define('DB_PASS', '');       // sesuaikan
define('DB_NAME', 'gaiacity_db');

// ── Autoload semua class dari folder /classes ─────────────────
$classDir = __DIR__ . '/../classes/';
foreach (glob($classDir . '*.php') as $classFile) {
    require_once $classFile;
}

/**
 * Helper backward-compatible: mengembalikan koneksi mysqli.
 */
function getConnection(): mysqli {
    return Database::getInstance()->getConnection();
}
