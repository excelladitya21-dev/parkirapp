<?php
// pages/area/create.php - Tambah Area Parkir Baru
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$page_title = 'Tambah Area Parkir';
$page_heading = 'Tambah Area Parkir Baru';
$current_page = 'area';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_area = trim($_POST['nama_area'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);

    if (empty($nama_area)) {
        $error = 'Nama area wajib diisi!';
    } elseif ($kapasitas <= 0) {
        $error = 'Kapasitas slot minimal 1!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES (?, ?, 0)");
            $stmt->execute([$nama_area, $kapasitas]);

            catat_log($pdo, (int)$_SESSION['user_id'], "Menambahkan area parkir: {$nama_area} (Kapasitas: {$kapasitas})");

            set_flash('success', "Area parkir '{$nama_area}' berhasil ditambahkan!");
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Form Tambah Area Parkir</h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Area Parkir <span class="text-danger">*</span></label>
                    <input type="text" name="nama_area" class="form-control" placeholder="Contoh: Area C - VIP / Truk" required autofocus value="<?= e($_POST['nama_area'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Kapasitas Slot Maksimal <span class="text-danger">*</span></label>
                    <input type="number" name="kapasitas" class="form-control" placeholder="Contoh: 50" min="1" required value="<?= e($_POST['kapasitas'] ?? '') ?>">
                    <div class="form-text">Batas maksimum jumlah kendaraan yang dapat parkir bersamaan di area ini.</div>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/area/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Simpan Area
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

