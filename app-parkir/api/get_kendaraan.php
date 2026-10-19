<?php
// api/get_kendaraan.php - Cari Data Kendaraan via AJAX
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$plat = trim($_GET['plat'] ?? '');
if (empty($plat)) {
    echo json_encode(['success' => false, 'message' => 'Plat nomor kosong']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM tb_kendaraan WHERE plat_nomor = ? LIMIT 1");
    $stmt->execute([$plat]);
    $kendaraan = $stmt->fetch();

    if ($kendaraan) {
        // Cek juga apakah saat ini sedang parkir aktif
        $stmt_parkir = $pdo->prepare("SELECT id_parkir, waktu_masuk, id_area FROM tb_transaksi WHERE id_kendaraan = ? AND status = 'masuk' LIMIT 1");
        $stmt_parkir->execute([$kendaraan['id_kendaraan']]);
        $is_parkir = $stmt_parkir->fetch();

        echo json_encode([
            'success' => true,
            'data' => $kendaraan,
            'is_parkir_aktif' => (bool)$is_parkir,
            'parkir_info' => $is_parkir
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kendaraan belum terdaftar']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

