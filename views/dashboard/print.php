<?php
// views/dashboard/print.php

// Hata raporlamayı etkinleştir (Geliştirme aşamasında)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gerekli dosyaları dahil et
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Tarih parametresini al
$selected_date = isset($_GET['tarih']) ? $_GET['tarih'] : '';

// Geçerli bir tarih olup olmadığını kontrol et
if (!$selected_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    die("Geçerli bir tarih seçiniz.");
}

// Seçilen tarihin gün ismini al
function getTurkishWeekday($date) {
    $days = [
        'Sunday'    => 'Pazar',
        'Monday'    => 'Pazartesi',
        'Tuesday'   => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday'  => 'Perşembe',
        'Friday'    => 'Cuma',
        'Saturday'  => 'Cumartesi'
    ];
    $englishDay = date('l', strtotime($date));
    return $days[$englishDay] ?? '';
}

$weekday = getTurkishWeekday($selected_date);

// Sahalar Listesi
$sahalar = $pdo->query("SELECT * FROM sahalar WHERE aktif=1 ORDER BY saha_adi ASC")->fetchAll(PDO::FETCH_ASSOC);

// Seansları Al
$seanslar = [];
foreach ($sahalar as $saha) {
    $saha_id = $saha['id'];
    $saha_adi = htmlspecialchars($saha['saha_adi']);

    // Dolu seansları al
    $doluSeansStmt = $pdo->prepare("SELECT seans_id, ad_soyad, telefon, kapora, created_at FROM rezervasyonlar WHERE saha_id = :saha_id AND tarih = :tarih");
    $doluSeansStmt->execute(['saha_id' => $saha_id, 'tarih' => $selected_date]);
    $doluSeanslar = $doluSeansStmt->fetchAll(PDO::FETCH_ASSOC);

    // Seansları al
    $seansStmt = $pdo->prepare("SELECT s.id, s.baslangic_saati, s.bitis_saati FROM seanslar s WHERE s.saha_id = :saha_id ORDER BY s.baslangic_saati ASC");
    $seansStmt->execute(['saha_id' => $saha_id]);
    $allSeans = $seansStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allSeans as $seans) {
        $seans_id = $seans['id'];
        $baslangic = substr($seans['baslangic_saati'], 0, 5);
        $bitis = substr($seans['bitis_saati'], 0, 5);
        $seans_zaman = "$baslangic - $bitis";

        // Rezervasyon bilgilerini kontrol et
        $rezervasyon = null;
        foreach ($doluSeanslar as $doluSeans) {
            if ($doluSeans['seans_id'] == $seans_id) {
                $rezervasyon = $doluSeans;
                break;
            }
        }

        $seanslar[$saha_id][] = [
            'seans_zaman' => $seans_zaman,
            'rezervasyon' => $rezervasyon
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rezervasyon Bilgileri - Yazdır</title>
    <!-- Bootstrap CSS (Yazdırma için minimal stil) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Yazdırma Stilleri */
        @media print {
            body {
                margin: 20mm;
                font-size: 12pt;
            }
            .no-print {
                display: none;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            h2, h4 {
                text-align: center;
            }
        }
        /* Genel Stiller */
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: auto;
        }
        .saha-section {
            margin-bottom: 40px;
        }
        .seans-table th, .seans-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body onload="window.print();">
    <div class="container" id="printableArea">
        <h2>Rezervasyon Bilgileri</h2>
        <h4><?= htmlspecialchars($selected_date) ?> - <?= htmlspecialchars($weekday) ?> Tarihli Seanslar</h4>

        <?php foreach ($sahalar as $saha): ?>
            <div class="saha-section">
                <h4><?= htmlspecialchars($saha['saha_adi']) ?> Seansları</h4>
                <?php if (isset($seanslar[$saha['id']]) && count($seanslar[$saha['id']]) > 0): ?>
                    <table class="seans-table">
                        <thead>
                            <tr>
                                <th>Seans Saati</th>
                                <th>Ad Soyad</th>
                                <th>Telefon</th>
                                <th>Kapora (TL)</th>
                                <th>Oluşturma Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seanslar[$saha['id']] as $seans): ?>
                                <tr>
                                    <td><?= htmlspecialchars($seans['seans_zaman']) ?></td>
                                    <td><?= htmlspecialchars($seans['rezervasyon']['ad_soyad'] ?? 'Boş') ?></td>
                                    <td><?= htmlspecialchars($seans['rezervasyon']['telefon'] ?? 'Boş') ?></td>
                                    <td><?= isset($seans['rezervasyon']['kapora']) ? number_format($seans['rezervasyon']['kapora'], 2) : '0.00' ?></td>
                                    <td><?= htmlspecialchars($seans['rezervasyon']['created_at'] ?? 'Boş') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Bu sahada seçtiğiniz tarihte seans bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
