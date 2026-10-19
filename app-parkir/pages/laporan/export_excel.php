<?php
// pages/laporan/export_excel.php - Export Data Transaksi ke Microsoft Excel (.xls)
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

// Jika role adalah petugas biasa, batasi hanya transaksinya sendiri
if (isPetugas()) {
    $sql .= " AND t.id_user = ?";
    $params[] = (int)$_SESSION['user_id'];
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

$filename = "Laporan_Parkir_" . date('Ymd_His') . ".xls";

// Header HTTP untuk download file Excel murni (.xls)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Transaksi Parkir</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #4a5568;
            padding: 8px 10px;
        }
        th {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
        }
        .header-sub {
            font-size: 11pt;
            text-align: center;
            color: #4b5563;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bg-total {
            background-color: #e2e8f0;
            font-weight: bold;
        }
        .text-str {
            mso-number-format: "\@"; /* Memastikan Excel membaca sebagai format teks agar plat nomor tidak terformat acak */
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="13" class="header-title" style="border:none;">SISTEM INFORMASI MANAJEMEN PARKIR</td>
    </tr>
    <tr>
        <td colspan="13" class="header-title" style="border:none;">LAPORAN REKAPITULASI TRANSAKSI PARKIR</td>
    </tr>
    <tr>
        <td colspan="13" class="header-sub" style="border:none;">
            Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s/d <?= date('d/m/Y', strtotime($tgl_sampai)) ?> 
            | Dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas') ?> 
            pada: <?= date('d/m/Y H:i') ?> WIB
        </td>
    </tr>
    <tr>
        <td colspan="13" style="border:none; height: 15px;"></td>
    </tr>
</table>

<table border="1">
    <thead>
        <tr>
            <th style="width: 40px;">No</th>
            <th>No. Tiket</th>
            <th>Plat Nomor</th>
            <th>Jenis Kendaraan</th>
            <th>Warna</th>
            <th>Pemilik</th>
            <th>Area Parkir</th>
            <th>Waktu Masuk</th>
            <th>Waktu Keluar</th>
            <th>Durasi (Jam)</th>
            <th>Tarif / Jam</th>
            <th>Total Biaya</th>
            <th>Status</th>
            <th>Petugas</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($data)): ?>
            <tr>
                <td colspan="14" class="text-center" style="padding: 20px;">Tidak ada transaksi parkir pada periode ini.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($data as $idx => $r): ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td class="text-center text-str">#<?= str_pad($r['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="text-center text-str" style="font-weight: bold;"><?= htmlspecialchars($r['plat_nomor']) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($r['jenis_kendaraan']) ?></td>
                    <td><?= htmlspecialchars($r['warna'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($r['pemilik'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($r['nama_area']) ?></td>
                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($r['waktu_masuk'])) ?></td>
                    <td class="text-center"><?= $r['waktu_keluar'] ? date('d/m/Y H:i', strtotime($r['waktu_keluar'])) : '-' ?></td>
                    <td class="text-center"><?= $r['durasi_jam'] ? $r['durasi_jam'] . ' Jam' : '-' ?></td>
                    <td class="text-right"><?= rupiah($r['tarif_per_jam']) ?></td>
                    <td class="text-right" style="font-weight: bold;">
                        <?= $r['status'] === 'keluar' ? rupiah($r['biaya_total']) : '-' ?>
                    </td>
                    <td class="text-center text-capitalize" style="font-weight: bold; color: <?= $r['status'] === 'masuk' ? '#15803d' : '#475569' ?>;">
                        <?= $r['status'] === 'masuk' ? 'Terparkir' : 'Selesai' ?>
                    </td>
                    <td><?= htmlspecialchars($r['nama_petugas']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="bg-total">
            <td colspan="11" class="text-right" style="font-weight: bold; font-size: 11pt;">TOTAL PENDAPATAN:</td>
            <td class="text-right" style="font-weight: bold; font-size: 11pt; color: #15803d;"><?= rupiah($total_pendapatan) ?></td>
            <td colspan="2" class="text-center"><?= $total_selesai ?> Transaksi Selesai</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
