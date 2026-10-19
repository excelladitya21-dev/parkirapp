<?php
// pages/tarif/create.php - Tambah Tarif Parkir Baru
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$page_title = 'Tambah Tarif';
$page_heading = 'Tambah Tarif Parkir Baru';
$current_page = 'tarif';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? 'motor');
    $tarif_per_jam   = (float)($_POST['tarif_per_jam'] ?? 0);

    if ($tarif_per_jam <= 0) {
        $error = 'Tarif per jam harus lebih besar dari 0!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES (?, ?)");
            $stmt->execute([$jenis_kendaraan, $tarif_per_jam]);

            catat_log($pdo, (int)$_SESSION['user_id'], "Menambahkan tarif {$jenis_kendaraan}: " . rupiah($tarif_per_jam) . "/jam");

            set_flash('success', "Tarif untuk jenis {$jenis_kendaraan} berhasil ditambahkan!");
            header("Location: " . base_url('pages/tarif/index.php'));
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Form Tambah Tarif Parkir</h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Kendaraan <span class="text-danger">*</span></label>
                    <select name="jenis_kendaraan" class="form-select" required>
                        <option value="motor">🏍️ Motor</option>
                        <option value="mobil">🚗 Mobil</option>
                        <option value="lainnya">🚚 Lainnya (Truk / Bus)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Tarif Per Jam (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="tarif_per_jam" class="form-control" placeholder="Contoh: 3000" min="100" step="500" required value="<?= e($_POST['tarif_per_jam'] ?? '') ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/tarif/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Simpan Tarif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

