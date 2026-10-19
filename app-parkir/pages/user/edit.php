<?php
// pages/user/edit.php - Edit Data Pengguna
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$id_user = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'Pengguna tidak ditemukan!');
    header("Location: " . base_url('pages/user/index.php'));
    exit;
}

$page_title = 'Edit Pengguna';
$page_heading = 'Edit Pengguna #' . $id_user;
$current_page = 'user';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $role         = trim($_POST['role'] ?? 'petugas');
    $status_aktif = (int)($_POST['status_aktif'] ?? 1);

    if (empty($nama_lengkap) || empty($username)) {
        $error = 'Nama lengkap dan username wajib diisi!';
    } elseif (!in_array($role, ['admin', 'petugas', 'owner'])) {
        $error = 'Role tidak valid!';
    } else {
        // Cek duplikasi username
        $stmt_cek = $pdo->prepare("SELECT id_user FROM tb_user WHERE username = ? AND id_user != ?");
        $stmt_cek->execute([$username, $id_user]);
        if ($stmt_cek->fetch()) {
            $error = "Username '{$username}' sudah digunakan akun lain!";
        } else {
            try {
                if (!empty($password)) {
                    // Update dengan password baru
                    $hash = generatePasswordHash($password);
                    $stmt_up = $pdo->prepare("UPDATE tb_user SET nama_lengkap = ?, username = ?, password = ?, role = ?, status_aktif = ? WHERE id_user = ?");
                    $stmt_up->execute([$nama_lengkap, $username, $hash, $role, $status_aktif, $id_user]);
                } else {
                    // Update tanpa ubah password
                    $stmt_up = $pdo->prepare("UPDATE tb_user SET nama_lengkap = ?, username = ?, role = ?, status_aktif = ? WHERE id_user = ?");
                    $stmt_up->execute([$nama_lengkap, $username, $role, $status_aktif, $id_user]);
                }

                catat_log($pdo, (int)$_SESSION['user_id'], "Mengubah data pengguna ID {$id_user} (@{$username})");

                set_flash('success', "Data pengguna @{$username} berhasil diperbarui!");
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Edit Data Pengguna #<?= $id_user ?></h5>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" required value="<?= e($user['nama_lengkap']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required value="<?= e($user['username']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Password Baru <span class="text-muted small fw-normal">(Kosongkan jika tidak ingin mengubah)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Isi untuk mereset password">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Peran (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                            <option value="petugas" <?= ($user['role'] === 'petugas') ? 'selected' : '' ?>>Petugas Operasional</option>
                            <option value="owner" <?= ($user['role'] === 'owner') ? 'selected' : '' ?>>Owner / Manajemen</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status Akun</label>
                        <select name="status_aktif" class="form-select">
                            <option value="1" <?= ((int)$user['status_aktif'] === 1) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ((int)$user['status_aktif'] === 0) ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <a href="<?= base_url('pages/user/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Perbarui Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

