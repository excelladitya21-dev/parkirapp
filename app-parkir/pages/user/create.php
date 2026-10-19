<?php
// pages/user/create.php - Tambah User Baru
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$page_title = 'Tambah Pengguna';
$page_heading = 'Tambah Pengguna Baru';
$current_page = 'user';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $role         = trim($_POST['role'] ?? 'petugas');
    $status_aktif = (int)($_POST['status_aktif'] ?? 1);

    if (empty($nama_lengkap) || empty($username) || empty($password)) {
        $error = 'Nama lengkap, username, dan password wajib diisi!';
    } elseif (!in_array($role, ['admin', 'petugas', 'owner'])) {
        $error = 'Pilihan role tidak valid!';
    } else {
        // Cek username unik
        $stmt_cek = $pdo->prepare("SELECT id_user FROM tb_user WHERE username = ?");
        $stmt_cek->execute([$username]);
        if ($stmt_cek->fetch()) {
            $error = "Username '{$username}' sudah digunakan oleh akun lain!";
        } else {
            try {
                $hash = generatePasswordHash($password);
                $stmt = $pdo->prepare("INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nama_lengkap, $username, $hash, $role, $status_aktif]);

                catat_log($pdo, (int)$_SESSION['user_id'], "Membuat akun pengguna baru: {$username} ({$role})");

                set_flash('success', "Pengguna {$nama_lengkap} (@{$username}) berhasil ditambahkan!");
                header("Location: " . base_url('pages/user/index.php'));
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Form Tambah Pengguna</h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Ahmad Rizki" required autofocus value="<?= e($_POST['nama_lengkap'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: ahmad1" required value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Peran (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="petugas" selected>Petugas Operasional</option>
                            <option value="admin">Administrator</option>
                            <option value="owner">Owner / Manajemen</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status Akun</label>
                        <select name="status_aktif" class="form-select">
                            <option value="1" selected>Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/user/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

