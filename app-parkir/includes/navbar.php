<?php
// includes/navbar.php - Top Navigation Bar
$page_heading = $page_heading ?? 'Dashboard';
$current_user = getCurrentUser();
?>
<div class="app-main">
    <nav class="app-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none border" id="sidebarToggle" type="button">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h4 class="mb-0 fw-bold fs-5 text-dark"><?= e($page_heading) ?></h4>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Digital Clock Realtime -->
            <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border">
                <i class="bi bi-clock text-primary"></i>
                <span id="liveClock" class="small fw-semibold text-secondary">--:--:-- WIB</span>
            </div>

            <!-- Quick Action Dropdown -->
            <?php if (isAdmin() || isPetugas()): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-primary rounded-pill px-3 dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-lg me-1"></i> Aksi Cepat
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li>
                        <a class="dropdown-item py-2" href="<?= base_url('pages/transaksi/masuk.php') ?>">
                            <i class="bi bi-box-arrow-in-right text-success me-2"></i> Kendaraan Masuk
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= base_url('pages/transaksi/keluar.php') ?>">
                            <i class="bi bi-box-arrow-up-right text-danger me-2"></i> Kendaraan Keluar
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= base_url('pages/kendaraan/create.php') ?>">
                            <i class="bi bi-car-front text-info me-2"></i> Registrasi Kendaraan
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- User Menu -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px; font-weight: 600;">
                        <?= strtoupper(substr($current_user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="d-none d-sm-inline fw-semibold small"><?= e($current_user['nama_lengkap'] ?? '') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= e($current_user['nama_lengkap'] ?? '') ?></div>
                        <div class="small text-muted">@<?= e($current_user['username'] ?? '') ?> &bull; <span class="badge badge-role badge-role-<?= e($current_user['role'] ?? '') ?>"><?= e($current_user['role'] ?? '') ?></span></div>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger py-2" href="<?= base_url('logout.php') ?>" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                            <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="content-container">
        <?php render_flash(); ?>

