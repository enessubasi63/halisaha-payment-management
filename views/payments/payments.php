<?php
// views/payments/payments.php

// Hata raporlamayı etkinleştir (Geliştirme aşamasında)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gerekli dosyaları dahil et
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

$message = '';
$errors = [];

// Başarılı mesajları göster
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $message = "Ödeme başarıyla alındı.";
    } elseif ($_GET['success'] == 2) {
        $message = "Ödeme başarıyla güncellendi.";
    }
}

// Tarih seçimi
$selected_date = isset($_GET['tarih']) ? $_GET['tarih'] : '';

// Sahalar Listesi
$sahalarStmt = $pdo->query("SELECT * FROM sahalar WHERE aktif=1 ORDER BY saha_adi ASC");
$sahalar = $sahalarStmt->fetchAll(PDO::FETCH_ASSOC);

// Rezervasyonları çekme (Tarih seçilmişse)
$reservations = [];
if ($selected_date) {
    $reservationsStmt = $pdo->prepare("
        SELECT 
            r.id, 
            h.id AS saha_id,
            h.saha_adi, 
            r.tarih, 
            s.baslangic_saati, 
            s.bitis_saati, 
            r.ad_soyad, 
            r.telefon, 
            r.kapora, 
            h.fiyat,
            r.subscription_id
        FROM rezervasyonlar r
        JOIN sahalar h ON r.saha_id = h.id
        JOIN seanslar s ON r.seans_id = s.id
        WHERE r.tarih = :tarih
        ORDER BY h.saha_adi ASC, s.baslangic_saati ASC
    ");
    $reservationsStmt->execute(['tarih' => $selected_date]);
    $allReservations = $reservationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Rezervasyonları sahaya göre grupla (saha_id kullanarak)
    foreach ($sahalar as $saha) {
        $saha_id = $saha['id'];
        $saha_adi = $saha['saha_adi'];
        $saha_reservations = array_filter($allReservations, function($rez) use ($saha_id) {
            return $rez['saha_id'] == $saha_id;
        });
        $reservations[$saha_id] = $saha_reservations;
    }
}

// Fonksiyon: Bir rezervasyona ait ödemeleri çekme
function getPaymentsForReservation($rezervasyon_id, $pdo) {
    $paymentsStmt = $pdo->prepare("SELECT * FROM odeme WHERE rezervasyon_id = :rez_id ORDER BY odeme_tarihi DESC");
    $paymentsStmt->execute(['rez_id' => $rezervasyon_id]);
    return $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Yönetimi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Ekstra Stil Düzenlemeleri */
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-action {
            min-width: 150px;
        }
        .card-header {
            font-size: 1.25rem;
        }
        .reservation-details {
            background-color: #f8f9fa;
        }
        /* Yazdırma Stilleri */
        @media print {
            .no-print {
                display: none;
            }
            #printableArea {
                width: 210mm;
                /* A4 genişliği */
                margin: 0 auto;
            }
            body {
                -webkit-print-color-adjust: exact;
            }
        }

        /* Mobil İçin Özelleştirilmiş Stil */
        @media (max-width: 767.98px) {
            /* Tabloların Kaydırılabilir Olmasını Sağla */
            .table-responsive {
                overflow-x: auto;
            }

            /* Daha İyi Görünüm için Hücreleri Düzenle */
            /* ID ve Telefon sütunlarını gizle */
            .payment-table th:nth-child(1),
            .payment-table td:nth-child(1),
            .payment-table th:nth-child(4),
            .payment-table td:nth-child(4) {
                display: none;
            }

            /* Diğer sütunları göster */
            .payment-table th:nth-child(2),
            .payment-table td:nth-child(2),
            .payment-table th:nth-child(3),
            .payment-table td:nth-child(3),
            .payment-table th:nth-child(5),
            .payment-table td:nth-child(5),
            .payment-table th:nth-child(6),
            .payment-table td:nth-child(6),
            .payment-table th:nth-child(7),
            .payment-table td:nth-child(7),
            .payment-table th:nth-child(8),
            .payment-table td:nth-child(8),
            .payment-table th:nth-child(9),
            .payment-table td:nth-child(9) {
                display: table-cell;
            }

            /* Hücre Başlıklarını Etiketle */
            .payment-table td::before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
            }

            .payment-table th {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../templates/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Ödeme Yönetimi</h2>
            <!-- Yazdırma Butonu -->
            <?php if ($selected_date): ?>
                <a href="print_payments.php?tarih=<?= urlencode($selected_date) ?>" class="btn btn-secondary no-print" target="_blank">
                    <i class="bi bi-printer-fill"></i> Yazdır
                </a>
            <?php endif; ?>
        </div>

        <!-- Tarih Seçim Formu -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Ödeme Tarihi Seç:</label>
                        <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary me-2 no-print" type="submit">Ödemeleri Göster</button>
                        <?php if ($selected_date): ?>
                            <a href="print_payments.php?tarih=<?= urlencode($selected_date) ?>" class="btn btn-secondary no-print" target="_blank">
                                <i class="bi bi-printer-fill"></i> Yazdır
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <small class="text-muted">Geçmiş veya gelecekteki tarihler seçilebilir.</small>
            </div>
        </div>

        <!-- Başarılı Mesajlar -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Hata Mesajları -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Ödeme Yönetim Kartları -->
        <?php if ($selected_date): ?>
            <?php foreach ($sahalar as $saha): ?>
                <?php
                    $saha_id = $saha['id'];
                    $saha_adi = $saha['saha_adi'];
                    // Seçilen tarih ve saha için rezervasyonları al
                    $saha_reservations = isset($reservations[$saha_id]) ? $reservations[$saha_id] : [];
                ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <?= htmlspecialchars($saha_adi) ?> - <?= htmlspecialchars($selected_date) ?> Tarihli Ödemeler
                    </div>
                    <div class="card-body">
                        <?php if (count($saha_reservations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped payment-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Seans</th>
                                            <th>Ad Soyad</th>
                                            <th>Telefon</th>
                                            <th>Kapora</th>
                                            <th>Toplam Ücret</th>
                                            <th>Kalan Ödeme</th>
                                            <th>Ödeme Durumu</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($saha_reservations as $rez): ?>
                                            <?php 
                                                // Alınan toplam ödeme miktarını hesapla
                                                $payments = getPaymentsForReservation($rez['id'], $pdo);
                                                $total_payment = 0;
                                                foreach ($payments as $pay) {
                                                    $total_payment += $pay['odeme_miktari'];
                                                }

                                                // Toplam ücret = saha fiyatı
                                                $toplam_ucret = (float)$rez['fiyat'];

                                                // Kalan ödeme = toplam ücret - toplam ödeme
                                                $kalan_odeme = $toplam_ucret - $total_payment;

                                                // Ödeme durumu
                                                if ($kalan_odeme <= 0) {
                                                    $odeme_durumu = '<span class="badge bg-success">Tamamlandı</span>';
                                                } elseif ($total_payment > 0) {
                                                    $odeme_durumu = '<span class="badge bg-warning text-dark">Kısmi Ödeme</span>';
                                                } else {
                                                    $odeme_durumu = '<span class="badge bg-danger">Ödenmedi</span>';
                                                }
                                            ?>
                                            <tr>
                                                <td data-label="ID"><?= htmlspecialchars($rez['id']) ?></td>
                                                <td data-label="Seans"><?= substr($rez['baslangic_saati'],0,5) . " - " . substr($rez['bitis_saati'],0,5) ?></td>
                                                <td data-label="Ad Soyad"><?= htmlspecialchars($rez['ad_soyad']) ?></td>
                                                <td data-label="Telefon"><?= htmlspecialchars($rez['telefon']) ?></td>
                                                <td data-label="Kapora"><?= number_format($rez['kapora'], 2) ?> TL</td>
                                                <td data-label="Toplam Ücret"><?= number_format($toplam_ucret, 2) ?> TL</td>
                                                <td data-label="Kalan Ödeme"><?= number_format($kalan_odeme, 2) ?> TL</td>
                                                <td data-label="Ödeme Durumu"><?= $odeme_durumu ?></td>
                                                <td data-label="İşlemler">
                                                    <?php if ($kalan_odeme > 0): ?>
                                                        <a href="/views/payments/take_payment.php?rez_id=<?= $rez['id'] ?>" class="btn btn-sm btn-primary mb-1 no-print">Ödeme Al</a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary mb-1" disabled>Ödeme Al</button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-info mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#payments<?= $rez['id'] ?>" aria-expanded="false" aria-controls="payments<?= $rez['id'] ?>">
                                                        Ödemeleri Göster
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="9" class="p-0">
                                                    <div class="collapse" id="payments<?= $rez['id'] ?>">
                                                        <div class="card card-body">
                                                            <?php if (count($payments) > 0): ?>
                                                                <table class="table table-sm table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Ödeme Türü</th>
                                                                            <th>Ödeme Yapan</th>
                                                                            <th>Ödeme Miktarı</th>
                                                                            <th>Ödeme Tarihi</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach($payments as $pay): ?>
                                                                            <tr>
                                                                                <td><?= htmlspecialchars($pay['id']) ?></td>
                                                                                <td><?= htmlspecialchars($pay['odeme_turu']) ?></td>
                                                                                <td><?= htmlspecialchars($pay['odeme_yapan']) ?></td>
                                                                                <td><?= number_format($pay['odeme_miktari'], 2) ?> TL</td>
                                                                                <td><?= htmlspecialchars($pay['odeme_tarihi']) ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php else: ?>
                                                                <div class="alert alert-info mb-0">Bu rezervasyon için henüz ödeme alınmamıştır.</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Bu sahada seçtiğiniz tarihte rezervasyon bulunmamaktadır.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">Lütfen tarih seçip "Ödemeleri Göster" butonuna tıklayın.</div>
        <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/../../templates/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
