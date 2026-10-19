<?php
// pages/laporan/cetak.php - Cetak Dokumen Laporan Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'owner', 'petugas']);

$tgl_mulai  = trim($_GET['tgl_mulai'] ?? date('Y-m-d', strtotime('-6 days')));
$tgl_sampai = trim($_GET['tgl_sampai'] ?? date('Y-m-d'));
$status     = trim($_GET['status'] ?? '');
$jenis      = trim($_GET['jenis'] ?? '');
$id_area    = (int)($_GET['id_area'] ?? 0);

$sql = "
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    JOIN tb_user u ON t.id_user = u.id_user
    WHERE DATE(t.waktu_masuk) >= ? AND DATE(t.waktu_masuk) <= ?
";
$params = [$tgl_mulai, $tgl_sampai];

if (!empty($status)) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}

if (!empty($jenis)) {
    $sql .= " AND k.jenis_kendaraan = ?";
    $params[] = $jenis;
}

if ($id_area > 0) {
    $sql .= " AND t.id_area = ?";
    $params[] = $id_area;
}

$sql .= " ORDER BY t.id_parkir ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$total_pendapatan = 0;
$total_selesai = 0;
foreach ($data as $d) {
    if ($d['status'] === 'keluar') {
        $total_pendapatan += (float)$d['biaya_total'];
        $total_selesai++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi Parkir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 13px; }
        .table-bordered th, .table-bordered td { border: 1px solid #333 !important; }
        @media print {
            .no-print { display: none !important; }
            @page { size: landscape; margin: 15mm; }
        }
    </style>
</head>
<body class="p-4">

<div class="no-print mb-4 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
        Cetak Sekarang (Print)
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm px-3 ms-2">
        Tutup
    </button>
</div>

<!-- Kop Surat Laporan -->
<div class="text-center border-bottom border-dark pb-3 mb-4">
    <h3 class="fw-bold mb-1">SISTEM INFORMASI MANAJEMEN PARKIR</h3>
    <h5 class="fw-bold mb-1">LAPORAN REKAPITULASI TRANSAKSI PARKIR</h5>
    <div>Periode: <strong><?= tgl_indo($tgl_mulai, false) ?></strong> s/d <strong><?= tgl_indo($tgl_sampai, false) ?></strong></div>
</div>

<!-- Ringkasan Info -->
<div class="row mb-3">
    <div class="col-6">
        <div>Total Transaksi: <strong><?= count($data) ?> kendaraan</strong></div>
        <div>Total Selesai: <strong><?= $total_selesai ?> transaksi</strong></div>
    </div>
    <div class="col-6 text-end">
        <div>Dicetak Oleh: <strong><?= e($_SESSION['nama_lengkap']) ?></strong></div>
        <div>Waktu Cetak: <strong><?= date('d/m/Y H:i') ?> WIB</strong></div>
    </div>
</div>

<table class="table table-bordered table-sm align-middle">
    <thead class="table-light text-center">
        <tr>
            <th>No</th>
            <th>No. Tiket</th>
            <th>Plat Nomor</th>
            <th>Jenis</th>
            <th>Area</th>
            <th>Waktu Masuk</th>
            <th>Waktu Keluar</th>
            <th>Durasi</th>
            <th>Tarif/Jam</th>
            <th>Total Biaya</th>
            <th>Status</th>
            <th>Petugas</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($data)): ?>
            <tr>
                <td colspan="12" class="text-center py-3">Tidak ada data transaksi parkir pada periode ini.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $idx => $r): ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td class="text-center fw-bold">#<?= str_pad($r['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="fw-bold"><?= e($r['plat_nomor']) ?></td>
                    <td class="text-capitalize"><?= e($r['jenis_kendaraan']) ?></td>
                    <td><?= e($r['nama_area']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['waktu_masuk'])) ?></td>
                    <td><?= $r['waktu_keluar'] ? date('d/m/Y H:i', strtotime($r['waktu_keluar'])) : '-' ?></td>
                    <td class="text-center"><?= $r['durasi_jam'] ? $r['durasi_jam'] . ' jam' : '-' ?></td>
                    <td class="text-end"><?= rupiah($r['tarif_per_jam']) ?></td>
                    <td class="text-end fw-bold"><?= $r['status'] === 'keluar' ? rupiah($r['biaya_total']) : '-' ?></td>
                    <td class="text-center text-capitalize"><?= e($r['status']) ?></td>
                    <td><?= e($r['nama_petugas']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="fw-bold table-light">
            <td colspan="9" class="text-end">TOTAL PENDAPATAN PARKIR:</td>
            <td class="text-end"><?= rupiah($total_pendapatan) ?></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<!-- Tanda Tangan -->
<div class="row mt-5 pt-4">
    <div class="col-6 text-center">
        <div>Petugas Kasir</div>
        <div style="height: 60px;"></div>
        <div>( _____________________ )</div>
    </div>
    <div class="col-6 text-center">
        <div>Penanggung Jawab / Manajemen</div>
        <div style="height: 60px;"></div>
        <div>( _____________________ )</div>
    </div>
</div>

</body>
</html>

