<?php
// index.php - Router Utama
require_once __DIR__ . '/config/helper.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header("Location: " . base_url('pages/dashboard/index.php'));
    exit;
} else {
    header("Location: " . base_url('login.php'));
    exit;
}

