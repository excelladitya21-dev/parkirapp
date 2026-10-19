<?php
// config/auth.php - Autentikasi & Otorisasi Pengguna

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helper.php';

// Sinkronkan data session dengan data terkini di database jika sedang login
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt_sync_user = $pdo->prepare("SELECT nama_lengkap, username, role, status_aktif FROM tb_user WHERE id_user = ? LIMIT 1");
        $stmt_sync_user->execute([$_SESSION['user_id']]);
        if ($usr = $stmt_sync_user->fetch()) {
            if ((int)$usr['status_aktif'] !== 1) {
                $_SESSION = [];
                session_destroy();
                session_start();
                set_flash('danger', 'Akun Anda telah dinonaktifkan.');
                header("Location: " . base_url('login.php'));
                exit;
            }
            $_SESSION['nama_lengkap'] = $usr['nama_lengkap'];
            $_SESSION['username']     = $usr['username'];
            $_SESSION['role']         = $usr['role'];
        }
    } catch (Exception $e) {
        // Abaikan jika terjadi error sesaat
    }
}

/**
 * Cek apakah user sedang login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Dapatkan user yang sedang login
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id_user'      => $_SESSION['user_id'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'username'     => $_SESSION['username'],
        'role'         => $_SESSION['role'],
    ];
}

/**
 * Cek peran (role) pengguna
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isPetugas() {
    return isLoggedIn() && $_SESSION['role'] === 'petugas';
}

function isOwner() {
    return isLoggedIn() && $_SESSION['role'] === 'owner';
}

/**
 * Wajibkan login untuk mengakses halaman
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . base_url('login.php'));
        exit;
    }
}

/**
 * Wajibkan role tertentu (bisa array role atau string role tunggal)
 */
function requireRole($allowed_roles) {
    requireLogin();
    
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Akses ditolak
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Akses ditolak! Anda tidak memiliki izin untuk mengakses halaman tersebut.'
        ];
        header("Location: " . base_url('pages/dashboard/index.php'));
        exit;
    }
}

/**
 * Proses login user
 */
function attemptLogin($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Username tidak ditemukan.'];
    }

    if ((int)$user['status_aktif'] !== 1) {
        return ['success' => false, 'message' => 'Akun Anda dinonaktifkan. Hubungi Administrator.'];
    }

    // Cek password hash: dukung SHA256 (sesuai db seed), bcrypt password_verify, atau md5
    $password_matched = false;
    $sha256 = hash('sha256', $password);

    if ($sha256 === $user['password']) {
        $password_matched = true;
    } elseif (password_verify($password, $user['password'])) {
        $password_matched = true;
    } elseif (md5($password) === $user['password']) {
        $password_matched = true;
    }

    if (!$password_matched) {
        return ['success' => false, 'message' => 'Password salah.'];
    }

    // Set session data
    $_SESSION['user_id']      = $user['id_user'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['role']         = $user['role'];

    // Catat log aktivitas login
    catat_log($pdo, $user['id_user'], "User {$user['nama_lengkap']} ({$user['role']}) berhasil login");

    return ['success' => true, 'user' => $user];
}

/**
 * Hash password baru (menggunakan sha256 agar seragam dengan skema varchar(100))
 */
function generatePasswordHash($password) {
    return hash('sha256', $password);
}

