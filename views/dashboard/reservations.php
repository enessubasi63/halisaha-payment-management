<?php
// views/dashboard/reservations.php

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

$action = isset($_GET['action']) ? $_GET['action'] : '';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Tarih seçimi
$selected_date = isset($_GET['tarih']) ? $_GET['tarih'] : '';

// Rezervasyon düzenleme (varsa)
$editReservation = null;
if ($action === 'edit' && $edit_id > 0) {
    // Rezervasyon bilgilerini çek
    $editStmt = $pdo->prepare("SELECT * FROM rezervasyonlar WHERE id = :id LIMIT 1");
    $editStmt->execute(['id' => $edit_id]);
    $editReservation = $editStmt->fetch();

    if (!$editReservation) {
        $message = "Düzenlemek istediğiniz rezervasyon bulunamadı.";
        $action = '';
    } else {
        // Seçili tarihi güncelle
        $selected_date = $editReservation['tarih'];
    }
}

// Rezervasyon Silme işlemi (önce onay sonra sil)
if ($action === 'delete' && $edit_id > 0) {
    // Rezervasyonun mevcut olup olmadığını kontrol et
    $delCheckStmt = $pdo->prepare("SELECT * FROM rezervasyonlar WHERE id = :id LIMIT 1");
    $delCheckStmt->execute(['id' => $edit_id]);
    $delReservation = $delCheckStmt->fetch();

    if ($delReservation) {
        // Rezervasyonun tarihini kontrol et
        $current_date = date('Y-m-d');
        $reservation_date = $delReservation['tarih'];

        if (isset($_GET['confirm']) && $_GET['confirm'] == 1) {
            // Rezervasyonu sil
            $delStmt = $pdo->prepare("DELETE FROM rezervasyonlar WHERE id = :id");
            $delStmt->execute(['id' => $edit_id]);

            // Eğer rezervasyon bir aboneliğe aitse, tüm aboneliği sil
            if ($delReservation['subscription_id']) {
                $sub_id = $delReservation['subscription_id'];
                // İlgili tüm rezervasyonları sil
                $pdo->prepare("DELETE FROM rezervasyonlar WHERE subscription_id = :sub_id")->execute(['sub_id' => $sub_id]);
                // Aboneliği sil
                $pdo->prepare("DELETE FROM subscriptions WHERE id = :sub_id")->execute(['sub_id' => $sub_id]);
            }

            $message = "Rezervasyon başarıyla silindi!";
            $action = '';
            $edit_id = 0;
        } else {
            // Onay istenilecek
            $confirmDelete = true;
        }
    } else {
        $message = "Silmek istediğiniz rezervasyon bulunamadı.";
    }
}

// Abonelik iptal etme işlemi
if (isset($_GET['action']) && $_GET['action'] === 'cancel_subscription' && isset($_GET['sub_id'])) {
    $sub_id = (int)$_GET['sub_id'];

    // Aboneliği ve ilgili rezervasyonları sil
    $pdo->beginTransaction();
    try {
        // Rezervasyonları sil
        $delRezStmt = $pdo->prepare("DELETE FROM rezervasyonlar WHERE subscription_id = :sub_id");
        $delRezStmt->execute(['sub_id' => $sub_id]);

        // Aboneliği sil
        $delSubStmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = :sub_id");
        $delSubStmt->execute(['sub_id' => $sub_id]);

        $pdo->commit();

        $message = "Abonelik ve ilgili tüm rezervasyonlar başarıyla iptal edildi.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Abonelik iptal edilirken bir hata oluştu: " . $e->getMessage();
    }
}

// Rezervasyon Güncelleme (Edit POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Güncelleme formundan gelen veriler
    $up_id = (int)$_POST['id'];
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $kapora = (float)($_POST['kapora'] ?? 0.00);

    $editCheckStmt = $pdo->prepare("SELECT * FROM rezervasyonlar WHERE id = :id LIMIT 1");
    $editCheckStmt->execute(['id' => $up_id]);
    $editReservation = $editCheckStmt->fetch();

    if (!$editReservation) {
        $errors[] = "Düzenlemek istediğiniz rezervasyon bulunamadı.";
    } else {
        $current_date = date('Y-m-d');
        $reservation_date = $editReservation['tarih'];
        $is_past = $reservation_date < $current_date;

        if ($is_past) {
            $errors[] = "Geçmiş tarihlere ait rezervasyonlar düzenlenemez.";
        }

        // Validasyon
        if (!$up_id) $errors[] = "Geçersiz rezervasyon ID.";
        if (strlen($ad_soyad) < 3) $errors[] = "Ad Soyad en az 3 karakter olmalıdır.";
        if (empty($telefon)) $errors[] = "Telefon numarası giriniz.";
        if ($kapora < 0) $errors[] = "Kapora negatif olamaz.";

        if (empty($errors)) {
            // Rezervasyonu güncelle
            $updateStmt = $pdo->prepare("UPDATE rezervasyonlar SET ad_soyad = :ad_soyad, telefon = :telefon, kapora = :kapora WHERE id = :id");
            $updateStmt->execute([
                'ad_soyad' => $ad_soyad,
                'telefon' => $telefon,
                'kapora' => $kapora,
                'id' => $up_id
            ]);

            // Kapora güncellemesi
            if ($kapora > 0) {
                $kaporaCheckStmt = $pdo->prepare("SELECT * FROM odeme WHERE rezervasyon_id = :rez_id AND odeme_turu = 'Kapora' LIMIT 1");
                $kaporaCheckStmt->execute(['rez_id' => $up_id]);
                $existingKapora = $kaporaCheckStmt->fetch();

                if ($existingKapora) {
                    // Kapora miktarını güncelle
                    $updateKaporaStmt = $pdo->prepare("UPDATE odeme SET odeme_miktari = :miktar WHERE id = :id");
                    $updateKaporaStmt->execute([
                        'miktar' => $kapora,
                        'id' => $existingKapora['id']
                    ]);
                } else {
                    // Yeni bir kapora ödemesi ekle
                    $insertKaporaStmt = $pdo->prepare("INSERT INTO odeme (rezervasyon_id, odeme_turu, odeme_yapan, odeme_miktari, odeme_tarihi) 
                                                        VALUES (:rez_id, 'Kapora', 'Sistem', :miktar, NOW())");
                    $insertKaporaStmt->execute([
                        'rez_id' => $up_id,
                        'miktar' => $kapora
                    ]);
                }
            }

            header('Location: /views/dashboard/reservations.php?tarih=' . urlencode($selected_date) . '&success=2');
            exit;
        }
    }

    if (!empty($errors)) {
        $editReservation = [
            'id' => $up_id,
            'ad_soyad' => $ad_soyad,
            'telefon' => $telefon,
            'kapora' => $kapora,
            'tarih' => $selected_date,
            'saha_id' => $editReservation['saha_id'] ?? 0,
            'seans_id' => $editReservation['seans_id'] ?? 0
        ];
        $action = 'edit';
        $edit_id = $up_id;
    }
}

// Yeni Rezervasyon Ekleme (POST) (Abonelik özelliği burada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $tarih = $_POST['tarih'] ?? '';
    $saha_id = (int)($_POST['saha_id'] ?? 0);
    $seans_id = (int)($_POST['seans_id'] ?? 0);
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $kapora = (float)($_POST['kapora'] ?? 0.00);

    $is_subscription = isset($_POST['is_subscription']) ? true : false;
    $subscription_duration = $_POST['subscription_duration'] ?? '';

    // Validasyon
    if (!$saha_id) $errors[] = "Lütfen bir saha seçiniz.";
    if (!$seans_id) $errors[] = "Lütfen bir seans seçiniz.";
    if (strlen($ad_soyad) < 3) $errors[] = "Ad Soyad en az 3 karakter olmalıdır.";
    if (empty($telefon)) $errors[] = "Telefon numarası giriniz.";
    if ($kapora < 0) $errors[] = "Kapora negatif olamaz.";
    if (!$tarih) $errors[] = "Lütfen bir tarih seçiniz.";

    if ($is_subscription) {
        $valid_durations = ['3 ay', '6 ay', '1 yıl'];
        if (!in_array($subscription_duration, $valid_durations)) {
            $errors[] = "Geçerli bir abonelik süresi seçiniz.";
        }
    }

    $current_date = date('Y-m-d');
    $is_past = $tarih < $current_date;
    if ($is_past) {
        $errors[] = "Geçmiş tarihlerde rezervasyon yapılamaz.";
    }

    if (empty($errors)) {
        if ($is_subscription) {
            // Abonelik oluşturma
            $pdo->beginTransaction();
            try {
                // Abonelik bilgilerini ekle
                $insertSubStmt = $pdo->prepare("INSERT INTO subscriptions (saha_id, seans_id, ad_soyad, telefon, kapora, start_date, duration) 
                                                VALUES (:saha_id, :seans_id, :ad_soyad, :telefon, :kapora, :start_date, :duration)");
                $insertSubStmt->execute([
                    'saha_id' => $saha_id,
                    'seans_id' => $seans_id,
                    'ad_soyad' => $ad_soyad,
                    'telefon' => $telefon,
                    'kapora' => $kapora,
                    'start_date' => $tarih,
                    'duration' => $subscription_duration
                ]);
                $subscription_id = $pdo->lastInsertId();

                // Abonelik süresine göre haftalık rezervasyonları oluştur
                $duration_months = 0;
                switch ($subscription_duration) {
                    case '3 ay':
                        $duration_months = 3;
                        break;
                    case '6 ay':
                        $duration_months = 6;
                        break;
                    case '1 yıl':
                        $duration_months = 12;
                        break;
                }

                // Başlangıç tarihinden itibaren her hafta aynı gün aynı seans için rezervasyon ekle
                $start_date = new DateTime($tarih);
                $end_date = (clone $start_date)->modify("+{$duration_months} months");

                $current_date_obj = clone $start_date;
                while ($current_date_obj <= $end_date) {
                    $formatted_date = $current_date_obj->format('Y-m-d');

                    // Seansın dolu olup olmadığını kontrol et
                    $check = $pdo->prepare("SELECT COUNT(*) as count FROM rezervasyonlar WHERE seans_id = :seans_id AND saha_id = :saha_id AND tarih = :tarih");
                    $check->execute(['seans_id' => $seans_id, 'saha_id' => $saha_id, 'tarih' => $formatted_date]);
                    $count = $check->fetch()['count'];
                    if ($count == 0) {
                        // Rezervasyonu ekle
                        $insertRezStmt = $pdo->prepare("INSERT INTO rezervasyonlar (saha_id, seans_id, ad_soyad, telefon, kapora, tarih, subscription_id) 
                                                        VALUES (:saha_id, :seans_id, :ad_soyad, :telefon, :kapora, :tarih, :subscription_id)");
                        $insertRezStmt->execute([
                            'saha_id' => $saha_id,
                            'seans_id' => $seans_id,
                            'ad_soyad' => $ad_soyad,
                            'telefon' => $telefon,
                            'kapora' => $kapora,
                            'tarih' => $formatted_date,
                            'subscription_id' => $subscription_id
                        ]);

                        // Eğer kapora girilmişse, kapora ödeme kaydı ekle
                        if ($kapora > 0) {
                            $insertKaporaStmt = $pdo->prepare("INSERT INTO odeme (rezervasyon_id, odeme_turu, odeme_yapan, odeme_miktari, odeme_tarihi) 
                                                                VALUES (:rez_id, 'Kapora', 'Sistem', :miktar, NOW())");
                            $insertKaporaStmt->execute([
                                'rez_id' => $pdo->lastInsertId(),
                                'miktar' => $kapora
                            ]);
                        }
                    }

                    // Bir hafta ekle
                    $current_date_obj->modify('+1 week');
                }

                $pdo->commit();

                header('Location: /views/dashboard/reservations.php?tarih=' . urlencode($tarih) . '&success=1');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Abonelik oluşturulurken bir hata oluştu: " . $e->getMessage();
            }
        } else {
            // Tek seferlik rezervasyon ekle
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO rezervasyonlar (saha_id, seans_id, ad_soyad, telefon, kapora, tarih) 
                                       VALUES (:saha_id, :seans_id, :ad_soyad, :telefon, :kapora, :tarih)");
                $stmt->execute([
                    'saha_id' => $saha_id,
                    'seans_id' => $seans_id,
                    'ad_soyad' => $ad_soyad,
                    'telefon' => $telefon,
                    'kapora' => $kapora,
                    'tarih' => $tarih
                ]);

                $rezervasyon_id = $pdo->lastInsertId();

                if ($kapora > 0) {
                    $insertKaporaStmt = $pdo->prepare("INSERT INTO odeme (rezervasyon_id, odeme_turu, odeme_yapan, odeme_miktari, odeme_tarihi) 
                                                        VALUES (:rez_id, 'Kapora', 'Sistem', :miktar, NOW())");
                    $insertKaporaStmt->execute([
                        'rez_id' => $rezervasyon_id,
                        'miktar' => $kapora
                    ]);
                }

                $pdo->commit();

                header('Location: /views/dashboard/reservations.php?tarih=' . urlencode($tarih) . '&success=1');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Rezervasyon oluşturulurken bir hata oluştu: " . $e->getMessage();
            }
        }
    } else {
        $selected_date = $tarih;
    }
}

// Başarılı mesajları success parametresine göre göster
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Rezervasyon başarıyla oluşturuldu!";
} elseif (isset($_GET['success']) && $_GET['success'] == 2) {
    $message = "Rezervasyon başarıyla güncellendi!";
}

// Sahalar Listesi
$sahalar = $pdo->query("SELECT * FROM sahalar WHERE aktif=1 ORDER BY saha_adi ASC")->fetchAll(PDO::FETCH_ASSOC);

// Seansları gösterme şartı: tarih seçilmiş olmalı
$seanslar = [];
if ($selected_date) {
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

    // Tüm sahalar için seansları al
    foreach ($sahalar as $saha) {
        $saha_id = $saha['id'];
        $saha_adi = htmlspecialchars($saha['saha_adi']);

        // Dolu seansları al
        $doluSeansStmt = $pdo->prepare("SELECT * FROM rezervasyonlar WHERE saha_id = :saha_id AND tarih = :tarih");
        $doluSeansStmt->execute(['saha_id' => $saha_id, 'tarih' => $selected_date]);
        $doluSeanslar = $doluSeansStmt->fetchAll(PDO::FETCH_ASSOC);

        // Seansları al
        $seansStmt = $pdo->prepare("SELECT s.id, s.baslangic_saati, s.bitis_saati 
                                    FROM seanslar s
                                    WHERE s.saha_id = :saha_id
                                    ORDER BY s.baslangic_saati ASC");
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
                'seans_id' => $seans_id,
                'seans_zaman' => $seans_zaman,
                'rezervasyon' => $rezervasyon
            ];
        }
    }
}

// Abonelikleri Listeleme
$subscriptions = $pdo->query("SELECT * FROM subscriptions ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rezervasyon Yönetimi</title>
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
            /* Modal İçindeki Tabloları Kaydırılabilir Yap */
            .modal-body .table-responsive {
                overflow-x: auto;
            }

            /* Küçük Ekranlarda Daha Az Sütun Göster */
            .subscriptions-table th:nth-child(1),
            .subscriptions-table td:nth-child(1),
            .subscriptions-table th:nth-child(2),
            .subscriptions-table td:nth-child(2),
            .subscriptions-table th:nth-child(3),
            .subscriptions-table td:nth-child(3),
            .subscriptions-table th:nth-child(4),
            .subscriptions-table td:nth-child(4),
            .subscriptions-table th:nth-child(5),
            .subscriptions-table td:nth-child(5) {
                display: none;
            }

            .subscriptions-table th:nth-child(6),
            .subscriptions-table td:nth-child(6),
            .subscriptions-table th:nth-child(7),
            .subscriptions-table td:nth-child(7),
            .subscriptions-table th:nth-child(8),
            .subscriptions-table td:nth-child(8),
            .subscriptions-table th:nth-child(9),
            .subscriptions-table td:nth-child(9) {
                display: table-cell;
            }

            /* Daha Küçük Ekranlarda Tabloların Başlıklarını Ekle */
            .subscriptions-table td::before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
            }

            .subscriptions-table th {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../templates/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Rezervasyon Yönetimi</h2>
            <!-- Abonelikleri Görüntüle Butonu -->
            <button class="btn btn-info no-print" data-bs-toggle="modal" data-bs-target="#subscriptionsModal">
                <i class="bi bi-box-arrow-up-right"></i> Abonelikleri Görüntüle
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Rezervasyon Tarihi Seç:</label>
                        <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary me-2 no-print" type="submit">Seansları Göster</button>
                        <?php if ($selected_date): ?>
                            <a href="print.php?tarih=<?= urlencode($selected_date) ?>" class="btn btn-secondary no-print" target="_blank">
                                <i class="bi bi-printer-fill"></i> Yazdır
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <small class="text-muted">Geçmiş veya gelecekteki tarihler seçilebilir. Geçmiş tarihlerde rezervasyon ekleme ve düzenleme işlemleri devre dışıdır.</small>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($message) && $message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($confirmDelete) && $confirmDelete === true): ?>
            <div class="alert alert-warning no-print">
                Bu rezervasyonu silmek istediğinizden emin misiniz?<br>
                <a href="?tarih=<?= urlencode($selected_date) ?>&action=delete&id=<?= htmlspecialchars($edit_id) ?>&confirm=1" class="btn btn-danger btn-sm mt-2">Evet, Sil</a>
                <a href="?tarih=<?= urlencode($selected_date) ?>" class="btn btn-secondary btn-sm mt-2">Vazgeç</a>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <!-- Rezervasyon Formları -->
                <?php if ($action === 'edit' && $editReservation): ?>
                    <?php
                        $current_date = date('Y-m-d');
                        $is_past = $selected_date < $current_date;
                    ?>
                    <?php if (!$is_past): ?>
                        <h4>Rezervasyonu Düzenle</h4>
                        <form method="post" class="mb-4">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($editReservation['id']) ?>">

                            <div class="mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($editReservation['tarih']) ?>" required readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Saha</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars(array_column($sahalar, 'saha_adi', 'id')[$editReservation['saha_id']]) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Seans</label>
                                <?php
                                    $seansStmt = $pdo->prepare("SELECT baslangic_saati, bitis_saati FROM seanslar WHERE id = :id LIMIT 1");
                                    $seansStmt->execute(['id' => $editReservation['seans_id']]);
                                    $seans = $seansStmt->fetch();
                                    if ($seans):
                                        $seans_zaman = substr($seans['baslangic_saati'], 0, 5) . " - " . substr($seans['bitis_saati'], 0, 5);
                                    else:
                                        $seans_zaman = "Seans Bulunamadı";
                                    endif;
                                ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($seans_zaman) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" name="ad_soyad" class="form-control" required value="<?= htmlspecialchars($editReservation['ad_soyad']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="telefon" class="form-control" required value="<?= htmlspecialchars($editReservation['telefon']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kapora (Opsiyonel)</label>
                                <input type="number" step="0.01" name="kapora" class="form-control" value="<?= htmlspecialchars($editReservation['kapora']) ?>" min="0">
                            </div>
                            <button class="btn btn-primary">Güncelle</button>
                            <a href="?tarih=<?= urlencode($selected_date) ?>" class="btn btn-secondary">İptal</a>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">Geçmiş tarihlerde rezervasyon düzenleme işlemi yapılamaz.</div>
                    <?php endif; ?>
                <?php elseif ($action === 'add' && isset($_GET['seans_id']) && isset($_GET['saha_id'])): ?>
                    <?php
                        $seans_id = (int)$_GET['seans_id'];
                        $saha_id = (int)$_GET['saha_id'];

                        $seansStmt = $pdo->prepare("SELECT s.baslangic_saati, s.bitis_saati, h.saha_adi FROM seanslar s JOIN sahalar h ON s.saha_id = h.id WHERE s.id = :id LIMIT 1");
                        $seansStmt->execute(['id' => $seans_id]);
                        $seans = $seansStmt->fetch();

                        if ($seans):
                            $seans_zaman = substr($seans['baslangic_saati'], 0, 5) . " - " . substr($seans['bitis_saati'], 0, 5);
                            $saha_adi = htmlspecialchars($seans['saha_adi']);
                        else:
                            $seans_zaman = "Seans Bulunamadı";
                            $saha_adi = "Seans Bulunamadı";
                        endif;

                        $current_date = date('Y-m-d');
                        $is_past = $selected_date < $current_date;
                    ?>
                    <?php if (!$is_past): ?>
                        <h4>Yeni Rezervasyon Yap</h4>
                        <form method="post" class="mb-4">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="saha_id" value="<?= htmlspecialchars($saha_id) ?>">
                            <input type="hidden" name="seans_id" value="<?= htmlspecialchars($seans_id) ?>">

                            <div class="mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" required readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Saha</label>
                                <input type="text" class="form-control" value="<?= $saha_adi ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Seans</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($seans_zaman) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" name="ad_soyad" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="telefon" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kapora (Opsiyonel)</label>
                                <input type="number" step="0.01" name="kapora" class="form-control" min="0">
                            </div>

                            <!-- Abonelik Seçeneği -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_subscription" name="is_subscription" onchange="toggleSubscriptionOptions()">
                                <label class="form-check-label" for="is_subscription">Abone mi?</label>
                            </div>

                            <!-- Abonelik Süresi -->
                            <div class="mb-3" id="subscription_options" style="display: none;">
                                <label class="form-label">Abonelik Süresi:</label>
                                <select name="subscription_duration" class="form-select">
                                    <option value="">Seçiniz</option>
                                    <option value="3 ay">3 Ay</option>
                                    <option value="6 ay">6 Ay</option>
                                    <option value="1 yıl">1 Yıl</option>
                                </select>
                            </div>

                            <button class="btn btn-success">Rezervasyon Ekle</button>
                            <a href="?tarih=<?= urlencode($selected_date) ?>" class="btn btn-secondary">İptal</a>
                        </form>

                        <script>
                            function toggleSubscriptionOptions() {
                                var isChecked = document.getElementById('is_subscription').checked;
                                var subscriptionOptions = document.getElementById('subscription_options');
                                var subscriptionSelect = document.querySelector('select[name="subscription_duration"]');
                                if (isChecked) {
                                    subscriptionOptions.style.display = 'block';
                                    subscriptionSelect.setAttribute('required', 'required');
                                } else {
                                    subscriptionOptions.style.display = 'none';
                                    subscriptionSelect.removeAttribute('required');
                                }
                            }
                        </script>
                    <?php else: ?>
                        <div class="alert alert-info">Geçmiş tarihlerde rezervasyon ekleme işlemi yapılamaz.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Rezervasyon Formu Görünmez -->
                    <p>Rezervasyon eklemek veya düzenlemek için ilgili seansa tıklayın.</p>
                <?php endif; ?>
            </div>

            <div class="col-md-8" id="printableArea">
                <?php if ($selected_date): ?>
                    <?php
                        echo "<h4>{$selected_date} - {$weekday} Tarihli Seanslar</h4>";
                    ?>
                    <?php foreach ($sahalar as $saha): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <?= htmlspecialchars($saha['saha_adi']) ?> Seansları
                            </div>
                            <div class="card-body">
                                <?php if (isset($seanslar[$saha['id']]) && count($seanslar[$saha['id']]) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Seans Saati</th>
                                                    <th>İşlem</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($seanslar[$saha['id']] as $seans): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($seans['seans_zaman']) ?></td>
                                                        <td>
                                                            <?php if ($seans['rezervasyon']): ?>
                                                                <?php if ($selected_date >= date('Y-m-d')): ?>
                                                                    <a href="?tarih=<?= urlencode($selected_date) ?>&action=edit&id=<?= htmlspecialchars($seans['rezervasyon']['id']) ?>" class="btn btn-warning btn-sm btn-action no-print">
                                                                        <i class="bi bi-pencil-square"></i> Düzenle
                                                                    </a>
                                                                    <a href="?tarih=<?= urlencode($selected_date) ?>&action=delete&id=<?= htmlspecialchars($seans['rezervasyon']['id']) ?>" class="btn btn-danger btn-sm btn-action no-print" onclick="return confirm('Bu rezervasyonu silmek istediğinizden emin misiniz?');">
                                                                        <i class="bi bi-trash-fill"></i> Sil
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button class="btn btn-secondary btn-sm btn-action" disabled>
                                                                        <i class="bi bi-pencil-square"></i> Düzenle
                                                                    </button>
                                                                    <button class="btn btn-secondary btn-sm btn-action" disabled>
                                                                        <i class="bi bi-trash-fill"></i> Sil
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <?php if ($selected_date >= date('Y-m-d')): ?>
                                                                    <a href="?tarih=<?= urlencode($selected_date) ?>&action=add&seans_id=<?= htmlspecialchars($seans['seans_id']) ?>&saha_id=<?= htmlspecialchars($saha['id']) ?>" class="btn btn-success btn-sm btn-action no-print">
                                                                        <i class="bi bi-plus-circle"></i> Rezervasyon Yap
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button class="btn btn-secondary btn-sm btn-action" disabled>
                                                                        <i class="bi bi-plus-circle"></i> Rezervasyon Yap
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php if ($seans['rezervasyon']): ?>
                                                        <tr class="reservation-details">
                                                            <td colspan="2">
                                                                <strong>Ad Soyad:</strong> <?= htmlspecialchars($seans['rezervasyon']['ad_soyad']) ?><br>
                                                                <strong>Telefon:</strong> <?= htmlspecialchars($seans['rezervasyon']['telefon']) ?><br>
                                                                <strong>Kapora:</strong> <?= number_format($seans['rezervasyon']['kapora'], 2) ?> TL<br>
                                                                <strong>Oluşturma Tarihi:</strong> <?= htmlspecialchars($seans['rezervasyon']['created_at']) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p>Bu sahada seçtiğiniz tarihte seans bulunmamaktadır.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Lütfen tarih seçip "Seansları Göster" butonuna tıklayın.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Abonelik Yönetimi Modal -->
    <div class="modal fade" id="subscriptionsModal" tabindex="-1" aria-labelledby="subscriptionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subscriptionsModalLabel">Abonelikler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <?php if (count($subscriptions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped subscriptions-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Saha</th>
                                        <th>Seans</th>
                                        <th>Ad Soyad</th>
                                        <th>Telefon</th>
                                        <th>Kapora</th>
                                        <th>Başlangıç Tarihi</th>
                                        <th>Süre</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $sub): ?>
                                        <tr>
                                            <td data-label="ID"><?= htmlspecialchars($sub['id']) ?></td>
                                            <td data-label="Saha"><?= htmlspecialchars(array_column($sahalar, 'saha_adi', 'id')[$sub['saha_id']]) ?></td>
                                            <td data-label="Seans">
                                                <?php
                                                    $seansStmt = $pdo->prepare("SELECT baslangic_saati, bitis_saati FROM seanslar WHERE id = :id LIMIT 1");
                                                    $seansStmt->execute(['id' => $sub['seans_id']]);
                                                    $seansInfo = $seansStmt->fetch();
                                                    if ($seansInfo):
                                                        $seans_zaman = substr($seansInfo['baslangic_saati'], 0, 5) . " - " . substr($seansInfo['bitis_saati'], 0, 5);
                                                    else:
                                                        $seans_zaman = "Seans Bulunamadı";
                                                    endif;
                                                ?>
                                                <?= htmlspecialchars($seans_zaman) ?>
                                            </td>
                                            <td data-label="Ad Soyad"><?= htmlspecialchars($sub['ad_soyad']) ?></td>
                                            <td data-label="Telefon"><?= htmlspecialchars($sub['telefon']) ?></td>
                                            <td data-label="Kapora"><?= number_format($sub['kapora'], 2) ?> TL</td>
                                            <td data-label="Başlangıç Tarihi"><?= htmlspecialchars($sub['start_date']) ?></td>
                                            <td data-label="Süre"><?= htmlspecialchars($sub['duration']) ?></td>
                                            <td data-label="İşlem">
                                                <a href="?action=cancel_subscription&sub_id=<?= htmlspecialchars($sub['id']) ?>&tarih=<?= urlencode($selected_date) ?>" class="btn btn-danger btn-sm no-print" onclick="return confirm('Bu aboneliği iptal etmek istediğinizden emin misiniz? Tüm ilgili rezervasyonlar silinecektir.');">
                                                    <i class="bi bi-x-circle-fill"></i> İptal Et
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>Henüz aktif bir aboneliğiniz yok.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../templates/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
