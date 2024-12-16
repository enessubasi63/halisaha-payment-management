<?php
// views/dashboard/index.php

// Hata raporlamayı etkinleştir (Geliştirme aşamasında, üretim ortamında kapatılmalıdır)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gerekli dosyaları dahil et
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Veri Çekme Fonksiyonları

try {
    // Toplam Rezervasyon Sayısı
    $totalReservationsStmt = $pdo->query("SELECT COUNT(*) as total FROM rezervasyonlar");
    $totalReservations = $totalReservationsStmt->fetch()['total'];

    // Toplam Ödeme Miktarı
    $totalPaymentsStmt = $pdo->query("SELECT SUM(odeme_miktari) as total FROM odeme");
    $totalPayments = $totalPaymentsStmt->fetch()['total'] ?? 0;

    // Toplam Gelir (Kapora dahil)
    $totalRevenueStmt = $pdo->query("SELECT SUM(h.fiyat) as total FROM rezervasyonlar r JOIN sahalar h ON r.saha_id = h.id");
    $totalRevenue = $totalRevenueStmt->fetch()['total'] ?? 0;

    // Bekleyen Ödemeler (Toplam Gelir - Toplam Ödeme)
    $pendingPayments = $totalRevenue - $totalPayments;

    // Ortalama Rezervasyon Değeri
    $averageReservationValue = $totalReservations > 0 ? $totalRevenue / $totalReservations : 0;

    // En Popüler Sahalar
    $popularFieldsStmt = $pdo->query("
        SELECT h.saha_adi, COUNT(r.id) as rezervasyon_sayisi 
        FROM rezervasyonlar r 
        JOIN sahalar h ON r.saha_id = h.id 
        GROUP BY h.id 
        ORDER BY rezervasyon_sayisi DESC 
        LIMIT 5
    ");
    $popularFields = $popularFieldsStmt->fetchAll(PDO::FETCH_ASSOC);

    // En Fazla Ödeme Yapan Kullanıcılar
    $topPayersStmt = $pdo->query("
        SELECT r.ad_soyad, r.telefon, SUM(o.odeme_miktari) as toplam_odeme 
        FROM odeme o 
        JOIN rezervasyonlar r ON o.rezervasyon_id = r.id 
        GROUP BY r.id 
        ORDER BY toplam_odeme DESC 
        LIMIT 5
    ");
    $topPayers = $topPayersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Aylık Rezervasyon Sayısı (Son 12 Ay)
    $monthlyReservationsStmt = $pdo->query("
        SELECT DATE_FORMAT(tarih, '%Y-%m') as ay, COUNT(*) as sayi 
        FROM rezervasyonlar 
        WHERE tarih >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
        GROUP BY ay 
        ORDER BY ay ASC
    ");
    $monthlyReservations = $monthlyReservationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Aylık Ödeme Miktarı (Son 12 Ay)
    $monthlyPaymentsStmt = $pdo->query("
        SELECT DATE_FORMAT(odeme_tarihi, '%Y-%m') as ay, SUM(odeme_miktari) as miktar 
        FROM odeme 
        WHERE odeme_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
        GROUP BY ay 
        ORDER BY ay ASC
    ");
    $monthlyPayments = $monthlyPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Gelir Dağılımı (Ödeme Türlerine Göre)
    $revenueByPaymentTypeStmt = $pdo->query("
        SELECT odeme_turu, SUM(odeme_miktari) as toplam 
        FROM odeme 
        GROUP BY odeme_turu
    ");
    $revenueByPaymentType = $revenueByPaymentTypeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Kullanıcı Segmentasyonu (Yeni vs. Tekrar Eden)
    $userSegmentationStmt = $pdo->query("
        SELECT 
            segment, COUNT(*) as sayi
        FROM (
            SELECT 
                CASE 
                    WHEN COUNT(r.id) = 1 THEN 'Yeni Kullanıcı'
                    ELSE 'Tekrar Eden Kullanıcı'
                END as segment
            FROM rezervasyonlar r
            GROUP BY r.telefon
        ) as user_segments
        GROUP BY segment
    ");
    $userSegmentation = $userSegmentationStmt->fetchAll(PDO::FETCH_ASSOC);

    // Gelir Dağılımı (Saha Bazında)
    $revenueByFieldStmt = $pdo->query("
        SELECT h.saha_adi, SUM(o.odeme_miktari) as toplam_odeme
        FROM odeme o
        JOIN rezervasyonlar r ON o.rezervasyon_id = r.id
        JOIN sahalar h ON r.saha_id = h.id
        GROUP BY h.id
        ORDER BY toplam_odeme DESC
        LIMIT 5
    ");
    $revenueByField = $revenueByFieldStmt->fetchAll(PDO::FETCH_ASSOC);

    // Son Rezervasyonlar
    $recentReservationsStmt = $pdo->prepare("
        SELECT r.id, h.saha_adi, r.tarih, s.baslangic_saati, s.bitis_saati, r.ad_soyad, r.telefon, r.kapora, r.created_at
        FROM rezervasyonlar r
        JOIN sahalar h ON r.saha_id = h.id
        JOIN seanslar s ON r.seans_id = s.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $recentReservationsStmt->execute();
    $recentReservations = $recentReservationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Son Ödemeler
    $recentPaymentsStmt = $pdo->prepare("
        SELECT o.id, r.ad_soyad, r.tarih, h.saha_adi, o.odeme_turu, o.odeme_yapan, o.odeme_miktari, o.odeme_tarihi
        FROM odeme o
        JOIN rezervasyonlar r ON o.rezervasyon_id = r.id
        JOIN sahalar h ON r.saha_id = h.id
        ORDER BY o.odeme_tarihi DESC
        LIMIT 10
    ");
    $recentPaymentsStmt->execute();
    $recentPayments = $recentPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Rezervasyon Dağılımı (Bugün, Son 7 Gün, Son 30 Gün)
    $reservationDistributionStmt = $pdo->query("
        SELECT 
            CASE 
                WHEN tarih = CURDATE() THEN 'Bugün'
                WHEN tarih BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 'Son 7 Gün'
                ELSE 'Son 30 Gün'
            END as periyot, COUNT(*) as sayi
        FROM rezervasyonlar
        WHERE tarih >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY periyot
    ");
    $reservationDistribution = $reservationDistributionStmt->fetchAll(PDO::FETCH_ASSOC);

    // Tablolar İçin Veri Hazırlama

    // Aylık Rezervasyonlar ve Ödemeler için Grafik Verileri
    $labels = [];
    $reservationData = [];
    $paymentData = [];

    // 12 Aylık Verileri Birleştirme
    for ($i = 11; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M Y', strtotime($date));

        // Rezervasyon Sayısı
        $found = false;
        foreach ($monthlyReservations as $mr) {
            if ($mr['ay'] == $date) {
                $reservationData[] = (int)$mr['sayi'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $reservationData[] = 0;
        }

        // Ödeme Miktarı
        $found = false;
        foreach ($monthlyPayments as $mp) {
            if ($mp['ay'] == $date) {
                $paymentData[] = (float)$mp['miktar'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $paymentData[] = 0;
        }
    }

} catch (PDOException $e) {
    // Hata mesajını güvenli bir şekilde göster (geliştirme aşamasında)
    echo "Veritabanı Hatası: " . htmlspecialchars($e->getMessage());
    exit;
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <h2>Yönetici Paneli</h2>
    <p>Hoş geldiniz! Sisteminizin genel performansını ve önemli metriklerini aşağıda bulabilirsiniz.</p>

    <!-- Özet Kartlar -->
    <div class="row">
        <!-- Toplam Rezervasyonlar -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Rezervasyonlar
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalReservations) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-check-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Toplam Ödemeler -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Toplam Ödemeler
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalPayments, 2) ?> TL</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash-stack fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Toplam Gelir -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Toplam Gelir
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalRevenue, 2) ?> TL</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-wallet-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Bekleyen Ödemeler -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Bekleyen Ödemeler
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($pendingPayments, 2) ?> TL</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hourglass-split fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Ortalama Rezervasyon Değeri -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Ortalama Rezervasyon Değeri
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($averageReservationValue, 2) ?> TL</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="row">
        <!-- Aylık Rezervasyon Sayısı -->
        <div class="col-xl-6 col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Aylık Rezervasyon Sayısı (Son 12 Ay)</h6>
                </div>
                <div class="card-body">
                    <canvas id="reservationsChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Aylık Ödeme Miktarı -->
        <div class="col-xl-6 col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-info">Aylık Ödeme Miktarı (Son 12 Ay)</h6>
                </div>
                <div class="card-body">
                    <canvas id="paymentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gelir Dağılımı -->
    <div class="row">
        <!-- Gelir Dağılımı (Ödeme Türlerine Göre) -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-success">Gelir Dağılımı (Ödeme Türlerine Göre)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueByPaymentTypeChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Gelir Dağılımı (Saha Bazında) -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">Gelir Dağılımı (Saha Bazında)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueByFieldChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ek Analizler -->
    <div class="row">
        <!-- Kullanıcı Segmentasyonu -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-danger">Kullanıcı Segmentasyonu</h6>
                </div>
                <div class="card-body">
                    <canvas id="userSegmentationChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Rezervasyon Dağılımı -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">Rezervasyon Dağılımı</h6>
                </div>
                <div class="card-body">
                    <canvas id="reservationDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detaylı Tablolar -->
    <div class="row">
        <!-- Son Rezervasyonlar -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Son 10 Rezervasyon</h6>
                    <a href="/views/dashboard/reservations.php" class="btn btn-sm btn-primary">Daha Fazla <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Saha</th>
                                    <th>Tarih</th>
                                    <th>Seans</th>
                                    <th>Ad Soyad</th>
                                    <th>Telefon</th>
                                    <th>Kapora</th>
                                    <th>Oluşturma</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentReservations as $rez): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rez['id']) ?></td>
                                        <td><?= htmlspecialchars($rez['saha_adi']) ?></td>
                                        <td><?= htmlspecialchars($rez['tarih']) ?></td>
                                        <td><?= substr($rez['baslangic_saati'],0,5) . " - " . substr($rez['bitis_saati'],0,5) ?></td>
                                        <td><?= htmlspecialchars($rez['ad_soyad']) ?></td>
                                        <td><?= htmlspecialchars($rez['telefon']) ?></td>
                                        <td><?= number_format($rez['kapora'], 2) ?> TL</td>
                                        <td><?= htmlspecialchars($rez['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Son Ödemeler -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-success">Son 10 Ödeme</h6>
                    <a href="/views/payments/payments.php" class="btn btn-sm btn-success">Daha Fazla <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Ad Soyad</th>
                                    <th>Saha</th>
                                    <th>Tarih</th>
                                    <th>Ödeme Türü</th>
                                    <th>Ödeme Yapan</th>
                                    <th>Ödeme Miktarı</th>
                                    <th>Ödeme Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentPayments as $pay): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pay['id']) ?></td>
                                        <td><?= htmlspecialchars($pay['ad_soyad']) ?></td>
                                        <td><?= htmlspecialchars($pay['saha_adi']) ?></td>
                                        <td><?= htmlspecialchars($pay['tarih']) ?></td>
                                        <td><?= htmlspecialchars($pay['odeme_turu']) ?></td>
                                        <td><?= htmlspecialchars($pay['odeme_yapan']) ?></td>
                                        <td><?= number_format($pay['odeme_miktari'], 2) ?> TL</td>
                                        <td><?= htmlspecialchars($pay['odeme_tarihi']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ek Analizler -->
    <div class="row">
        <!-- En Popüler 5 Saha -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">En Popüler 5 Saha</h6>
                </div>
                <div class="card-body">
                    <canvas id="popularFieldsChart"></canvas>
                </div>
            </div>
        </div>
        <!-- En Fazla Ödeme Yapan 5 Kullanıcı -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-danger">En Fazla Ödeme Yapan 5 Kullanıcı</h6>
                </div>
                <div class="card-body">
                    <canvas id="topPayersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Segmentasyonu ve Rezervasyon Dağılımı -->
    <div class="row">
        <!-- Kullanıcı Segmentasyonu Grafiği -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">Kullanıcı Segmentasyonu</h6>
                </div>
                <div class="card-body">
                    <canvas id="userSegmentationChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Rezervasyon Dağılımı Grafiği -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Rezervasyon Dağılımı</h6>
                </div>
                <div class="card-body">
                    <canvas id="reservationDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ek Raporlar -->
    <div class="row">
        <!-- Gelir Trend Grafiği -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-info">Gelir Trend Grafiği</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Ortalama Rezervasyon Değeri Grafiği -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-success">Ortalama Rezervasyon Değeri Grafiği</h6>
                </div>
                <div class="card-body">
                    <canvas id="averageReservationValueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Kütüphanesini Dahil Et -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Bootstrap Icons (Opsiyonel) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- CSS Düzenlemeleri -->
<style>
    .card-header a {
        text-decoration: none;
    }
    .table-responsive {
        max-height: 400px;
    }
</style>

<script>
// Aylık Rezervasyon Sayısı Grafiği
const reservationsCtx = document.getElementById('reservationsChart').getContext('2d');
const reservationsChart = new Chart(reservationsCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Rezervasyon Sayısı',
            data: <?= json_encode($reservationData) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' Rezervasyon';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                precision: 0
            }
        }
    }
});

// Aylık Ödeme Miktarı Grafiği
const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
const paymentsChart = new Chart(paymentsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Ödeme Miktarı (TL)',
            data: <?= json_encode($paymentData) ?>,
            fill: true,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gelir Dağılımı (Ödeme Türlerine Göre) Grafiği
const revenueByPaymentTypeCtx = document.getElementById('revenueByPaymentTypeChart').getContext('2d');
const revenueByPaymentTypeChart = new Chart(revenueByPaymentTypeCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($revenueByPaymentType, 'odeme_turu')) ?>,
        datasets: [{
            label: 'Gelir',
            data: <?= json_encode(array_column($revenueByPaymentType, 'toplam')) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderColor: [
                'rgba(255, 99, 132,1)',
                'rgba(54, 162, 235,1)',
                'rgba(255, 206, 86,1)',
                'rgba(75, 192, 192,1)',
                'rgba(153, 102, 255,1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            label += new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed);
                        }
                        return label;
                    }
                }
            }
        }
    }
});

// Gelir Dağılımı (Saha Bazında) Grafiği
const revenueByFieldCtx = document.getElementById('revenueByFieldChart').getContext('2d');
const revenueByFieldChart = new Chart(revenueByFieldCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($revenueByField, 'saha_adi')) ?>,
        datasets: [{
            label: 'Gelir',
            data: <?= json_encode(array_column($revenueByField, 'toplam_odeme')) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderColor: [
                'rgba(255, 99, 132,1)',
                'rgba(54, 162, 235,1)',
                'rgba(255, 206, 86,1)',
                'rgba(75, 192, 192,1)',
                'rgba(153, 102, 255,1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            label += new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed);
                        }
                        return label;
                    }
                }
            }
        }
    }
});

// En Fazla Ödeme Yapan Kullanıcılar Grafiği
const topPayersCtx = document.getElementById('topPayersChart').getContext('2d');
const topPayersChart = new Chart(topPayersCtx, {
    type: 'bar', // 'horizontalBar' deprecated in Chart.js v3, use 'bar' with indexAxis
    data: {
        labels: <?= json_encode(array_column($topPayers, 'ad_soyad')) ?>,
        datasets: [{
            label: 'Toplam Ödeme (TL)',
            data: <?= json_encode(array_column($topPayers, 'toplam_odeme')) ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.6)',
            borderColor: 'rgba(255, 159, 64,1)',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y', // Horizontal bar chart
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.x);
                    }
                }
            }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

// Kullanıcı Segmentasyonu Grafiği
const userSegmentationCtx = document.getElementById('userSegmentationChart').getContext('2d');
const userSegmentationChart = new Chart(userSegmentationCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($userSegmentation, 'segment')) ?>,
        datasets: [{
            label: 'Kullanıcı Segmentasyonu',
            data: <?= json_encode(array_column($userSegmentation, 'sayi')) ?>,
            backgroundColor: [
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderColor: [
                'rgba(75, 192, 192,1)',
                'rgba(153, 102, 255,1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            label += context.parsed;
                        }
                        return label;
                    }
                }
            }
        }
    }
});

// Rezervasyon Dağılımı Grafiği
const reservationDistributionCtx = document.getElementById('reservationDistributionChart').getContext('2d');
const reservationDistributionChart = new Chart(reservationDistributionCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($reservationDistribution, 'periyot')) ?>,
        datasets: [{
            label: 'Rezervasyon Dağılımı',
            data: <?= json_encode(array_column($reservationDistribution, 'sayi')) ?>,
            backgroundColor: [
                'rgba(255, 205, 86, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 99, 132, 0.6)'
            ],
            borderColor: [
                'rgba(255, 205, 86,1)',
                'rgba(54, 162, 235,1)',
                'rgba(255, 99, 132,1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            label += context.parsed;
                        }
                        return label;
                    }
                }
            }
        }
    }
});

// Gelir Trend Grafiği
const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
const revenueTrendChart = new Chart(revenueTrendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Gelir Trend (TL)',
            data: <?= json_encode($paymentData) ?>,
            fill: true,
            backgroundColor: 'rgba(153, 102, 255, 0.2)',
            borderColor: 'rgba(153, 102, 255, 1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Ortalama Rezervasyon Değeri Grafiği
const averageReservationValueCtx = document.getElementById('averageReservationValueChart').getContext('2d');
const averageReservationValueChart = new Chart(averageReservationValueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Ortalama Rezervasyon Değeri (TL)',
            data: <?= json_encode(array_fill(0, 12, $averageReservationValue)) ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.6)',
            borderColor: 'rgba(255, 159, 64,1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
