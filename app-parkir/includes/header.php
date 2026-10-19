<?php
// includes/header.php - Header HTML Template
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/helper.php';
    require_once __DIR__ . '/../config/auth.php';
}

$page_title = $page_title ?? 'Sistem Manajemen Parkir';
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - ParkirApp</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>
<div class="app-wrapper">

