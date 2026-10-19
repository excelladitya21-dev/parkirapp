<?php
// pages/user/index.php - Manajemen Pengguna Sistem
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole('admin');

$page_title = 'Kelola Pengguna';
$page_heading = 'Manajemen Pengguna & Hak Akses';
$current_page = 'user';

$stmt = $pdo->query("SELECT * FROM tb_user ORDER BY id_user ASC");
$users = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Daftar Pengguna Sistem</h4>
        <p class="text-muted small mb-0">Kelola akun admin, petugas operasional, dan owner</p>
    </div>
    <a href="<?= base_url('pages/user/create.php') ?>" class="btn btn-primary rounded-3 shadow-sm">
        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
    </a>
</div>

<div class="data-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role / Hak Akses</th>
                    <th>Status Akun</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $is_current = ($u['id_user'] == $_SESSION['user_id']);
                ?>
                    <tr>
                        <td class="ps-4 fw-bold">#<?= $u['id_user'] ?></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= e($u['nama_lengkap']) ?></div>
                            <?php if ($is_current): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Akun Anda</span>
                            <?php endif; ?>
                        </td>
                        <td><code>@<?= e($u['username']) ?></code></td>
                        <td>
                            <span class="badge badge-role badge-role-<?= e($u['role']) ?>">
                                <?= e($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int)$u['status_aktif'] === 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="bi bi-x-circle-fill me-1"></i> Nonaktif
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= base_url('pages/user/edit.php?id=' . $u['id_user']) ?>" class="btn btn-light border text-primary" title="Edit Pengguna">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if (!$is_current): ?>
                                    <a href="<?= base_url('pages/user/toggle_status.php?id=' . $u['id_user']) ?>" class="btn btn-light border <?= (int)$u['status_aktif'] === 1 ? 'text-warning' : 'text-success' ?>" title="<?= (int)$u['status_aktif'] === 1 ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>" onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif akun ini?')">
                                        <i class="bi <?= (int)$u['status_aktif'] === 1 ? 'bi-lock' : 'bi-unlock' ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

