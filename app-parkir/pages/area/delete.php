<?php
// pages/area/delete.php - Hapus Area Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$id_area = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tb_area_parkir WHERE id_area = ?");
$stmt->execute([$id_area]);
$area = $stmt->fetch();

if (!$area) {
    set_flash('danger', 'Area parkir tidak ditemukan!');
    header("Location: " . base_url('pages/area/index.php'));
    exit;
}

// Cek apakah ada riwayat transaksi di area ini
$stmt_cek = $pdo->prepare("SELECT COUNT(*) AS total FROM tb_transaksi WHERE id_area = ?");
$stmt_cek->execute([$id_area]);
$has_trx = (int)$stmt_cek->fetch()['total'];

if ($has_trx > 0) {
    set_flash('danger', "Area parkir '{$area['nama_area']}' tidak dapat dihapus karena memiliki {$has_trx} riwayat transaksi parkir!");
    header("Location: " . base_url('pages/area/index.php'));
    exit;
}

try {
    $stmt_del = $pdo->prepare("DELETE FROM tb_area_parkir WHERE id_area = ?");
    $stmt_del->execute([$id_area]);

    catat_log($pdo, (int)$_SESSION['user_id'], "Menghapus area parkir '{$area['nama_area']}'");

    set_flash('success', "Area parkir '{$area['nama_area']}' berhasil dihapus.");
} catch (PDOException $e) {
    set_flash('danger', "Gagal menghapus: " . $e->getMessage());
}

header("Location: " . base_url('pages/area/index.php'));
exit;

