<?php
// pages/laporan/index.php - Laporan Transaksi & Pendapatan Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'owner', 'petugas']);

$page_title = 'Laporan Parkir';
$page_heading = 'Laporan Transaksi & Pendapatan';
$current_page = 'laporan';

// Default filter: 7 hari terakhir s/d hari ini
$tgl_mulai  = trim($_GET['tgl_mulai'] ?? date('Y-m-d', strtotime('-6 days')));
$tgl_sampai = trim($_GET['tgl_sampai'] ?? date('Y-m-d'));
$status     = trim($_GET['status'] ?? '');
$jenis      = trim($_GET['jenis'] ?? '');
$id_area    = (int)($_GET['id_area'] ?? 0);

// Base query
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

// Jika role adalah petugas biasa, batasi hanya transaksinya sendiri jika diperlukan
if (isPetugas()) {
    $sql .= " AND t.id_user = ?";
    $params[] = (int)$_SESSION['user_id'];
}

$sql .= " ORDER BY t.id_parkir DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan_data = $stmt->fetchAll();

// Hitung Ringkasan
$total_masuk = count($laporan_data);
$total_keluar = 0;
$total_pendapatan = 0;
$total_durasi = 0;

foreach ($laporan_data as $row) {
    if ($row['status'] === 'keluar') {
        $total_keluar++;
        $total_pendapatan += (float)$row['biaya_total'];
        $total_durasi += (int)$row['durasi_jam'];
    }
}

$rata_durasi = $total_keluar > 0 ? round($total_durasi / $total_keluar, 1) : 0;

// Ambil data area untuk opsi filter
$areas = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Laporan & Rekapitulasi Parkir</h4>
        <p class="text-muted small mb-0">Periode: <?= tgl_indo($tgl_mulai, false) ?> s/d <?= tgl_indo($tgl_sampai, false) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('pages/laporan/cetak.php?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-dark rounded-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak Laporan (PDF)
        </a>
        <a href="<?= base_url('pages/laporan/export_excel.php?' . http_build_query($_GET)) ?>" class="btn btn-success rounded-3 shadow-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- 4 Kartu Metrik Ringkasan -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Kendaraan Masuk</span>
                <div class="stat-icon primary">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= number_format($total_masuk) ?></div>
            <div class="small text-muted mt-1">Sesuai rentang filter</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Selesai / Keluar</span>
                <div class="stat-icon success">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-success"><?= number_format($total_keluar) ?></div>
            <div class="small text-muted mt-1">Transaksi terbayar</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Pendapatan</span>
                <div class="stat-icon warning">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-warning"><?= rupiah($total_pendapatan) ?></div>
            <div class="small text-muted mt-1">Akumulasi pendapatan</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Rata-rata Durasi</span>
                <div class="stat-icon info">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-info"><?= $rata_durasi ?> Jam</div>
            <div class="small text-muted mt-1">Per kendaraan selesai</div>
        </div>
    </div>
</div>

<!-- Filter Panel -->
<div class="data-card p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-bold">Dari Tanggal</label>
            <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= e($tgl_mulai) ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold">Sampai Tanggal</label>
            <input type="date" name="tgl_sampai" class="form-control form-control-sm" value="<?= e($tgl_sampai) ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="masuk" <?= ($status === 'masuk') ? 'selected' : '' ?>>Parkir Aktif (Masuk)</option>
                <option value="keluar" <?= ($status === 'keluar') ? 'selected' : '' ?>>Selesai (Keluar)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold">Jenis Kendaraan</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="">Semua Jenis</option>
                <option value="motor" <?= ($jenis === 'motor') ? 'selected' : '' ?>>Motor</option>
                <option value="mobil" <?= ($jenis === 'mobil') ? 'selected' : '' ?>>Mobil</option>
                <option value="lainnya" <?= ($jenis === 'lainnya') ? 'selected' : '' ?>>Lainnya</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold">Area Parkir</label>
            <select name="id_area" class="form-select form-select-sm">
                <option value="0">Semua Area</option>
                <?php foreach ($areas as $ar): ?>
                    <option value="<?= $ar['id_area'] ?>" <?= ($id_area == $ar['id_area']) ? 'selected' : '' ?>>
                        <?= e($ar['nama_area']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="bi bi-funnel me-1"></i> Terapkan
            </button>
            <a href="<?= base_url('pages/laporan/index.php') ?>" class="btn btn-sm btn-light border" title="Reset">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabel Laporan -->
<div class="data-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">No. Tiket</th>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Area</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                    <th>Durasi</th>
                    <th>Tarif/Jam</th>
                    <th>Biaya Total</th>
                    <th>Status</th>
                    <th class="pe-4">Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($laporan_data)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>
                            Tidak ada transaksi pada filter periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($laporan_data as $row): ?>
                        <tr>
                            <td class="ps-4 fw-bold">#<?= str_pad($row['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><span class="plat-badge"><?= e($row['plat_nomor']) ?></span></td>
                            <td class="text-capitalize"><?= e($row['jenis_kendaraan']) ?></td>
                            <td><?= e($row['nama_area']) ?></td>
                            <td class="small"><?= tgl_indo($row['waktu_masuk'], true) ?></td>
                            <td class="small"><?= $row['waktu_keluar'] ? tgl_indo($row['waktu_keluar'], true) : '-' ?></td>
                            <td><?= $row['durasi_jam'] ? $row['durasi_jam'] . ' jam' : '-' ?></td>
                            <td class="small"><?= rupiah($row['tarif_per_jam']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'keluar'): ?>
                                    <strong class="text-success"><?= rupiah($row['biaya_total']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'masuk'): ?>
                                    <span class="badge-status-masuk">Terparkir</span>
                                <?php else: ?>
                                    <span class="badge-status-keluar">Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 small"><?= e($row['nama_petugas']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="8" class="ps-4 text-end">TOTAL PENDAPATAN PERIODE INI:</td>
                    <td colspan="3" class="pe-4 text-success fs-6"><?= rupiah($total_pendapatan) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

