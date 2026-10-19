<?php
// pages/transaksi/struk.php - Cetak Struk Pembayaran Parkir
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

$last_co = $_SESSION['last_checkout'] ?? null;
$uang_bayar = ($last_co && $last_co['id_parkir'] == $id_parkir) ? (float)$last_co['uang_bayar'] : (float)$trx['biaya_total'];
$kembalian  = ($last_co && $last_co['id_parkir'] == $id_parkir) ? (float)$last_co['kembalian'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #<?= str_pad($trx['id_parkir'], 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
        }
        .struk-box {
            max-width: 340px;
            margin: 0 auto;
            background: #fff;
            padding: 20px 15px;
            border: 1px dashed #475569;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .dashed-line {
            border-top: 1px dashed #475569;
            margin: 10px 0;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .struk-box {
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
        <i class="bi bi-printer me-1"></i> Cetak Struk
    </button>
    <a href="<?= base_url('pages/transaksi/keluar.php') ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm ms-2">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <a href="<?= base_url('pages/dashboard/index.php') ?>" class="btn btn-light border btn-sm px-3 shadow-sm ms-1">
        Dashboard
    </a>
</div>

<div class="struk-box">
    <!-- Header Struk -->
    <div class="text-center">
        <h5 class="fw-bold mb-0">STRUK PARKIR</h5>
        <div style="font-size: 0.8rem;" class="text-muted">Kwitansi Pembayaran Resmi</div>
        <div class="dashed-line"></div>
    </div>

    <!-- Informasi Transaksi -->
    <table class="w-100" style="font-size: 0.85rem;">
        <tr>
            <td style="width: 45%;">No. Transaksi</td>
            <td class="fw-bold">: #<?= str_pad($trx['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
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
            <td>Masuk</td>
            <td>: <?= date('d/m/Y H:i', strtotime($trx['waktu_masuk'])) ?></td>
        </tr>
        <tr>
            <td>Keluar</td>
            <td>: <?= $trx['waktu_keluar'] ? date('d/m/Y H:i', strtotime($trx['waktu_keluar'])) : '-' ?></td>
        </tr>
        <tr>
            <td>Durasi</td>
            <td>: <?= $trx['durasi_jam'] ?> Jam</td>
        </tr>
        <tr>
            <td>Tarif/Jam</td>
            <td>: <?= rupiah($trx['tarif_per_jam']) ?></td>
        </tr>
    </table>

    <div class="dashed-line"></div>

    <!-- Total & Pembayaran -->
    <table class="w-100" style="font-size: 0.9rem;">
        <tr>
            <td><strong>TOTAL BIAYA</strong></td>
            <td class="text-end fw-bold"><strong><?= rupiah($trx['biaya_total']) ?></strong></td>
        </tr>
        <tr>
            <td>Bayar (Tunai)</td>
            <td class="text-end"><?= rupiah($uang_bayar) ?></td>
        </tr>
        <tr>
            <td>Kembalian</td>
            <td class="text-end"><?= rupiah($kembalian) ?></td>
        </tr>
    </table>

    <div class="dashed-line"></div>

    <!-- Petugas & Catatan -->
    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
        <span>Kasir: <?= e($_SESSION['nama_lengkap'] ?? $trx['nama_petugas']) ?></span>
        <span><?= date('d/m/Y') ?></span>
    </div>

    <div class="text-center mt-3" style="font-size: 0.72rem; line-height: 1.3;">
        <div>TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
        <div>HATI-HATI DI JALAN & SEMOGA SELAMAT</div>
    </div>
</div>

<script>
// Auto print bila diarahkan setelah checkout berhasil
<?php if (isset($_GET['checkout']) && $_GET['checkout'] === 'success'): ?>
window.onload = function() {
    window.print();
};
<?php endif; ?>
</script>
</body>
</html>

