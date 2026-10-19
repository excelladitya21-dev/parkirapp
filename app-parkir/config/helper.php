<?php
// config/helper.php - Fungsi-fungsi pembantu (Helper Functions)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mendapatkan base URL proyek
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Deteksi sub-direktori aplikasi jika ada
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Potong jika berada di dalam subfolder seperti /pages/transaksi dll.
    $parts = explode('/', trim($script_dir, '/'));
    $base_dir = '';
    if (!empty($parts[0])) {
        $base_dir = '/' . $parts[0];
    }
    
    $url = $protocol . $host . $base_dir;
    return rtrim($url, '/') . '/' . ltrim($path, '/');
}

/**
 * Format angka ke format mata uang Rupiah
 */
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia
 */
function tgl_indo($datetime, $with_time = true) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($datetime);
    $hari = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    
    $str = $hari . ' ' . $bln . ' ' . $thn;
    if ($with_time) {
        $str .= ' ' . date('H:i', $timestamp) . ' WIB';
    }
    return $str;
}

/**
 * Hitung durasi jam dan display text durasi parkir
 * Durasi dihitung pembulatan ke atas (ceil), minimal 1 jam
 */
function hitung_durasi_jam($waktu_masuk, $waktu_keluar = null) {
    $masuk = strtotime($waktu_masuk);
    $keluar = $waktu_keluar ? strtotime($waktu_keluar) : time();
    
    $selisih_detik = max(0, $keluar - $masuk);
    
    // Pembulatan ke atas jam (1 detik - 3600 detik = 1 jam)
    $jam_dihitung = ceil($selisih_detik / 3600);
    if ($jam_dihitung < 1) {
        $jam_dihitung = 1;
    }
    
    $jam_asli = floor($selisih_detik / 3600);
    $menit_sisa = floor(($selisih_detik % 3600) / 60);
    $detik_sisa = $selisih_detik % 60;
    
    $display = '';
    if ($jam_asli > 0) {
        $display .= $jam_asli . ' jam ';
    }
    $display .= $menit_sisa . ' menit';
    if ($jam_asli == 0 && $menit_sisa == 0) {
        $display = $detik_sisa . ' detik';
    }
    
    return [
        'jam_hitung' => (int)$jam_dihitung,
        'selisih_detik' => $selisih_detik,
        'display' => trim($display)
    ];
}

/**
 * Catat aktivitas ke tb_log_aktivitas
 */
function catat_log($pdo, $id_user, $aktivitas) {
    try {
        $stmt = $pdo->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES (?, ?, NOW())");
        $stmt->execute([(int)$id_user, substr($aktivitas, 0, 100)]);
        return true;
    } catch (PDOException $e) {
        error_log("Error catat log: " . $e->getMessage());
        return false;
    }
}

/**
 * Sinkronisasi jumlah terisi pada area parkir
 */
function sync_area_terisi($pdo, $id_area = null) {
    try {
        if ($id_area) {
            $stmt = $pdo->prepare("
                UPDATE tb_area_parkir 
                SET terisi = (SELECT COUNT(*) FROM tb_transaksi WHERE tb_transaksi.id_area = ? AND tb_transaksi.status = 'masuk')
                WHERE id_area = ?
            ");
            $stmt->execute([$id_area, $id_area]);
        } else {
            $pdo->query("
                UPDATE tb_area_parkir ap
                SET ap.terisi = (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = ap.id_area AND t.status = 'masuk')
            ");
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error sync area: " . $e->getMessage());
        return false;
    }
}

/**
 * Flash Message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $msg = htmlspecialchars($flash['message']);
        $icon = 'info-circle';
        if ($type === 'success') $icon = 'check-circle-fill';
        if ($type === 'danger') $icon = 'exclamation-triangle-fill';
        if ($type === 'warning') $icon = 'exclamation-circle-fill';
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm' role='alert'>
                <i class='bi bi-{$icon} me-2 fs-5'></i>
                <div>{$msg}</div>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

/**
 * Sanitasi output HTML
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

