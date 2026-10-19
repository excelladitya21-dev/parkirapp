<?php
// pages/area/edit.php - Edit Area Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$id_area = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tb_area_parkir WHERE id_area = ?");
$stmt->execute([$id_area]);
$area = $stmt->fetch();

if (!$area) {
    set_flash('danger', 'Area parkir tidak ditemukan!');
    header("Location: " . base_url('pages/area/index.php'));
    exit;
}

$page_title = 'Edit Area Parkir';
$page_heading = 'Edit Area Parkir #' . $id_area;
$current_page = 'area';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_area = trim($_POST['nama_area'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);

    if (empty($nama_area)) {
        $error = 'Nama area wajib diisi!';
    } elseif ($kapasitas < (int)$area['terisi']) {
        $error = "Kapasitas tidak boleh lebih kecil dari kendaraan yang sedang parkir saat ini ({$area['terisi']} kendaraan)!";
    } else {
        try {
            $stmt_up = $pdo->prepare("UPDATE tb_area_parkir SET nama_area = ?, kapasitas = ? WHERE id_area = ?");
            $stmt_up->execute([$nama_area, $kapasitas, $id_area]);

            catat_log($pdo, (int)$_SESSION['user_id'], "Mengubah data area parkir ID {$id_area} ({$nama_area})");

            set_flash('success', "Area parkir '{$nama_area}' berhasil diperbarui!");
            header("Location: " . base_url('pages/area/index.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= e($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="data-card p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Edit Data Area Parkir</h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Area Parkir <span class="text-danger">*</span></label>
                    <input type="text" name="nama_area" class="form-control" required value="<?= e($area['nama_area']) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Kapasitas Slot Maksimal <span class="text-danger">*</span></label>
                    <input type="number" name="kapasitas" class="form-control" min="<?= (int)$area['terisi'] ?>" required value="<?= $area['kapasitas'] ?>">
                    <div class="form-text">Saat ini sedang terisi <strong><?= $area['terisi'] ?></strong> slot.</div>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/area/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Perbarui Area
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

