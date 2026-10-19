<?php
// pages/transaksi/keluar.php - Check-Out & Pembayaran Parkir
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas']);

$page_title = 'Kendaraan Keluar';
$page_heading = 'Check-Out & Pembayaran Parkir';
$current_page = 'transaksi_keluar';

$error = '';
$success = '';
$selected_trx = null;
$durasi = null;
$total_biaya = 0;

// Parameter pencarian dari GET (misal dari dashboard atau list)
$search_query = trim($_GET['id'] ?? ($_GET['q'] ?? ''));

// Function to fetch active transaction
function cariTransaksiAktif($pdo, $q) {
    if (empty($q)) return null;
    $stmt = $pdo->prepare("
        SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
               a.nama_area, tf.jenis_kendaraan AS tarif_jenis, tf.tarif_per_jam,
               u.nama_lengkap AS petugas_masuk
        FROM tb_transaksi t
        JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
        JOIN tb_area_parkir a ON t.id_area = a.id_area
        JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
        JOIN tb_user u ON t.id_user = u.id_user
        WHERE (t.id_parkir = ? OR k.plat_nomor = ?) AND t.status = 'masuk'
        LIMIT 1
    ");
    $stmt->execute([$q, $q]);
    return $stmt->fetch();
}

if (!empty($search_query)) {
    $selected_trx = cariTransaksiAktif($pdo, $search_query);
    if (!$selected_trx) {
        $error = "Data tiket/kendaraan '{$search_query}' tidak ditemukan atau sudah selesai keluar!";
    } else {
        $durasi = hitung_durasi_jam($selected_trx['waktu_masuk']);
        $total_biaya = $durasi['jam_hitung'] * (float)$selected_trx['tarif_per_jam'];
    }
}

// Proses Pembayaran (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_parkir   = (int)($_POST['id_parkir'] ?? 0);
    $uang_bayar  = (float)($_POST['uang_bayar'] ?? 0);
    $id_user_kasir = (int)$_SESSION['user_id'];

    if ($id_parkir <= 0) {
        $error = 'ID Parkir tidak valid!';
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci record transaksi
            $stmt_lock = $pdo->prepare("
                SELECT t.*, k.plat_nomor, tf.tarif_per_jam, a.id_area, a.nama_area
                FROM tb_transaksi t
                JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
                JOIN tb_area_parkir a ON t.id_area = a.id_area
                WHERE t.id_parkir = ? FOR UPDATE
            ");
            $stmt_lock->execute([$id_parkir]);
            $trx_to_close = $stmt_lock->fetch();

            if (!$trx_to_close) {
                $pdo->rollBack();
                $error = 'Transaksi tidak ditemukan.';
            } elseif ($trx_to_close['status'] === 'keluar') {
                $pdo->rollBack();
                $error = 'Transaksi ini sudah selesai sebelumnya!';
            } else {
                $waktu_keluar = date('Y-m-d H:i:s');
                $dur = hitung_durasi_jam($trx_to_close['waktu_masuk'], $waktu_keluar);
                $final_biaya = $dur['jam_hitung'] * (float)$trx_to_close['tarif_per_jam'];

                if ($uang_bayar < $final_biaya) {
                    $pdo->rollBack();
                    $error = "Uang pembayaran kurang! Total biaya adalah " . rupiah($final_biaya) . ", uang diterima " . rupiah($uang_bayar);
                    // Reload selected trx
                    $selected_trx = cariTransaksiAktif($pdo, $id_parkir);
                    $durasi = $dur;
                    $total_biaya = $final_biaya;
                } else {
                    $kembalian = $uang_bayar - $final_biaya;

                    // Update tb_transaksi
                    $stmt_update = $pdo->prepare("
                        UPDATE tb_transaksi 
                        SET waktu_keluar = ?, durasi_jam = ?, biaya_total = ?, status = 'keluar'
                        WHERE id_parkir = ?
                    ");
                    $stmt_update->execute([$waktu_keluar, $dur['jam_hitung'], $final_biaya, $id_parkir]);

                    // Kurangi tb_area_parkir terisi
                    $stmt_up_area = $pdo->prepare("UPDATE tb_area_parkir SET terisi = GREATEST(0, terisi - 1) WHERE id_area = ?");
                    $stmt_up_area->execute([$trx_to_close['id_area']]);

                    // Catat ke log
                    catat_log($pdo, $id_user_kasir, "Checkout {$trx_to_close['plat_nomor']} durasi {$dur['jam_hitung']} jam, bayar " . rupiah($final_biaya));

                    $pdo->commit();

                    // Simpan data pembayaran ke session untuk cetak struk
                    $_SESSION['last_checkout'] = [
                        'id_parkir' => $id_parkir,
                        'uang_bayar' => $uang_bayar,
                        'kembalian' => $kembalian
                    ];

                    header("Location: " . base_url("pages/transaksi/struk.php?id={$id_parkir}&checkout=success"));
                    exit;
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

// Ambil daftar kendaraan yang saat ini masih parkir untuk referensi cepat
$stmt_masih_parkir = $pdo->query("
    SELECT t.id_parkir, k.plat_nomor, k.jenis_kendaraan, a.nama_area, t.waktu_masuk
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    JOIN tb_area_parkir a ON t.id_area = a.id_area
    WHERE t.status = 'masuk'
    ORDER BY t.id_parkir DESC
    LIMIT 6
");
$parkir_list = $stmt_masih_parkir->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= e($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form Cari Tiket / Plat -->
        <div class="data-card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-search text-primary me-2"></i>Cari Kendaraan Keluar</h5>
            <form method="GET" action="" class="row g-2">
                <div class="col-sm-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" name="q" id="searchField" class="form-control" placeholder="Scan Barcode / Ketik Plat Nomor atau ID Tiket..." value="<?= e($search_query) ?>" required autofocus>
                    </div>
                </div>
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selected_trx): ?>
            <!-- Rincian Check-Out & Form Bayar -->
            <div class="data-card p-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <span class="badge bg-primary fs-6 px-3 py-2">Tiket #<?= str_pad($selected_trx['id_parkir'], 5, '0', STR_PAD_LEFT) ?></span>
                        <span class="plat-badge ms-2"><?= e($selected_trx['plat_nomor']) ?></span>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2">
                        <i class="bi bi-hourglass-split me-1"></i> Status: Terparkir
                    </span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Jenis / Area</div>
                            <div class="fw-bold fs-6 text-capitalize"><?= e($selected_trx['jenis_kendaraan']) ?> &bull; <?= e($selected_trx['nama_area']) ?></div>
                            <div class="small text-muted mt-1">Pemilik: <?= e($selected_trx['pemilik'] ?: '-') ?> (<?= e($selected_trx['warna'] ?: '-') ?>)</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Tarif Per Jam</div>
                            <div class="fw-bold fs-6 text-primary"><?= rupiah($selected_trx['tarif_per_jam']) ?> / jam</div>
                            <div class="small text-muted mt-1">Petugas Masuk: <?= e($selected_trx['petugas_masuk']) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Waktu Masuk</div>
                            <div class="fw-semibold text-dark"><?= tgl_indo($selected_trx['waktu_masuk'], true) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Waktu Keluar (Sekarang)</div>
                            <div class="fw-semibold text-danger"><?= tgl_indo(date('Y-m-d H:i:s'), true) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Perhitungan Durasi & Biaya -->
                <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary-subtle mb-4">
                    <div class="row align-items-center text-center">
                        <div class="col-6 border-end border-primary-subtle">
                            <div class="text-muted small">Durasi Dihitung</div>
                            <div class="fs-4 fw-bold text-dark"><?= $durasi['jam_hitung'] ?> Jam</div>
                            <div class="small text-secondary">(Waktu riil: <?= $durasi['display'] ?>)</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Total Biaya Parkir</div>
                            <div class="fs-3 fw-bold text-success"><?= rupiah($total_biaya) ?></div>
                            <div class="small text-muted"><?= $durasi['jam_hitung'] ?> jam &times; <?= rupiah($selected_trx['tarif_per_jam']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Form Pembayaran Kasir -->
                <form method="POST" action="" id="formPembayaran">
                    <input type="hidden" name="id_parkir" value="<?= $selected_trx['id_parkir'] ?>">
                    <input type="hidden" id="biayaTotal" value="<?= $total_biaya ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nominal Uang Pembayaran (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold">Rp</span>
                            <input type="number" name="uang_bayar" id="uangBayar" class="form-control fw-bold fs-4 text-primary" placeholder="0" min="<?= $total_biaya ?>" required autofocus>
                        </div>
                        <!-- Tombol Cepat Nominal -->
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setNominal(<?= $total_biaya ?>)">Uang Pas (<?= rupiah($total_biaya) ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setNominal(5000)">Rp 5.000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setNominal(10000)">Rp 10.000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setNominal(20000)">Rp 20.000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setNominal(50000)">Rp 50.000</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setNominal(100000)">Rp 100.000</button>
                        </div>
                    </div>

                    <div class="mb-4 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary">Uang Kembalian:</span>
                        <span class="fs-4 fw-bold text-dark" id="uangKembalian">Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('pages/transaksi/keluar.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg px-4 fw-semibold shadow-sm" id="btnProsesBayar">
                            <i class="bi bi-check-circle-fill me-1"></i> Bayar & Cetak Struk
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kolom Kanan: Kendaraan Sedang Parkir -->
    <div class="col-lg-5">
        <div class="data-card p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-p-square-fill text-primary me-2"></i>Kendaraan Terparkir Saat Ini</h5>
            <p class="text-muted small">Klik pada kendaraan untuk langsung memproses checkout & pembayaran.</p>

            <?php if (empty($parkir_list)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                    Tidak ada kendaraan yang sedang parkir saat ini.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($parkir_list as $pl): 
                        $dur_item = hitung_durasi_jam($pl['waktu_masuk']);
                    ?>
                        <a href="<?= base_url('pages/transaksi/keluar.php?id=' . $pl['id_parkir']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-2 border-bottom">
                            <div>
                                <span class="plat-badge mb-1"><?= e($pl['plat_nomor']) ?></span>
                                <div class="small text-muted mt-1">
                                    <span class="text-capitalize"><?= e($pl['jenis_kendaraan']) ?></span> &bull; <?= e($pl['nama_area']) ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning-subtle text-warning-emphasis">
                                    <i class="bi bi-clock me-1"></i><?= $dur_item['display'] ?>
                                </span>
                                <div class="text-primary small fw-semibold mt-1">
                                    Pilih <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const uangBayarInput = document.getElementById('uangBayar');
const uangKembalianText = document.getElementById('uangKembalian');
const biayaTotal = parseFloat(document.getElementById('biayaTotal')?.value || 0);
const btnProsesBayar = document.getElementById('btnProsesBayar');

function hitungKembalian() {
    if (!uangBayarInput) return;
    const bayar = parseFloat(uangBayarInput.value || 0);
    const kembalian = bayar - biayaTotal;

    if (kembalian >= 0) {
        uangKembalianText.innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
        uangKembalianText.className = 'fs-4 fw-bold text-success';
        btnProsesBayar.disabled = false;
    } else {
        uangKembalianText.innerText = 'Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
        uangKembalianText.className = 'fs-4 fw-bold text-danger';
        btnProsesBayar.disabled = true;
    }
}

if (uangBayarInput) {
    uangBayarInput.addEventListener('input', hitungKembalian);
}

function setNominal(nominal) {
    if (uangBayarInput) {
        uangBayarInput.value = nominal;
        hitungKembalian();
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

