<?php
/**
 * Logout GaiaCity
 * Menggunakan class User untuk proses logout (OOP).
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Menggunakan class User::logout() - OOP
$userObj = new User(Database::getInstance()->getConnection());
$userObj->logout();

// Redirect absolut agar tidak terpengaruh path pemanggil
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
header('Location: ' . $base . '/login.php');
exit;
