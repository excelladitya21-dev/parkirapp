<?php
// pages/area/index.php - Manajemen & Monitoring Area Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas', 'owner']);

$page_title = 'Area Parkir';
$page_heading = 'Manajemen & Status Area Parkir';
$current_page = 'area';

// Auto-sync
sync_area_terisi($pdo);

$stmt = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY id_area ASC");
$areas = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Status & Data Area Parkir</h4>
        <p class="text-muted small mb-0">Pantau kapasitas dan ketersediaan slot parkir secara langsung</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('pages/area/sync.php') ?>" class="btn btn-outline-secondary rounded-3 shadow-sm">
            <i class="bi bi-arrow-repeat me-1"></i> Sinkronkan Slot
        </a>
        <?php if (isAdmin()): ?>
            <a href="<?= base_url('pages/area/create.php') ?>" class="btn btn-primary rounded-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Area Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Visual Cards Keterisian Area -->
<div class="row g-4 mb-4">
    <?php foreach ($areas as $area): 
        $kapasitas = (int)$area['kapasitas'];
        $terisi = (int)$area['terisi'];
        $tersedia = max(0, $kapasitas - $terisi);
        $persen = $kapasitas > 0 ? round(($terisi / $kapasitas) * 100) : 0;
        
        $bar_color = 'bg-success';
        $badge_class = 'bg-success-subtle text-success border-success-subtle';
        $status_label = 'Tersedia';
        
        if ($persen >= 90) {
            $bar_color = 'bg-danger';
            $badge_class = 'bg-danger-subtle text-danger border-danger-subtle';
            $status_label = ($persen >= 100) ? 'Penuh' : 'Hampir Penuh';
        } elseif ($persen >= 70) {
            $bar_color = 'bg-warning';
            $badge_class = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
            $status_label = 'Mulai Padat';
        }
    ?>
    <div class="col-md-6 col-lg-6">
        <div class="data-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="bi bi-geo-alt-fill fs-5"></i>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark"><?= e($area['nama_area']) ?></h5>
                </div>
                <span class="badge border <?= $badge_class ?> rounded-pill px-3 py-2"><?= $status_label ?></span>
            </div>

            <div class="row text-center g-2 my-3">
                <div class="col-4">
                    <div class="p-2 bg-light rounded-3">
                        <div class="text-muted small">Kapasitas</div>
                        <div class="fs-4 fw-bold text-dark"><?= $kapasitas ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 bg-light rounded-3">
                        <div class="text-muted small">Terisi</div>
                        <div class="fs-4 fw-bold text-danger"><?= $terisi ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 bg-light rounded-3">
                        <div class="text-muted small">Sisa Kosong</div>
                        <div class="fs-4 fw-bold text-success"><?= $tersedia ?></div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-2">
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Tingkat Keterisian</span>
                    <span class="fw-bold text-dark"><?= $persen ?>%</span>
                </div>
                <div class="progress area-capacity-bar" style="height: 12px;">
                    <div class="progress-bar <?= $bar_color ?>" role="progressbar" style="width: <?= min(100, $persen) ?>%"></div>
                </div>
            </div>

            <?php if (isAdmin()): ?>
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= base_url('pages/area/edit.php?id=' . $area['id_area']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url('pages/area/delete.php?id=' . $area['id_area']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus area <?= e($area['nama_area']) ?>?')">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabel Ringkasan Area -->
<div class="data-card">
    <div class="card-header">
        <h6 class="fw-bold mb-0 text-dark">Data Master Area Parkir</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">ID Area</th>
                    <th>Nama Area</th>
                    <th>Kapasitas Slot</th>
                    <th>Terisi Saat Ini</th>
                    <th>Sisa Slot</th>
                    <th>Persentase</th>
                    <?php if (isAdmin()): ?>
                        <th class="text-center pe-4">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($areas as $area): 
                    $kap = (int)$area['kapasitas'];
                    $isi = (int)$area['terisi'];
                    $sisa = max(0, $kap - $isi);
                    $pct = $kap > 0 ? round(($isi / $kap) * 100) : 0;
                ?>
                    <tr>
                        <td class="ps-4 fw-bold">#<?= $area['id_area'] ?></td>
                        <td class="fw-semibold text-dark"><?= e($area['nama_area']) ?></td>
                        <td><?= $kap ?> slot</td>
                        <td><span class="badge bg-danger-subtle text-danger"><?= $isi ?> slot</span></td>
                        <td><span class="badge bg-success-subtle text-success"><?= $sisa ?> slot</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="small text-muted"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <?php if (isAdmin()): ?>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= base_url('pages/area/edit.php?id=' . $area['id_area']) ?>" class="btn btn-light border text-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= base_url('pages/area/delete.php?id=' . $area['id_area']) ?>" class="btn btn-light border text-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus area ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

