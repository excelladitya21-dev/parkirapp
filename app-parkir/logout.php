<?php
// logout.php - Logout Pengguna
define('APP_INIT', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $nama = $_SESSION['nama_lengkap'];
    catat_log($pdo, $user_id, "User {$nama} berhasil logout dari sistem");
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

session_start();
set_flash('info', 'Anda telah berhasil logout.');
header("Location: " . base_url('login.php'));
exit;
