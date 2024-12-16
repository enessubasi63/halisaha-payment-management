<?php
// templates/header.php
require_once __DIR__ . '/../config/session.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Halı Saha Rezervasyon Sistemi</title>
    <link rel="icon" type="image/x-icon" href="/public/img/favicon.ico" />

    <!-- Bootstrap CSS (CDN) -->
    <link 
        rel="stylesheet" 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="/public/css/styles.css" />
    <link rel="stylesheet" href="/public/css/login.css" />

    <meta name="description" content="Halı saha rezervasyon sistemi ile sahalarınızı kolayca yönetin." />
    <meta name="keywords" content="halı saha, rezervasyon, yönetim, futbol" />
    <meta name="author" content="Enes Subaşı" />
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Halı Saha</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" 
                aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                        <li class="nav-item"><a class="nav-link" href="/views/dashboard/index.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/views/dashboard/reservations.php">Rezervasyonlar</a></li>
                        <li class="nav-item"><a class="nav-link" href="/views/payments/payments.php">Ödeme Yönetimi</a></li> 
                        <li class="nav-item"><a class="nav-link" href="/views/settings/fields.php">Saha Ayarları</a></li>
                        <li class="nav-item"><a class="nav-link" href="/views/settings/users.php">Kullanıcı Ayarları</a></li>
                        <li class="nav-item"><a class="nav-link text-danger" href="/views/auth/logout.php">Çıkış Yap</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/views/auth/login.php">Giriş Yap</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
