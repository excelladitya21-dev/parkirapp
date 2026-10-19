<?php
// pages/kendaraan/index.php - Manajemen Data Kendaraan
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas', 'owner']);

$page_title = 'Data Kendaraan';
$page_heading = 'Manajemen Data Kendaraan';
$current_page = 'kendaraan';

$search = trim($_GET['search'] ?? '');
$jenis = trim($_GET['jenis'] ?? '');

$sql = "
    SELECT k.*, u.nama_lengkap AS nama_pendaftar,
           (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_kendaraan = k.id_kendaraan AND t.status = 'masuk') AS sedang_parkir
    FROM tb_kendaraan k
    JOIN tb_user u ON k.id_user = u.id_user
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (k.plat_nomor LIKE ? OR k.pemilik LIKE ? OR k.warna LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($jenis)) {
    $sql .= " AND k.jenis_kendaraan = ?";
    $params[] = $jenis;
}

$sql .= " ORDER BY k.id_kendaraan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kendaraan_list = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Data Kendaraan Terdaftar</h4>
        <p class="text-muted small mb-0">Total: <?= count($kendaraan_list) ?> kendaraan tercatat dalam sistem</p>
    </div>
    <?php if (isAdmin() || isPetugas()): ?>
        <a href="<?= base_url('pages/kendaraan/create.php') ?>" class="btn btn-primary rounded-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kendaraan Baru
        </a>
    <?php endif; ?>
</div>

<!-- Filter Pencarian -->
<div class="data-card p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Plat Nomor / Pemilik / Warna..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="jenis" class="form-select form-select-sm">
                <option value="">Semua Jenis Kendaraan</option>
                <option value="motor" <?= ($jenis === 'motor') ? 'selected' : '' ?>>Sepeda Motor</option>
                <option value="mobil" <?= ($jenis === 'mobil') ? 'selected' : '' ?>>Mobil</option>
                <option value="lainnya" <?= ($jenis === 'lainnya') ? 'selected' : '' ?>>Lainnya</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="bi bi-search me-1"></i> Cari
            </button>
            <a href="<?= base_url('pages/kendaraan/index.php') ?>" class="btn btn-sm btn-light border" title="Reset">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabel Kendaraan -->
<div class="data-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Warna</th>
                    <th>Pemilik</th>
                    <th>Status Parkir</th>
                    <th>Didata Oleh</th>
                    <?php if (isAdmin() || isPetugas()): ?>
                        <th class="text-center pe-4">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kendaraan_list)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-car-front fs-2 d-block mb-2"></i>
                            Tidak ada data kendaraan yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kendaraan_list as $i => $row): ?>
                        <tr>
                            <td class="ps-4"><?= $i + 1 ?></td>
                            <td>
                                <span class="plat-badge"><?= e($row['plat_nomor']) ?></span>
                            </td>
                            <td class="text-capitalize fw-semibold">
                                <?php if ($row['jenis_kendaraan'] === 'motor'): ?>
                                    🏍️ Motor
                                <?php elseif ($row['jenis_kendaraan'] === 'mobil'): ?>
                                    🚗 Mobil
                                <?php else: ?>
                                    🚚 <?= e($row['jenis_kendaraan']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['warna'] ?: '-') ?></td>
                            <td><?= e($row['pemilik'] ?: '-') ?></td>
                            <td>
                                <?php if ((int)$row['sedang_parkir'] > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-p-circle-fill me-1"></i> Sedang Parkir
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">
                                        Tidak Parkir
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= e($row['nama_pendaftar']) ?></td>
                            <?php if (isAdmin() || isPetugas()): ?>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('pages/kendaraan/edit.php?id=' . $row['id_kendaraan']) ?>" class="btn btn-light border text-primary" title="Edit Kendaraan">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php if (isAdmin()): ?>
                                            <a href="<?= base_url('pages/kendaraan/delete.php?id=' . $row['id_kendaraan']) ?>" class="btn btn-light border text-danger" title="Hapus Kendaraan" onclick="return confirm('Apakah Anda yakin ingin menghapus data kendaraan plat <?= e($row['plat_nomor']) ?>?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

