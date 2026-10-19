<?php
// pages/kendaraan/delete.php - Hapus Kendaraan
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$id_kendaraan = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?");
$stmt->execute([$id_kendaraan]);
$kendaraan = $stmt->fetch();

if (!$kendaraan) {
    set_flash('danger', 'Kendaraan tidak ditemukan!');
    header("Location: " . base_url('pages/kendaraan/index.php'));
    exit;
}

// Cek apakah kendaraan memiliki riwayat transaksi di tb_transaksi
$stmt_trx = $pdo->prepare("SELECT COUNT(*) as total FROM tb_transaksi WHERE id_kendaraan = ?");
$stmt_trx->execute([$id_kendaraan]);
$has_trx = (int)$stmt_trx->fetch()['total'];

if ($has_trx > 0) {
    set_flash('danger', "Kendaraan plat {$kendaraan['plat_nomor']} tidak dapat dihapus karena memiliki {$has_trx} riwayat transaksi parkir!");
    header("Location: " . base_url('pages/kendaraan/index.php'));
    exit;
}

try {
    $stmt_del = $pdo->prepare("DELETE FROM tb_kendaraan WHERE id_kendaraan = ?");
    $stmt_del->execute([$id_kendaraan]);

    catat_log($pdo, (int)$_SESSION['user_id'], "Menghapus kendaraan plat {$kendaraan['plat_nomor']}");

    set_flash('success', "Kendaraan plat {$kendaraan['plat_nomor']} berhasil dihapus.");
} catch (PDOException $e) {
    set_flash('danger', "Gagal menghapus kendaraan: " . $e->getMessage());
}

header("Location: " . base_url('pages/kendaraan/index.php'));
exit;

