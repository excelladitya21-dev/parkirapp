<?php
// pages/area/sync.php - Sinkronkan Jumlah Slot Terisi
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas']);

sync_area_terisi($pdo);
catat_log($pdo, (int)$_SESSION['user_id'], "Melakukan sinkronisasi slot area parkir");

set_flash('success', 'Jumlah slot terisi di seluruh area parkir berhasil disinkronkan kembali dengan transaksi aktif!');
header("Location: " . base_url('pages/area/index.php'));
exit;

