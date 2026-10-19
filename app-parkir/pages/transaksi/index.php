<?php
// pages/transaksi/index.php - Daftar Riwayat Transaksi Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas', 'owner']);

$page_title = 'Daftar Transaksi';
$page_heading = 'Riwayat Transaksi Parkir';
$current_page = 'transaksi';

// Filter parameter
$status_filter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$tgl = trim($_GET['tgl'] ?? '');
$area_filter = (int)($_GET['area'] ?? 0);

// Query builder
$sql = "
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
           a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    JOIN tb_user u ON t.id_user = u.id_user
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (k.plat_nomor LIKE ? OR k.pemilik LIKE ? OR t.id_parkir = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

if (!empty($tgl)) {
    $sql .= " AND (DATE(t.waktu_masuk) = ? OR DATE(t.waktu_keluar) = ?)";
    $params[] = $tgl;
    $params[] = $tgl;
}

if ($area_filter > 0) {
    $sql .= " AND t.id_area = ?";
    $params[] = $area_filter;
}

$sql .= " ORDER BY t.id_parkir DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll();

// List area untuk dropdown filter
$areas = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Daftar Transaksi Parkir</h4>
        <p class="text-muted small mb-0">Kelola dan pantau seluruh transaksi masuk dan keluar</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (isAdmin() || isPetugas()): ?>
            <a href="<?= base_url('pages/transaksi/masuk.php') ?>" class="btn btn-success rounded-3 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Kendaraan Masuk
            </a>
            <a href="<?= base_url('pages/transaksi/keluar.php') ?>" class="btn btn-danger rounded-3 shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Kendaraan Keluar
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Box -->
<div class="data-card p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <!-- Pencarian Plat / Tiket -->
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Plat / ID Tiket / Pemilik..." value="<?= e($search) ?>">
        </div>

        <!-- Filter Status -->
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="masuk" <?= ($status_filter === 'masuk') ? 'selected' : '' ?>>Parkir Aktif (Masuk)</option>
                <option value="keluar" <?= ($status_filter === 'keluar') ? 'selected' : '' ?>>Selesai (Keluar)</option>
            </select>
        </div>

        <!-- Filter Area -->
        <div class="col-md-3">
            <select name="area" class="form-select form-select-sm">
                <option value="0">Semua Area Parkir</option>
                <?php foreach ($areas as $ar): ?>
                    <option value="<?= $ar['id_area'] ?>" <?= ($area_filter == $ar['id_area']) ? 'selected' : '' ?>>
                        <?= e($ar['nama_area']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter Tanggal -->
        <div class="col-md-2">
            <input type="date" name="tgl" class="form-control form-control-sm" value="<?= e($tgl) ?>">
        </div>

        <!-- Tombol Filter -->
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="<?= base_url('pages/transaksi/index.php') ?>" class="btn btn-sm btn-light border" title="Reset Filter">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabel Transaksi -->
<div class="data-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">No. Tiket</th>
                    <th>Plat Nomor</th>
                    <th>Kendaraan</th>
                    <th>Area</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                    <th>Durasi</th>
                    <th>Biaya Total</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transaksi_list)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada data transaksi yang sesuai filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksi_list as $trx): 
                        $dur = hitung_durasi_jam($trx['waktu_masuk'], $trx['waktu_keluar']);
                    ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#<?= str_pad($trx['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <span class="plat-badge"><?= e($trx['plat_nomor']) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-capitalize"><?= e($trx['jenis_kendaraan']) ?></div>
                                <div class="small text-muted"><?= e($trx['warna'] ?: '-') ?> &bull; <?= e($trx['pemilik'] ?: '-') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <?= e($trx['nama_area']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= tgl_indo($trx['waktu_masuk'], true) ?></div>
                                <div class="small text-muted">Petugas: <?= e($trx['nama_petugas']) ?></div>
                            </td>
                            <td>
                                <?php if ($trx['status'] === 'keluar' && $trx['waktu_keluar']): ?>
                                    <div class="small fw-semibold"><?= tgl_indo($trx['waktu_keluar'], true) ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($trx['status'] === 'keluar'): ?>
                                    <span class="small fw-semibold"><?= $trx['durasi_jam'] ?> Jam</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">
                                        <i class="bi bi-clock me-1"></i><?= $dur['display'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($trx['status'] === 'keluar'): ?>
                                    <strong class="text-success"><?= rupiah($trx['biaya_total']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted small"><?= rupiah($trx['tarif_per_jam']) ?>/jam</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($trx['status'] === 'masuk'): ?>
                                    <span class="badge-status-masuk">
                                        <i class="bi bi-p-circle-fill me-1"></i> Terparkir
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status-keluar">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i> Selesai
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($trx['status'] === 'masuk'): ?>
                                        <?php if (isAdmin() || isPetugas()): ?>
                                            <a href="<?= base_url('pages/transaksi/keluar.php?id=' . $trx['id_parkir']) ?>" class="btn btn-danger" title="Proses Checkout">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Checkout
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('pages/transaksi/karcis.php?id=' . $trx['id_parkir']) ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Karcis Masuk">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('pages/transaksi/struk.php?id=' . $trx['id_parkir']) ?>" target="_blank" class="btn btn-outline-success" title="Cetak Struk Pembayaran">
                                            <i class="bi bi-receipt me-1"></i> Struk
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

