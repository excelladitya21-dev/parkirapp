<?php
// pages/tarif/index.php - Kelola Tarif Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$page_title = 'Tarif Parkir';
$page_heading = 'Manajemen Tarif Parkir';
$current_page = 'tarif';

$stmt = $pdo->query("SELECT * FROM tb_tarif ORDER BY id_tarif ASC");
$tarifs = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Daftar Tarif Parkir</h4>
        <p class="text-muted small mb-0">Atur besaran tarif parkir per jam berdasarkan jenis kendaraan</p>
    </div>
    <a href="<?= base_url('pages/tarif/create.php') ?>" class="btn btn-primary rounded-3 shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Tambah Tarif Baru
    </a>
</div>

<!-- Kartu Tarif Ringkasan -->
<div class="row g-3 mb-4">
    <?php foreach ($tarifs as $tf): 
        $icon = 'bi-car-front';
        $bg = 'bg-primary-subtle text-primary';
        if ($tf['jenis_kendaraan'] === 'motor') {
            $icon = 'bi-bicycle';
            $bg = 'bg-success-subtle text-success';
        } elseif ($tf['jenis_kendaraan'] === 'lainnya') {
            $icon = 'bi-truck';
            $bg = 'bg-warning-subtle text-warning-emphasis';
        }
    ?>
    <div class="col-md-4">
        <div class="data-card p-4 text-center h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-inline-flex p-3 rounded-circle <?= $bg ?> fs-3 mb-3">
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <h5 class="fw-bold text-capitalize mb-1"><?= e($tf['jenis_kendaraan']) ?></h5>
                <div class="fs-3 fw-bold text-dark my-2"><?= rupiah($tf['tarif_per_jam']) ?> <span class="fs-6 text-muted fw-normal">/ jam</span></div>
            </div>
            <div class="d-flex justify-content-center gap-2 mt-3 pt-3 border-top">
                <a href="<?= base_url('pages/tarif/edit.php?id=' . $tf['id_tarif']) ?>" class="btn btn-sm btn-outline-primary px-3">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <a href="<?= base_url('pages/tarif/delete.php?id=' . $tf['id_tarif']) ?>" class="btn btn-sm btn-outline-danger px-3" onclick="return confirm('Yakin ingin menghapus tarif <?= e($tf['jenis_kendaraan']) ?>?')">
                    <i class="bi bi-trash me-1"></i> Hapus
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabel Tarif -->
<div class="data-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">ID Tarif</th>
                    <th>Jenis Kendaraan</th>
                    <th>Tarif Per Jam</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tarifs as $tf): ?>
                    <tr>
                        <td class="ps-4 fw-bold">#<?= $tf['id_tarif'] ?></td>
                        <td class="text-capitalize fw-semibold"><?= e($tf['jenis_kendaraan']) ?></td>
                        <td class="fw-bold text-success fs-6"><?= rupiah($tf['tarif_per_jam']) ?></td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= base_url('pages/tarif/edit.php?id=' . $tf['id_tarif']) ?>" class="btn btn-light border text-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url('pages/tarif/delete.php?id=' . $tf['id_tarif']) ?>" class="btn btn-light border text-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data tarif ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

