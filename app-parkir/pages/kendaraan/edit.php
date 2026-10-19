<?php
// pages/kendaraan/edit.php - Edit Data Kendaraan
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas']);

$id_kendaraan = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?");
$stmt->execute([$id_kendaraan]);
$kendaraan = $stmt->fetch();

if (!$kendaraan) {
    set_flash('danger', 'Data kendaraan tidak ditemukan!');
    header("Location: " . base_url('pages/kendaraan/index.php'));
    exit;
}

$page_title = 'Edit Kendaraan';
$page_heading = 'Edit Data Kendaraan';
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
        // Cek duplikasi plat nomor dengan id lain
        $stmt_cek = $pdo->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ? AND id_kendaraan != ?");
        $stmt_cek->execute([$plat_nomor, $id_kendaraan]);
        if ($stmt_cek->fetch()) {
            $error = "Plat nomor {$plat_nomor} sudah digunakan kendaraan lain!";
        } else {
            try {
                $stmt_up = $pdo->prepare("UPDATE tb_kendaraan SET plat_nomor = ?, jenis_kendaraan = ?, warna = ?, pemilik = ? WHERE id_kendaraan = ?");
                $stmt_up->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_kendaraan]);

                catat_log($pdo, $id_user, "Mengubah data kendaraan ID {$id_kendaraan} ({$plat_nomor})");

                set_flash('success', "Data kendaraan plat {$plat_nomor} berhasil diperbarui!");
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Edit Data Kendaraan #<?= $id_kendaraan ?></h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nomor Plat Kendaraan <span class="text-danger">*</span></label>
                    <input type="text" name="plat_nomor" class="form-control text-uppercase fw-bold" required value="<?= e($kendaraan['plat_nomor']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Kendaraan <span class="text-danger">*</span></label>
                    <select name="jenis_kendaraan" class="form-select" required>
                        <option value="motor" <?= ($kendaraan['jenis_kendaraan'] === 'motor') ? 'selected' : '' ?>>🏍️ Sepeda Motor</option>
                        <option value="mobil" <?= ($kendaraan['jenis_kendaraan'] === 'mobil') ? 'selected' : '' ?>>🚗 Mobil</option>
                        <option value="lainnya" <?= ($kendaraan['jenis_kendaraan'] === 'lainnya') ? 'selected' : '' ?>>🚚 Truk / Lainnya</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Warna Kendaraan</label>
                    <input type="text" name="warna" class="form-control" value="<?= e($kendaraan['warna']) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Nama Pemilik / Pelanggan</label>
                    <input type="text" name="pemilik" class="form-control" value="<?= e($kendaraan['pemilik']) ?>">
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/kendaraan/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Perbarui Kendaraan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

