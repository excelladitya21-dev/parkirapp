<?php
// login.php - Halaman Login dengan Kartu Slot Parkir di Samping
define('APP_INIT', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';
require_once __DIR__ . '/config/auth.php';

// Jika sudah login, langsung ke dashboard
if (isLoggedIn()) {
    header("Location: " . base_url('pages/dashboard/index.php'));
    exit;
}

// Sinkronkan data area parkir terkini
sync_area_terisi($pdo);

// Ambil data slot area parkir
$stmt_area = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY id_area ASC");
$areas = $stmt_area->fetchAll();

$total_kapasitas = 0;
$total_terisi = 0;
foreach ($areas as $a) {
    $total_kapasitas += (int)$a['kapasitas'];
    $total_terisi += (int)$a['terisi'];
}
$total_tersedia = max(0, $total_kapasitas - $total_terisi);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password wajib diisi!';
    } else {
        $login = attemptLogin($pdo, $username, $password);
        if ($login['success']) {
            set_flash('success', "Selamat datang kembali, {$login['user']['nama_lengkap']}!");
            header("Location: " . base_url('pages/dashboard/index.php'));
            exit;
        } else {
            $error_message = $login['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Ketersediaan Slot Parkir - ParkirApp</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0284c7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
        }
        .app-panel-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.25);
            overflow: hidden;
        }
        .slot-item-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 1rem 1.25rem;
            transition: all 0.2s ease;
        }
        .slot-item-box:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-dot 1.6s infinite;
        }
        @keyframes pulse-dot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 980px;">
    <div class="row g-4 align-items-stretch justify-content-center">

        <!-- KARTU 1 (KIRI / SAMPING): KETERSEDIAAN SLOT PARKIR -->
        <div class="col-md-6 col-lg-5">
            <div class="app-panel-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <!-- Header Kartu Slot -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-geo-alt-fill text-primary fs-5"></i>
                            <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.5px;">SLOT PARKIR SAAT INI</span>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small d-flex align-items-center gap-1">
                            <span class="live-dot"></span>
                            <span>Realtime</span>
                        </span>
                    </div>

                    <!-- Kotak Slot Setiap Area -->
                    <div class="d-flex flex-column gap-3 mb-3">
                        <?php foreach ($areas as $area): 
                            $kapasitas = (int)$area['kapasitas'];
                            $terisi = (int)$area['terisi'];
                            $sisa = max(0, $kapasitas - $terisi);
                            $is_full = ($sisa <= 0);
                            $persen = $kapasitas > 0 ? round(($terisi / $kapasitas) * 100) : 0;
                            $bar_color = $is_full ? 'bg-danger' : ($persen >= 70 ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="slot-item-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark fs-6"><?= e($area['nama_area']) ?></span>
                                <span class="badge <?= $is_full ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> rounded-pill small">
                                    <?= $is_full ? 'Penuh' : 'Tersedia' ?>
                                </span>
                            </div>
                            
                            <div class="d-flex align-items-baseline gap-2 my-1">
                                <span class="fs-2 fw-bold <?= $is_full ? 'text-danger' : 'text-success' ?>"><?= $sisa ?></span>
                                <span class="text-muted fw-semibold">Slot Tersedia</span>
                            </div>

                            <div class="text-muted small mb-2">
                                <?php if ($is_full): ?>
                                    <span class="text-danger fw-semibold">Kapasitas penuh (<?= $terisi ?> dari <?= $kapasitas ?> terisi)</span>
                                <?php else: ?>
                                    Tersedia (Sisa dari total <?= $kapasitas ?> slot)
                                <?php endif; ?>
                            </div>

                            <div class="progress" style="height: 6px; background-color: #e2e8f0;">
                                <div class="progress-bar <?= $bar_color ?>" style="width: <?= min(100, $persen) ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Footer Kartu Slot -->
                <div class="pt-3 border-top mt-auto">
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                        <span>Total Slot Tersedia:</span>
                        <strong class="text-success fs-6"><?= $total_tersedia ?> / <?= $total_kapasitas ?></strong>
                    </div>
                    <button type="button" onclick="location.reload()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                        <i class="bi bi-arrow-clockwise me-1"></i> Perbarui Status Slot
                    </button>
                </div>
            </div>
        </div>

        <!-- KARTU 2 (KANAN): FORM LOGIN SISTEM -->
        <div class="col-md-6 col-lg-6">
            <div class="app-panel-card p-4 p-md-5 h-100 d-flex flex-column justify-content-center">
                <div>
                    <!-- Header Form Login -->
                    <div class="text-center mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-2 mb-2 shadow-sm">
                            <i class="bi bi-p-square-fill fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-0">Aplikasi Parkir</h4>
                        <small class="text-muted">Silakan masuk menggunakan akun terdaftar</small>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= e($error_message) ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" name="username" id="usernameInput" class="form-control border-start-0 ps-0" placeholder="Masukkan username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="password" id="passwordInput" class="form-control border-start-0 border-end-0 ps-0" placeholder="Masukkan password" required>
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show/Hide Password
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('passwordInput');
const eyeIcon = document.getElementById('eyeIcon');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });
}
</script>
</body>
</html>
