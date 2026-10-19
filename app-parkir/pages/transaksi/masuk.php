<?php
// pages/transaksi/masuk.php - Check-in Kendaraan Masuk
define('APP_INIT', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helper.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin', 'petugas']);

$page_title = 'Kendaraan Masuk';
$page_heading = 'Check-In Kendaraan Masuk';
$current_page = 'transaksi_masuk';

// Ambil data tarif
$stmt_tarif = $pdo->query("SELECT * FROM tb_tarif ORDER BY id_tarif ASC");
$tarifs = $stmt_tarif->fetchAll();

// Ambil data area parkir
$stmt_area = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY id_area ASC");
$areas = $stmt_area->fetchAll();

$error = '';
$success = '';
$new_ticket_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? 'motor');
    $warna           = trim($_POST['warna'] ?? '');
    $pemilik         = trim($_POST['pemilik'] ?? '');
    $id_area         = (int)($_POST['id_area'] ?? 0);
    $id_tarif        = (int)($_POST['id_tarif'] ?? 0);
    $id_user         = (int)$_SESSION['user_id'];

    if (empty($plat_nomor)) {
        $error = 'Plat nomor kendaraan wajib diisi!';
    } elseif ($id_area <= 0) {
        $error = 'Silakan pilih area parkir!';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Cek apakah kendaraan ini sedang parkir aktif
            $stmt_cek = $pdo->prepare("
                SELECT t.id_parkir, a.nama_area, t.waktu_masuk 
                FROM tb_transaksi t
                JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                JOIN tb_area_parkir a ON t.id_area = a.id_area
                WHERE k.plat_nomor = ? AND t.status = 'masuk'
                LIMIT 1
            ");
            $stmt_cek->execute([$plat_nomor]);
            $parkir_aktif = $stmt_cek->fetch();

            if ($parkir_aktif) {
                $pdo->rollBack();
                $error = "Kendaraan dengan plat {$plat_nomor} sudah berstatus terparkir di {$parkir_aktif['nama_area']} sejak " . tgl_indo($parkir_aktif['waktu_masuk'], true) . "! (Tiket #{$parkir_aktif['id_parkir']})";
            } else {
                // 2. Cek kapasitas area parkir
                $stmt_area_cek = $pdo->prepare("SELECT nama_area, kapasitas, terisi FROM tb_area_parkir WHERE id_area = ? FOR UPDATE");
                $stmt_area_cek->execute([$id_area]);
                $area_info = $stmt_area_cek->fetch();

                if (!$area_info) {
                    $pdo->rollBack();
                    $error = 'Area parkir tidak ditemukan.';
                } elseif ($area_info['terisi'] >= $area_info['kapasitas']) {
                    $pdo->rollBack();
                    $error = "Area {$area_info['nama_area']} sudah penuh! Kapasitas ({$area_info['kapasitas']} slot) telah terisi semua.";
                } else {
                    // 3. Tentukan id_tarif otomatis jika tidak dipilih manual
                    if ($id_tarif <= 0) {
                        $stmt_tarif_auto = $pdo->prepare("SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = ? LIMIT 1");
                        $stmt_tarif_auto->execute([$jenis_kendaraan]);
                        $tarif_found = $stmt_tarif_auto->fetch();
                        if ($tarif_found) {
                            $id_tarif = (int)$tarif_found['id_tarif'];
                        } else {
                            // Default ke tarif pertama
                            $id_tarif = (int)($tarifs[0]['id_tarif'] ?? 1);
                        }
                    }

                    // 4. Cek atau daftarkan kendaraan di tb_kendaraan
                    $stmt_kend = $pdo->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ? LIMIT 1");
                    $stmt_kend->execute([$plat_nomor]);
                    $kend = $stmt_kend->fetch();

                    if ($kend) {
                        $id_kendaraan = (int)$kend['id_kendaraan'];
                        // Update info jenis/warna jika diisi
                        $stmt_up_kend = $pdo->prepare("UPDATE tb_kendaraan SET jenis_kendaraan = ?, warna = ?, pemilik = ? WHERE id_kendaraan = ?");
                        $stmt_up_kend->execute([$jenis_kendaraan, $warna, $pemilik, $id_kendaraan]);
                    } else {
                        $stmt_ins_kend = $pdo->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)");
                        $stmt_ins_kend->execute([$plat_nomor, $jenis_kendaraan, $warna, $pemilik, $id_user]);
                        $id_kendaraan = (int)$pdo->lastInsertId();
                    }

                    // 5. Insert ke tb_transaksi
                    $stmt_trx = $pdo->prepare("
                        INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) 
                        VALUES (?, NOW(), ?, 'masuk', ?, ?)
                    ");
                    $stmt_trx->execute([$id_kendaraan, $id_tarif, $id_user, $id_area]);
                    $new_ticket_id = (int)$pdo->lastInsertId();

                    // 6. Update jumlah terisi di tb_area_parkir
                    $stmt_up_area = $pdo->prepare("UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = ?");
                    $stmt_up_area->execute([$id_area]);

                    // 7. Catat ke tb_log_aktivitas
                    catat_log($pdo, $id_user, "Kendaraan {$plat_nomor} ({$jenis_kendaraan}) masuk di {$area_info['nama_area']}");

                    $pdo->commit();
                    $success = "Kendaraan berhasil masuk! Nomor Tiket: #" . str_pad($new_ticket_id, 5, '0', STR_PAD_LEFT);
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

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= e($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success) && $new_ticket_id): ?>
            <div class="alert alert-success alert-dismissible fade show p-4 mb-4 rounded-3 shadow-sm" role="alert">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="alert-heading fw-bold mb-1"><i class="bi bi-check-circle-fill me-2"></i>Check-In Berhasil!</h5>
                        <p class="mb-0"><?= e($success) ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('pages/transaksi/karcis.php?id=' . $new_ticket_id) ?>" target="_blank" class="btn btn-dark shadow-sm">
                            <i class="bi bi-printer me-1"></i> Cetak Karcis
                        </a>
                        <a href="<?= base_url('pages/transaksi/masuk.php') ?>" class="btn btn-outline-success">
                            <i class="bi bi-plus-lg me-1"></i> Input Baru
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="data-card p-4">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Form Registrasi Kendaraan Masuk</h5>
                        <small class="text-muted">Masukkan data plat nomor dan area parkir untuk menerbitkan karcis</small>
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                    <i class="bi bi-clock me-1"></i> <?= date('d M Y - H:i') ?>
                </span>
            </div>

            <form method="POST" action="" id="formCheckIn">
                <!-- Plat Nomor -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Nomor Plat Kendaraan <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-dark text-white fw-bold">
                            <i class="bi bi-credit-card-2-front"></i>
                        </span>
                        <input type="text" name="plat_nomor" id="platNomor" class="form-control text-uppercase fw-bold" placeholder="CONTOH: B 1234 ABC" required autofocus autocomplete="off" style="letter-spacing: 2px;">
                        <button type="button" class="btn btn-outline-secondary" id="btnCekPlat" title="Cek apakah plat sudah terdaftar">
                            <i class="bi bi-search me-1"></i> Cek Data
                        </button>
                    </div>
                    <div id="platNotice" class="form-text mt-1"></div>
                </div>

                <!-- Jenis Kendaraan & Tarif -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Jenis Kendaraan <span class="text-danger">*</span></label>
                        <select name="jenis_kendaraan" id="jenisKendaraan" class="form-select" required>
                            <option value="motor">🏍️ Sepeda Motor</option>
                            <option value="mobil" selected>🚗 Mobil Pribadi / Minibus</option>
                            <option value="lainnya">🚚 Truk / Bus / Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Tarif Parkir</label>
                        <select name="id_tarif" id="idTarif" class="form-select">
                            <?php foreach ($tarifs as $tf): ?>
                                <option value="<?= $tf['id_tarif'] ?>" data-jenis="<?= $tf['jenis_kendaraan'] ?>">
                                    <?= ucfirst($tf['jenis_kendaraan']) ?> - <?= rupiah($tf['tarif_per_jam']) ?> / jam
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Warna & Pemilik (Opsional) -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Warna Kendaraan <span class="text-muted small">(Opsional)</span></label>
                        <input type="text" name="warna" id="warnaKendaraan" class="form-control" placeholder="Contoh: Hitam, Putih, Merah">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Pemilik / Pengemudi <span class="text-muted small">(Opsional)</span></label>
                        <input type="text" name="pemilik" id="pemilikKendaraan" class="form-control" placeholder="Contoh: Budi">
                    </div>
                </div>

                <!-- Pilihan Area Parkir -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Area Parkir Tujuan <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <?php foreach ($areas as $idx => $ar): 
                            $kap = (int)$ar['kapasitas'];
                            $isi = (int)$ar['terisi'];
                            $sisa = max(0, $kap - $isi);
                            $is_full = ($sisa <= 0);
                        ?>
                        <div class="col-md-6">
                            <label class="card h-100 p-3 border <?= $is_full ? 'bg-light opacity-50' : 'bg-white' ?>" style="cursor: <?= $is_full ? 'not-allowed' : 'pointer' ?>;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="id_area" value="<?= $ar['id_area'] ?>" <?= ($idx === 0 && !$is_full) ? 'checked' : '' ?> <?= $is_full ? 'disabled' : '' ?> required>
                                    <div class="form-check-label ms-1">
                                        <div class="fw-bold text-dark"><?= e($ar['nama_area']) ?></div>
                                        <div class="small <?= $is_full ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?php if ($is_full): ?>
                                                <i class="bi bi-x-circle-fill"></i> PENUH (<?= $isi ?>/<?= $kap ?>)
                                            <?php else: ?>
                                                <i class="bi bi-check-circle-fill text-success"></i> Sisa: <strong class="text-primary"><?= $sisa ?></strong> slot kosong
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="<?= base_url('pages/dashboard/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal / Kembali
                    </a>
                    <button type="submit" class="btn btn-success px-4 py-2 fw-semibold shadow-sm" id="btnSubmit">
                        <i class="bi bi-printer me-1"></i> Simpan & Terbitkan Karcis
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Sinkronkan tarif otomatis berdasarkan jenis kendaraan
const jenisKendaraanSelect = document.getElementById('jenisKendaraan');
const idTarifSelect = document.getElementById('idTarif');

jenisKendaraanSelect.addEventListener('change', function() {
    const jenis = this.value;
    for (let opt of idTarifSelect.options) {
        if (opt.getAttribute('data-jenis') === jenis) {
            opt.selected = true;
            break;
        }
    }
});

// Otomatis cek data kendaraan ketika plat nomor diinput
const platInput = document.getElementById('platNomor');
const platNotice = document.getElementById('platNotice');

function checkPlat() {
    const val = platInput.value.trim().toUpperCase();
    if (val.length < 3) return;

    fetch(`<?= base_url('api/get_kendaraan.php') ?>?plat=${encodeURIComponent(val)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.is_parkir_aktif) {
                    platNotice.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Peringatan: Kendaraan ini sedang aktif parkir sejak ${data.parkir_info.waktu_masuk}!</span>`;
                    document.getElementById('btnSubmit').disabled = true;
                } else {
                    platNotice.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill"></i> Kendaraan terdaftar atas nama: <strong>${data.data.pemilik || '-'}</strong></span>`;
                    document.getElementById('btnSubmit').disabled = false;
                }
                
                // Isi field otomatis
                if (data.data.jenis_kendaraan) {
                    jenisKendaraanSelect.value = data.data.jenis_kendaraan;
                    jenisKendaraanSelect.dispatchEvent(new Event('change'));
                }
                if (data.data.warna) {
                    document.getElementById('warnaKendaraan').value = data.data.warna;
                }
                if (data.data.pemilik) {
                    document.getElementById('pemilikKendaraan').value = data.data.pemilik;
                }
            } else {
                platNotice.innerHTML = `<span class="text-muted"><i class="bi bi-info-circle"></i> Kendaraan baru (belum pernah terdaftar di sistem).</span>`;
                document.getElementById('btnSubmit').disabled = false;
            }
        })
        .catch(err => console.error(err));
}

platInput.addEventListener('blur', checkPlat);
document.getElementById('btnCekPlat').addEventListener('click', checkPlat);
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

