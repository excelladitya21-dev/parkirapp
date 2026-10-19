<?php
// api/get_transaksi.php - Cari Transaksi Parkir Aktif untuk Checkout
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (empty($q)) {
    echo json_encode(['success' => false, 'message' => 'Parameter pencarian kosong']);
    exit;
}

try {
    // Cari berdasarkan id_parkir atau plat_nomor
    $stmt = $pdo->prepare("
        SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
               a.nama_area, tf.jenis_kendaraan AS tarif_jenis, tf.tarif_per_jam,
               u.nama_lengkap AS petugas_masuk
        FROM tb_transaksi t
        JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
        JOIN tb_area_parkir a ON t.id_area = a.id_area
        JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
        JOIN tb_user u ON t.id_user = u.id_user
        WHERE (t.id_parkir = ? OR k.plat_nomor = ?) AND t.status = 'masuk'
        LIMIT 1
    ");
    $stmt->execute([$q, $q]);
    $trx = $stmt->fetch();

    if ($trx) {
        $waktu_keluar_simulasi = date('Y-m-d H:i:s');
        $durasi = hitung_durasi_jam($trx['waktu_masuk'], $waktu_keluar_simulasi);
        $total_biaya = $durasi['jam_hitung'] * (float)$trx['tarif_per_jam'];

        echo json_encode([
            'success' => true,
            'data' => [
                'id_parkir' => (int)$trx['id_parkir'],
                'plat_nomor' => $trx['plat_nomor'],
                'jenis_kendaraan' => $trx['jenis_kendaraan'],
                'warna' => $trx['warna'],
                'pemilik' => $trx['pemilik'],
                'nama_area' => $trx['nama_area'],
                'waktu_masuk' => $trx['waktu_masuk'],
                'waktu_masuk_indo' => tgl_indo($trx['waktu_masuk'], true),
                'waktu_keluar' => $waktu_keluar_simulasi,
                'waktu_keluar_indo' => tgl_indo($waktu_keluar_simulasi, true),
                'durasi_display' => $durasi['display'],
                'durasi_jam' => $durasi['jam_hitung'],
                'tarif_per_jam' => (float)$trx['tarif_per_jam'],
                'tarif_per_jam_format' => rupiah($trx['tarif_per_jam']),
                'biaya_total' => $total_biaya,
                'biaya_total_format' => rupiah($total_biaya),
                'petugas_masuk' => $trx['petugas_masuk']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tiket atau plat nomor tidak ditemukan dalam status parkir aktif.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

