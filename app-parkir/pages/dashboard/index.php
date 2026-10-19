<?php
// pages/dashboard/index.php - Halaman Utama Dashboard
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin();

$page_title = 'Dashboard Utama';
$page_heading = 'Dashboard Ringkasan Parkir';
$current_page = 'dashboard';

// Sinkronkan data terisi pada area parkir secara otomatis
sync_area_terisi($pdo);

// 1. Statistik Kendaraan Parkir Saat Ini (Masuk)
$stmt_aktif = $pdo->query("SELECT COUNT(*) AS total FROM tb_transaksi WHERE status = 'masuk'");
$total_parkir_aktif = (int)$stmt_aktif->fetch()['total'];

// 2. Statistik Kendaraan Masuk Hari Ini
$stmt_masuk_hari_ini = $pdo->query("SELECT COUNT(*) AS total FROM tb_transaksi WHERE DATE(waktu_masuk) = CURDATE()");
$total_masuk_hari_ini = (int)$stmt_masuk_hari_ini->fetch()['total'];

// 3. Statistik Kendaraan Keluar Hari Ini
$stmt_keluar_hari_ini = $pdo->query("SELECT COUNT(*) AS total FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()");
$total_keluar_hari_ini = (int)$stmt_keluar_hari_ini->fetch()['total'];

// 4. Statistik Pendapatan Hari Ini
$stmt_pendapatan_hari_ini = $pdo->query("SELECT COALESCE(SUM(biaya_total), 0) AS total FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()");
$total_pendapatan_hari_ini = (float)$stmt_pendapatan_hari_ini->fetch()['total'];

// 5. Data Area Parkir
$stmt_area = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY id_area ASC");
$areas = $stmt_area->fetchAll();

// 6. Transaksi Terbaru (10 data)
$stmt_recent = $pdo->query("
    SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, 
           a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
    JOIN tb_user u ON t.id_user = u.id_user
    ORDER BY t.id_parkir DESC
    LIMIT 10
");
$recent_transaksi = $stmt_recent->fetchAll();

// 7. Data Tren 7 Hari Terakhir untuk Grafik (Admin & Owner)
$chart_labels = [];
$chart_masuk = [];
$chart_pendapatan = [];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $tgl_label = date('d M', strtotime($tgl));
    $chart_labels[] = $tgl_label;
    
    // Total Masuk di tanggal tgl
    $st_m = $pdo->prepare("SELECT COUNT(*) as jml FROM tb_transaksi WHERE DATE(waktu_masuk) = ?");
    $st_m->execute([$tgl]);
    $chart_masuk[] = (int)$st_m->fetch()['jml'];
    
    // Total Pendapatan di tanggal tgl
    $st_p = $pdo->prepare("SELECT COALESCE(SUM(biaya_total), 0) as total FROM tb_transaksi WHERE DATE(waktu_keluar) = ? AND status = 'keluar'");
    $st_p->execute([$tgl]);
    $chart_pendapatan[] = (float)$st_p->fetch()['total'];
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<!-- Baris Sambutan & Quick Action -->
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h4 class="fw-bold mb-1">Halo, <?= e($_SESSION['nama_lengkap']) ?>! 👋</h4>
        <p class="text-muted mb-0">Berikut adalah status dan operasional area parkir terkini.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
        <?php if (isAdmin() || isPetugas()): ?>
            <a href="<?= base_url('pages/transaksi/masuk.php') ?>" class="btn btn-success rounded-3 shadow-sm px-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk (Check-In)
            </a>
            <a href="<?= base_url('pages/transaksi/keluar.php') ?>" class="btn btn-danger rounded-3 shadow-sm px-3">
                <i class="bi bi-box-arrow-up-right me-1"></i> Keluar (Check-Out)
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Kartu Statistik -->
<div class="row g-3 mb-4">
    <!-- Parkir Aktif -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Kendaraan Terparkir</span>
                <div class="stat-icon primary">
                    <i class="bi bi-p-square"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= number_format($total_parkir_aktif) ?></div>
            <div class="small text-muted mt-1">
                <span class="text-primary fw-semibold"><i class="bi bi-activity"></i> Realtime</span> di seluruh area
            </div>
        </div>
    </div>

    <!-- Kendaraan Masuk Hari Ini -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Masuk Hari Ini</span>
                <div class="stat-icon success">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-success"><?= number_format($total_masuk_hari_ini) ?></div>
            <div class="small text-muted mt-1">
                <span class="text-success fw-semibold"><i class="bi bi-calendar-check"></i> Hari ini</span> (<?= date('d M Y') ?>)
            </div>
        </div>
    </div>

    <!-- Kendaraan Keluar Hari Ini -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Keluar / Selesai</span>
                <div class="stat-icon info">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-info"><?= number_format($total_keluar_hari_ini) ?></div>
            <div class="small text-muted mt-1">
                <span class="text-info fw-semibold"><i class="bi bi-check-all"></i> Transaksi</span> selesai dibayar
            </div>
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pendapatan Hari Ini</span>
                <div class="stat-icon warning">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="fs-3 fw-bold text-warning"><?= rupiah($total_pendapatan_hari_ini) ?></div>
            <div class="small text-muted mt-1">
                <span class="text-warning-emphasis fw-semibold"><i class="bi bi-wallet2"></i> Kas</span> dari parkir keluar
            </div>
        </div>
    </div>
</div>

<!-- Monitoring Slot Area Parkir -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="data-card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>Status Ketersediaan Slot Area Parkir
                </h5>
                <a href="<?= base_url('pages/area/index.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    Kelola Area <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-3">
                <?php foreach ($areas as $area): 
                    $kapasitas = (int)$area['kapasitas'];
                    $terisi = (int)$area['terisi'];
                    $tersedia = max(0, $kapasitas - $terisi);
                    $persen = $kapasitas > 0 ? round(($terisi / $kapasitas) * 100) : 0;
                    
                    $bar_color = 'bg-success';
                    $badge_class = 'bg-success-subtle text-success border-success-subtle';
                    $status_text = 'Tersedia';
                    
                    if ($persen >= 90) {
                        $bar_color = 'bg-danger';
                        $badge_class = 'bg-danger-subtle text-danger border-danger-subtle';
                        $status_text = ($persen >= 100) ? 'Penuh' : 'Hampir Penuh';
                    } elseif ($persen >= 70) {
                        $bar_color = 'bg-warning';
                        $badge_class = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                        $status_text = 'Mulai Padat';
                    }
                ?>
                <div class="col-md-6 col-lg-6">
                    <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-6"><?= e($area['nama_area']) ?></span>
                            <span class="badge border <?= $badge_class ?> rounded-pill px-2 py-1"><?= $status_text ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span>Terisi: <strong class="text-dark"><?= $terisi ?></strong> / <?= $kapasitas ?> Slot</span>
                            <span>Sisa Kosong: <strong class="text-primary"><?= $tersedia ?></strong> Slot</span>
                        </div>
                        <div class="progress area-capacity-bar">
                            <div class="progress-bar <?= $bar_color ?>" role="progressbar" style="width: <?= min(100, $persen) ?>%" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-end mt-1 text-muted" style="font-size: 0.75rem;">
                            Tingkat Keterisian: <?= $persen ?>%
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Tren & Ringkasan (Admin & Owner) -->
<?php if (isAdmin() || isOwner()): ?>
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="data-card p-3 p-md-4">
            <h5 class="fw-bold mb-3 text-dark">
                <i class="bi bi-graph-up text-primary me-2"></i>Tren Kendaraan Masuk (7 Hari Terakhir)
            </h5>
            <canvas id="trafficChart" height="110"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="data-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-3 text-dark">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Aksi Cepat Sistem
                </h5>
                <div class="d-grid gap-2">
                    <a href="<?= base_url('pages/transaksi/masuk.php') ?>" class="btn btn-outline-success text-start py-2">
                        <i class="bi bi-box-arrow-in-right me-2 fs-5 align-middle"></i>
                        <span>Input Kendaraan Masuk</span>
                    </a>
                    <a href="<?= base_url('pages/transaksi/keluar.php') ?>" class="btn btn-outline-danger text-start py-2">
                        <i class="bi bi-box-arrow-up-right me-2 fs-5 align-middle"></i>
                        <span>Checkout / Bayar Parkir</span>
                    </a>
                    <a href="<?= base_url('pages/laporan/index.php') ?>" class="btn btn-outline-primary text-start py-2">
                        <i class="bi bi-file-earmark-bar-graph me-2 fs-5 align-middle"></i>
                        <span>Cetak Laporan Pendapatan</span>
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="<?= base_url('pages/user/index.php') ?>" class="btn btn-outline-secondary text-start py-2">
                        <i class="bi bi-people me-2 fs-5 align-middle"></i>
                        <span>Kelola Akun Petugas</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-4 p-3 bg-light rounded-3 text-muted small">
                <i class="bi bi-shield-check text-success me-1"></i> Sistem beroperasi normal. Database tersambung di <code>db_parkir</code>.
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabel Transaksi Terbaru -->
<div class="row g-3">
    <div class="col-12">
        <div class="data-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i>Transaksi Parkir Terbaru
                </h5>
                <a href="<?= base_url('pages/transaksi/index.php') ?>" class="btn btn-sm btn-light border">
                    Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Tiket</th>
                            <th>Plat Nomor</th>
                            <th>Jenis / Kendaraan</th>
                            <th>Area</th>
                            <th>Waktu Masuk</th>
                            <th>Waktu Keluar</th>
                            <th>Total Biaya</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transaksi)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Belum ada transaksi parkir tercatat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transaksi as $trx): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary">#<?= str_pad($trx['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <span class="plat-badge"><?= e($trx['plat_nomor']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-capitalize"><?= e($trx['jenis_kendaraan']) ?></div>
                                        <div class="small text-muted"><?= e($trx['warna'] ?? '-') ?> &bull; <?= e($trx['pemilik'] ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= e($trx['nama_area']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= tgl_indo($trx['waktu_masuk'], true) ?></div>
                                        <div class="small text-muted">Petugas: <?= e($trx['nama_petugas']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($trx['status'] === 'keluar' && $trx['waktu_keluar']): ?>
                                            <div class="small fw-semibold"><?= tgl_indo($trx['waktu_keluar'], true) ?></div>
                                            <div class="small text-muted"><?= $trx['durasi_jam'] ?> jam</div>
                                        <?php else: 
                                            $dur = hitung_durasi_jam($trx['waktu_masuk']);
                                        ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">
                                                <i class="bi bi-hourglass-split me-1"></i><?= $dur['display'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($trx['status'] === 'keluar'): ?>
                                            <strong class="text-success"><?= rupiah($trx['biaya_total']) ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted small">Tarif: <?= rupiah($trx['tarif_per_jam']) ?>/jam</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($trx['status'] === 'masuk'): ?>
                                            <span class="badge-status-masuk">
                                                <i class="bi bi-p-circle-fill me-1"></i> Terparkir
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status-keluar">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i> Selesai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($trx['status'] === 'masuk'): ?>
                                                <a href="<?= base_url('pages/transaksi/keluar.php?id=' . $trx['id_parkir']) ?>" class="btn btn-danger" title="Proses Keluar & Bayar">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Checkout
                                                </a>
                                                <a href="<?= base_url('pages/transaksi/karcis.php?id=' . $trx['id_parkir']) ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Karcis Masuk">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('pages/transaksi/struk.php?id=' . $trx['id_parkir']) ?>" target="_blank" class="btn btn-outline-success" title="Cetak Struk Pembayaran">
                                                    <i class="bi bi-receipt me-1"></i> Struk
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin() || isOwner()): ?>
<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('trafficChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Kendaraan Masuk',
                data: <?= json_encode($chart_masuk) ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

