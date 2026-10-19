<?php
// pages/kendaraan/create.php - Tambah Kendaraan Baru
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas']);

$page_title = 'Tambah Kendaraan';
$page_heading = 'Registrasi Kendaraan Baru';
$current_page = 'kendaraan';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? 'motor');
    $warna           = trim($_POST['warna'] ?? '');
    $pemilik         = trim($_POST['pemilik'] ?? '');
    $id_user         = (int)$_SESSION['user_id'];

    if (empty($plat_nomor)) {
        $error = 'Plat nomor wajib diisi!';
    } else {
        // Cek apakah plat nomor sudah ada
        $stmt_cek = $pdo->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ?");
        $stmt_cek->execute([$plat_nomor]);
        if ($stmt_cek->fetch()) {
            $error = "Kendaraan dengan nomor plat {$plat_nomor} sudah terdaftar!";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_user]);
                
                catat_log($pdo, $id_user, "Mendaftarkan kendaraan baru: {$plat_nomor} ({$jenis_kendaraan})");

                set_flash('success', "Kendaraan dengan plat {$plat_nomor} berhasil didaftarkan!");
                header("Location: " . base_url('pages/kendaraan/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Form Registrasi Kendaraan</h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nomor Plat Kendaraan <span class="text-danger">*</span></label>
                    <input type="text" name="plat_nomor" class="form-control text-uppercase fw-bold" placeholder="Contoh: B 1234 ABC" required autofocus value="<?= e($_POST['plat_nomor'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Kendaraan <span class="text-danger">*</span></label>
                    <select name="jenis_kendaraan" class="form-select" required>
                        <option value="motor">🏍️ Sepeda Motor</option>
                        <option value="mobil" selected>🚗 Mobil</option>
                        <option value="lainnya">🚚 Truk / Lainnya</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Warna Kendaraan</label>
                    <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam Metalik, Putih" value="<?= e($_POST['warna'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Nama Pemilik / Pelanggan</label>
                    <input type="text" name="pemilik" class="form-control" placeholder="Contoh: Budi Prasetyo" value="<?= e($_POST['pemilik'] ?? '') ?>">
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/kendaraan/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Simpan Kendaraan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

