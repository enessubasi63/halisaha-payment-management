<?php
// views/payments/take_payment.php

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

// Rezervasyon ID'sini al
$rezervasyon_id = isset($_GET['rez_id']) ? (int)$_GET['rez_id'] : 0;

// Rezervasyon bilgilerini çek
$rezStmt = $pdo->prepare("
    SELECT 
        r.id, 
        h.saha_adi, 
        r.tarih, 
        s.baslangic_saati, 
        s.bitis_saati, 
        r.ad_soyad, 
        r.telefon, 
        r.kapora, 
        h.fiyat
    FROM rezervasyonlar r
    JOIN sahalar h ON r.saha_id = h.id
    JOIN seanslar s ON r.seans_id = s.id
    WHERE r.id = :id LIMIT 1
");
$rezStmt->execute(['id' => $rezervasyon_id]);
$rezervasyon = $rezStmt->fetch(PDO::FETCH_ASSOC);

// Rezervasyon bulunamadıysa hata mesajı ekle
if (!$rezervasyon) {
    $errors[] = "Geçerli bir rezervasyon seçmediniz.";
}

// Rezervasyonun toplam ödemesini hesapla
function getPaymentsForReservation($rezervasyon_id, $pdo) {
    $paymentsStmt = $pdo->prepare("SELECT * FROM odeme WHERE rezervasyon_id = :rez_id ORDER BY odeme_tarihi DESC");
    $paymentsStmt->execute(['rez_id' => $rezervasyon_id]);
    return $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_payment = 0.00;
$kalan_odeme = 0.00;
$payments = [];

if ($rezervasyon) {
    $payments = getPaymentsForReservation($rezervasyon_id, $pdo);
    foreach ($payments as $pay) {
        $total_payment += (float)$pay['odeme_miktari'];
    }
    $kalan_odeme = (float)$rezervasyon['fiyat'] - $total_payment;
    // Ensure $kalan_odeme is not negative
    if ($kalan_odeme < 0) {
        $kalan_odeme = 0.00;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    if ($rezervasyon) {
        $odeme_turu = $_POST['odeme_turu'] ?? '';
        $odeme_yapan = trim($_POST['odeme_yapan'] ?? '');
        $odeme_miktari = $_POST['odeme_miktari'] ?? '';

        // Validasyon
        if (!in_array($odeme_turu, ['Nakit', 'IBAN'])) {
            $errors[] = "Geçerli bir ödeme türü seçiniz.";
        }
        if (empty($odeme_yapan)) {
            $errors[] = "Ödeme yapan bilgisi boş bırakılamaz.";
        }
        if (!is_numeric($odeme_miktari) || $odeme_miktari <= 0) {
            $errors[] = "Geçerli bir ödeme miktarı giriniz.";
        }

        $odeme_miktari = (float)$odeme_miktari;

        // Rezervasyonun kalan ödemesini tekrar hesapla
        $kalan_odeme = (float)$rezervasyon['fiyat'] - $total_payment;
        if ($kalan_odeme < 0) {
            $kalan_odeme = 0.00;
        }

        if ($odeme_miktari > $kalan_odeme) {
            $errors[] = "Girdiğiniz ödeme miktarı kalan ödemeden fazladır. Kalan ödeme: " . number_format($kalan_odeme, 2) . " TL.";
        }

        if (empty($errors)) {
            try {
                // Ödemeyi veritabanına ekle
                $payStmt = $pdo->prepare("
                    INSERT INTO odeme (rezervasyon_id, odeme_turu, odeme_yapan, odeme_miktari, odeme_tarihi) 
                    VALUES (:rez_id, :odeme_turu, :odeme_yapan, :odeme_miktari, NOW())
                ");
                $payStmt->execute([
                    'rez_id' => $rezervasyon_id,
                    'odeme_turu' => $odeme_turu,
                    'odeme_yapan' => $odeme_yapan,
                    'odeme_miktari' => $odeme_miktari
                ]);

                // Başarılı mesajı ayarla
                $message = "Ödeme başarıyla alındı.";

                // Ödeme işlemi sonrası ödemeleri yeniden çek
                $payments = getPaymentsForReservation($rezervasyon_id, $pdo);
                $total_payment = 0.00;
                foreach ($payments as $pay) {
                    $total_payment += (float)$pay['odeme_miktari'];
                }
                $kalan_odeme = (float)$rezervasyon['fiyat'] - $total_payment;
                if ($kalan_odeme < 0) {
                    $kalan_odeme = 0.00;
                }

            } catch (PDOException $e) {
                $errors[] = "Ödeme işlemi sırasında bir hata oluştu: " . $e->getMessage();
            }
        }
    } else {
        $errors[] = "Geçerli bir rezervasyon seçmediniz.";
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <h2>Ödeme Al</h2>

    <!-- Başarılı Mesaj -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($message) ?>
        </div>
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

    <?php if ($rezervasyon): ?>
        <div class="card mb-4">
            <div class="card-header">
                Rezervasyon Bilgileri
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?= htmlspecialchars($rezervasyon['id']) ?></p>
                <p><strong>Saha:</strong> <?= htmlspecialchars($rezervasyon['saha_adi']) ?></p>
                <p><strong>Tarih:</strong> <?= htmlspecialchars($rezervasyon['tarih']) ?></p>
                <p><strong>Seans:</strong> <?= htmlspecialchars(substr($rezervasyon['baslangic_saati'],0,5) . " - " . substr($rezervasyon['bitis_saati'],0,5)) ?></p>
                <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($rezervasyon['ad_soyad']) ?></p>
                <p><strong>Telefon:</strong> <?= htmlspecialchars($rezervasyon['telefon']) ?></p>
                <p><strong>Kapora:</strong> <?= number_format($rezervasyon['kapora'], 2) ?> TL</p>
                <p><strong>Toplam Ücret:</strong> <?= number_format($rezervasyon['fiyat'], 2) ?> TL</p>
                <p><strong>Toplam Alınan Ödeme:</strong> <?= number_format($total_payment, 2) ?> TL</p>
                <p><strong>Kalan Ödeme:</strong> <?= number_format($kalan_odeme, 2) ?> TL</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Ödeme Formu
            </div>
            <div class="card-body">
                <form method="post" action="/views/payments/take_payment.php?rez_id=<?= $rezervasyon_id ?>">
                    <input type="hidden" name="action" value="pay">
                    <div class="mb-3">
                        <label for="odeme_turu" class="form-label">Ödeme Türü</label>
                        <select class="form-select" id="odeme_turu" name="odeme_turu" required>
                            <option value="">Seçiniz</option>
                            <option value="Nakit" <?= (isset($_POST['odeme_turu']) && $_POST['odeme_turu'] == 'Nakit') ? 'selected' : '' ?>>Nakit</option>
                            <option value="IBAN" <?= (isset($_POST['odeme_turu']) && $_POST['odeme_turu'] == 'IBAN') ? 'selected' : '' ?>>IBAN</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odeme_yapan" class="form-label">Ödeme Yapan</label>
                        <input type="text" class="form-control" id="odeme_yapan" name="odeme_yapan" value="<?= htmlspecialchars($_POST['odeme_yapan'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="odeme_miktari" class="form-label">Ödeme Miktarı</label>
                        <input type="number" step="0.01" class="form-control" id="odeme_miktari" name="odeme_miktari" value="<?= htmlspecialchars($_POST['odeme_miktari'] ?? '') ?>" required min="0.01" max="<?= htmlspecialchars($kalan_odeme) ?>">
                        <small class="form-text text-muted">Kalan ödeme: <?= number_format($kalan_odeme, 2) ?> TL</small>
                    </div>
                    <button type="submit" class="btn btn-success">Ödeme Al</button>
                    <a href="/views/payments/payments.php" class="btn btn-secondary">İptal</a>
                </form>
            </div>
        </div>

        <!-- Ödeme Geçmişi -->
        <div class="card">
            <div class="card-header">
                Ödeme Geçmişi
            </div>
            <div class="card-body">
                <?php if (is_array($payments) && count($payments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
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
                                        <td><?= number_format((float)$pay['odeme_miktari'], 2) ?> TL</td>
                                        <td><?= htmlspecialchars($pay['odeme_tarihi']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Bu rezervasyon için henüz ödeme alınmamıştır.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
