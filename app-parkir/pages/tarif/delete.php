<?php
// pages/tarif/delete.php - Hapus Tarif
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$id_tarif = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tb_tarif WHERE id_tarif = ?");
$stmt->execute([$id_tarif]);
$tarif = $stmt->fetch();

if (!$tarif) {
    set_flash('danger', 'Tarif tidak ditemukan!');
    header("Location: " . base_url('pages/tarif/index.php'));
    exit;
}

// Cek foreign key usage
$stmt_cek = $pdo->prepare("SELECT COUNT(*) as total FROM tb_transaksi WHERE id_tarif = ?");
$stmt_cek->execute([$id_tarif]);
$has_trx = (int)$stmt_cek->fetch()['total'];

if ($has_trx > 0) {
    set_flash('danger', "Tarif untuk '{$tarif['jenis_kendaraan']}' tidak dapat dihapus karena digunakan pada {$has_trx} data transaksi!");
    header("Location: " . base_url('pages/tarif/index.php'));
    exit;
}

try {
    $stmt_del = $pdo->prepare("DELETE FROM tb_tarif WHERE id_tarif = ?");
    $stmt_del->execute([$id_tarif]);

    catat_log($pdo, (int)$_SESSION['user_id'], "Menghapus tarif ID {$id_tarif} ({$tarif['jenis_kendaraan']})");

    set_flash('success', "Tarif berhasil dihapus.");
} catch (PDOException $e) {
    set_flash('danger', "Gagal menghapus: " . $e->getMessage());
}

header("Location: " . base_url('pages/tarif/index.php'));
exit;

