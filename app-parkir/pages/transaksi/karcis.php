<?php
// pages/transaksi/karcis.php - Cetak Karcis Masuk Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin();

$id_parkir = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    JOIN tb_user u ON t.id_user = u.id_user
    WHERE t.id_parkir = ?
");
$stmt->execute([$id_parkir]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Data transaksi tidak ditemukan!");
}

$barcode_val = str_pad($trx['id_parkir'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karcis Parkir #<?= $barcode_val ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            background-color: #f1f5f9;
            padding: 20px;
        }
        .ticket-box {
            max-width: 330px;
            margin: 0 auto;
            background: #fff;
            padding: 20px 15px;
            border: 1px dashed #475569;
            font-family: 'Courier New', Courier, monospace;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .dashed-line {
            border-top: 1px dashed #475569;
            margin: 10px 0;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .ticket-box {
                border: none;
                box-shadow: none;
                width: 100% !important;
                max-width: 100% !important;
                padding: 5px;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
        <i class="bi bi-printer me-1"></i> Cetak Karcis
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm px-3 shadow-sm ms-2">
        <i class="bi bi-x-circle me-1"></i> Tutup
    </button>
</div>

<div class="ticket-box">
    <!-- Header -->
    <div class="text-center">
        <h5 class="fw-bold mb-0" style="letter-spacing: 1px;">PARKIR TERPADU</h5>
        <div class="small text-muted" style="font-size: 0.8rem;">Sistem Informasi Manajemen Parkir</div>
        <div class="dashed-line"></div>
    </div>

    <!-- Barcode -->
    <div class="text-center my-2">
        <svg id="barcode"></svg>
        <div class="fw-bold" style="font-size: 0.85rem; letter-spacing: 2px;">* <?= $barcode_val ?> *</div>
    </div>

    <div class="dashed-line"></div>

    <!-- Informasi Detail -->
    <table class="w-100" style="font-size: 0.85rem;">
        <tr>
            <td style="width: 45%;">No. Karcis</td>
            <td class="fw-bold">: #<?= $barcode_val ?></td>
        </tr>
        <tr>
            <td>Plat Nomor</td>
            <td class="fw-bold">: <?= e($trx['plat_nomor']) ?></td>
        </tr>
        <tr>
            <td>Jenis Kendaraan</td>
            <td>: <?= ucfirst($trx['jenis_kendaraan']) ?></td>
        </tr>
        <tr>
            <td>Area Parkir</td>
            <td>: <?= e($trx['nama_area']) ?></td>
        </tr>
        <tr>
            <td>Tarif Per Jam</td>
            <td>: <?= rupiah($trx['tarif_per_jam']) ?></td>
        </tr>
        <tr>
            <td>Waktu Masuk</td>
            <td>: <?= date('d/m/Y H:i', strtotime($trx['waktu_masuk'])) ?></td>
        </tr>
        <tr>
            <td>Petugas Masuk</td>
            <td>: <?= e($trx['nama_petugas']) ?></td>
        </tr>
    </table>

    <div class="dashed-line"></div>

    <!-- Peraturan & Footer -->
    <div class="text-center" style="font-size: 0.72rem; line-height: 1.3;">
        <div><strong>PERHATIAN:</strong></div>
        <div>1. Karcis hilang dikenakan denda.</div>
        <div>2. Jangan meninggalkan barang berharga.</div>
        <div>3. Kerusakan / kehilangan di luar tanggung jawab pengelola.</div>
        <div class="mt-2 fw-semibold">-- TERIMA KASIH --</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
JsBarcode("#barcode", "<?= $barcode_val ?>", {
    format: "CODE128",
    lineColor: "#000",
    width: 2,
    height: 40,
    displayValue: false
});

// Auto-trigger print jika ada query parameter print=1
<?php if (isset($_GET['print']) && $_GET['print'] == 1): ?>
window.onload = function() {
    window.print();
};
<?php endif; ?>
</script>
</body>
</html>

