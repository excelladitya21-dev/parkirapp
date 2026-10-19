<?php
// pages/user/toggle_status.php - Toggle Aktif / Nonaktif Pengguna
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$id_user = (int)($_GET['id'] ?? 0);

if ($id_user == $_SESSION['user_id']) {
    set_flash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri!');
    header("Location: " . base_url('pages/user/index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'Pengguna tidak ditemukan!');
    header("Location: " . base_url('pages/user/index.php'));
    exit;
}

$new_status = ((int)$user['status_aktif'] === 1) ? 0 : 1;
$status_label = ($new_status === 1) ? 'diaktifkan' : 'dinonaktifkan';

$stmt_up = $pdo->prepare("UPDATE tb_user SET status_aktif = ? WHERE id_user = ?");
$stmt_up->execute([$new_status, $id_user]);

catat_log($pdo, (int)$_SESSION['user_id'], "Mengubah status akun @{$user['username']} menjadi {$status_label}");

set_flash('success', "Akun @{$user['username']} berhasil {$status_label}.");
header("Location: " . base_url('pages/user/index.php'));
exit;

