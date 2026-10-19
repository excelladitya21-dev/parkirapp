<?php
// pages/log/index.php - Audit Trail & Log Aktivitas Pengguna
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'owner']);

$page_title = 'Log Aktivitas';
$page_heading = 'Audit Trail & Log Aktivitas Sistem';
$current_page = 'log';

$search = trim($_GET['search'] ?? '');
$filter_user = (int)($_GET['id_user'] ?? 0);

$sql = "
    SELECT l.*, u.nama_lengkap, u.username, u.role
    FROM tb_log_aktivitas l
    JOIN tb_user u ON l.id_user = u.id_user
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (l.aktivitas LIKE ? OR u.nama_lengkap LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_user > 0) {
    $sql .= " AND l.id_user = ?";
    $params[] = $filter_user;
}

$sql .= " ORDER BY l.id_log DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// List user untuk dropdown
$user_list = $pdo->query("SELECT id_user, nama_lengkap, role FROM tb_user ORDER BY nama_lengkap ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Log Aktivitas Sistem</h4>
        <p class="text-muted small mb-0">Catatan riwayat seluruh aktivitas dan audit trail petugas/pengguna</p>
    </div>
</div>

<!-- Filter Pencarian Log -->
<div class="data-card p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari aktivitas, nama petugas, username..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="id_user" class="form-select form-select-sm">
                <option value="0">Semua Pengguna</option>
                <?php foreach ($user_list as $ul): ?>
                    <option value="<?= $ul['id_user'] ?>" <?= ($filter_user == $ul['id_user']) ? 'selected' : '' ?>>
                        <?= e($ul['nama_lengkap']) ?> (<?= e($ul['role']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="bi bi-search me-1"></i> Cari
            </button>
            <a href="<?= base_url('pages/log/index.php') ?>" class="btn btn-sm btn-light border" title="Reset">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabel Log -->
<div class="data-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4" style="width: 80px;">ID Log</th>
                    <th style="width: 220px;">Waktu Kejadian</th>
                    <th style="width: 220px;">Pengguna</th>
                    <th>Aktivitas Terlaksana</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                            Belum ada catatan aktivitas yang sesuai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#<?= $l['id_log'] ?></td>
                            <td>
                                <div class="small fw-semibold"><?= tgl_indo($l['waktu_aktivitas'], true) ?></div>
                                <div class="small text-muted" style="font-size: 0.75rem;"><?= $l['waktu_aktivitas'] ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($l['nama_lengkap']) ?></div>
                                <span class="badge badge-role badge-role-<?= e($l['role']) ?>" style="font-size: 0.65rem;">
                                    <?= e($l['role']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="p-2 bg-light rounded-2 border-start border-3 border-primary small">
                                    <?= e($l['aktivitas']) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

