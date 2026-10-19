<?php
// includes/sidebar.php - Navigation Sidebar
$current_page = $current_page ?? '';
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['nama_lengkap'] ?? 'Pengguna';
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Header Sidebar -->
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <a href="<?= base_url('pages/dashboard/index.php') ?>" class="sidebar-brand">
            <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-2 shadow-sm">
                <i class="bi bi-p-square-fill fs-4"></i>
            </span>
            <div class="d-flex flex-column">
                <span class="fw-bold fs-5 text-white">ParkirApp</span>
                <span class="text-xs text-muted" style="font-size: 0.7rem; color: #94a3b8 !important;">Sistem Parkir Terpadu</span>
            </div>
        </a>
    </div>

    <!-- Menu Sidebar -->
    <div class="sidebar-menu">
        <div class="sidebar-heading">Menu Utama</div>
        <a href="<?= base_url('pages/dashboard/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <?php if ($user_role === 'admin' || $user_role === 'petugas'): ?>
            <div class="sidebar-heading mt-3">Operasional Parkir</div>
            <a href="<?= base_url('pages/transaksi/masuk.php') ?>" class="sidebar-nav-link <?= ($current_page === 'transaksi_masuk') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-in-right text-success"></i>
                <span>Kendaraan Masuk</span>
            </a>
            <a href="<?= base_url('pages/transaksi/keluar.php') ?>" class="sidebar-nav-link <?= ($current_page === 'transaksi_keluar') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-up-right text-danger"></i>
                <span>Kendaraan Keluar</span>
            </a>
            <a href="<?= base_url('pages/transaksi/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'transaksi') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Daftar Transaksi</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-heading mt-3">Master Data</div>
        <a href="<?= base_url('pages/kendaraan/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'kendaraan') ? 'active' : '' ?>">
            <i class="bi bi-car-front-fill"></i>
            <span>Data Kendaraan</span>
        </a>
        <a href="<?= base_url('pages/area/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'area') ? 'active' : '' ?>">
            <i class="bi bi-geo-alt-fill"></i>
            <span>Area Parkir</span>
        </a>

        <?php if ($user_role === 'admin'): ?>
            <a href="<?= base_url('pages/tarif/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'tarif') ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Tarif Parkir</span>
            </a>
            <a href="<?= base_url('pages/user/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'user') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Kelola Pengguna</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-heading mt-3">Laporan & Audit</div>
        <?php if ($user_role === 'admin' || $user_role === 'owner'): ?>
            <a href="<?= base_url('pages/laporan/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'laporan') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Laporan Pendapatan</span>
            </a>
            <a href="<?= base_url('pages/log/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'log') ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Log Aktivitas</span>
            </a>
        <?php elseif ($user_role === 'petugas'): ?>
            <a href="<?= base_url('pages/laporan/index.php') ?>" class="sidebar-nav-link <?= ($current_page === 'laporan') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Rekap Shift Saya</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- User Profile Footer in Sidebar -->
    <div class="sidebar-user d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600;">
                <?= strtoupper(substr($user_name, 0, 1)) ?>
            </div>
            <div class="text-truncate">
                <div class="fw-semibold text-white small text-truncate"><?= e($user_name) ?></div>
                <div>
                    <span class="badge badge-role badge-role-<?= e($user_role) ?>"><?= e($user_role) ?></span>
                </div>
            </div>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="btn btn-sm btn-outline-danger border-0 p-1 text-light" title="Logout" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
            <i class="bi bi-box-arrow-right fs-5"></i>
        </a>
    </div>
</aside>

