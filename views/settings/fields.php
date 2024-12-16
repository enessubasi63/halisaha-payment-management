<?php
// views/settings/fields.php

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
$field_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Rezervasyon Ekleme (Add)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $saha_adi = trim($_POST['saha_adi'] ?? '');
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $fiyat = trim($_POST['fiyat'] ?? '');

    // Validasyon
    if (empty($saha_adi)) {
        $errors[] = "Saha adı boş bırakılamaz.";
    }
    if (empty($fiyat) || !is_numeric($fiyat) || $fiyat < 0) {
        $errors[] = "Geçerli bir fiyat giriniz.";
    }

    // Saha adının benzersiz olup olmadığını kontrol et
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM sahalar WHERE saha_adi = :saha_adi");
    $checkStmt->execute(['saha_adi' => $saha_adi]);
    $count = $checkStmt->fetch()['count'];
    if ($count > 0) {
        $errors[] = "Bu saha adı zaten mevcut. Lütfen başka bir isim deneyin.";
    }

    if (empty($errors)) {
        // Sahayı ekle
        $stmt = $pdo->prepare("INSERT INTO sahalar (saha_adi, aktif, fiyat) VALUES (:saha_adi, :aktif, :fiyat)");
        $stmt->execute([
            'saha_adi' => $saha_adi,
            'aktif' => $aktif,
            'fiyat' => $fiyat
        ]);

        $message = "Saha başarıyla eklendi.";
        // PRG deseni ile sayfa yeniden yönlendirme
        header('Location: /views/settings/fields.php?success=1');
        exit;
    }
}

// Rezervasyon Düzenleme (Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $saha_adi = trim($_POST['saha_adi'] ?? '');
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $fiyat = trim($_POST['fiyat'] ?? '');

    // Validasyon
    if (empty($saha_adi)) {
        $errors[] = "Saha adı boş bırakılamaz.";
    }
    if (empty($fiyat) || !is_numeric($fiyat) || $fiyat < 0) {
        $errors[] = "Geçerli bir fiyat giriniz.";
    }

    // Saha adının benzersiz olup olmadığını kontrol et (kendi dışındaki kayıtlar için)
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM sahalar WHERE saha_adi = :saha_adi AND id != :id");
    $checkStmt->execute(['saha_adi' => $saha_adi, 'id' => $id]);
    $count = $checkStmt->fetch()['count'];
    if ($count > 0) {
        $errors[] = "Bu saha adı zaten mevcut. Lütfen başka bir isim deneyin.";
    }

    if (empty($errors)) {
        // Sahayı güncelle
        $stmt = $pdo->prepare("UPDATE sahalar SET saha_adi = :saha_adi, aktif = :aktif, fiyat = :fiyat WHERE id = :id");
        $stmt->execute([
            'saha_adi' => $saha_adi,
            'aktif' => $aktif,
            'fiyat' => $fiyat,
            'id' => $id
        ]);

        $message = "Saha başarıyla güncellendi.";
        // PRG deseni ile sayfa yeniden yönlendirme
        header('Location: /views/settings/fields.php?success=2');
        exit;
    }
}

// Rezervasyon Silme (Delete)
if ($action === 'delete' && $field_id > 0) {
    // Önce, silinecek sahanın mevcut rezervasyonlarını kontrol et
    $checkRezervasyon = $pdo->prepare("SELECT COUNT(*) as count FROM rezervasyonlar WHERE saha_id = :saha_id");
    $checkRezervasyon->execute(['saha_id' => $field_id]);
    $rezCount = $checkRezervasyon->fetch()['count'];

    if ($rezCount > 0) {
        $errors[] = "Bu sahaya ait mevcut rezervasyonlar bulunduğu için silme işlemi gerçekleştirilemiyor.";
    } else {
        // Sahayı sil
        $delStmt = $pdo->prepare("DELETE FROM sahalar WHERE id = :id");
        $delStmt->execute(['id' => $field_id]);

        $message = "Saha başarıyla silindi.";
        // PRG deseni ile sayfa yeniden yönlendirme
        header('Location: /views/settings/fields.php?success=3');
        exit;
    }
}

// Başarılı mesajları göster
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $message = "Saha başarıyla eklendi.";
    } elseif ($_GET['success'] == 2) {
        $message = "Saha başarıyla güncellendi.";
    } elseif ($_GET['success'] == 3) {
        $message = "Saha başarıyla silindi.";
    }
}

// Tüm sahaları listeleme
$fields = $pdo->query("SELECT * FROM sahalar ORDER BY saha_adi ASC")->fetchAll(PDO::FETCH_ASSOC);

// Düzenleme formu için saha bilgilerini çekme
$editReservation = null;
if ($action === 'edit' && $field_id > 0) {
    $editStmt = $pdo->prepare("SELECT * FROM sahalar WHERE id = :id LIMIT 1");
    $editStmt->execute(['id' => $field_id]);
    $editReservation = $editStmt->fetch(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container mt-4">
    <h2>Saha Yönetimi</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Yeni Saha Ekleme Formu -->
   <!-- <div class="card mb-4">
        <div class="card-header">
            Yeni Saha Ekle
        </div>
        <div class="card-body">
            <form method="post" action="/views/settings/fields.php">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label for="saha_adi" class="form-label">Saha Adı</label>
                    <input type="text" class="form-control" id="saha_adi" name="saha_adi" required>
                </div>
                <div class="mb-3">
                    <label for="fiyat" class="form-label">Fiyat (TL)</label>
                    <input type="number" step="0.01" class="form-control" id="fiyat" name="fiyat" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="aktif" name="aktif" checked>
                    <label class="form-check-label" for="aktif">
                        Aktif
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Ekle</button>
            </form>
        </div>
    </div> -->

    <!-- Sahaların Listesi -->
    <div class="card">
        <div class="card-header">
            Mevcut Sahalar
        </div>
        <div class="card-body">
            <?php if (count($fields) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Saha Adı</th>
                            <th>Fiyat (TL)</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?= htmlspecialchars($field['id']) ?></td>
                                <td><?= htmlspecialchars($field['saha_adi']) ?></td>
                                <td><?= number_format($field['fiyat'], 2) ?> TL</td>
                                <td><?= $field['aktif'] ? 'Aktif' : 'Pasif' ?></td>
                                <td>
                                    <a href="/views/settings/fields.php?action=edit&id=<?= $field['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                    <a href="/views/settings/fields.php?action=delete&id=<?= $field['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu sahayı silmek istediğinizden emin misiniz?');">Sil</a>
                                </td>
                            </tr>
                            <?php if ($action === 'edit' && $field_id == $field['id'] && $editReservation): ?>
                                <tr>
                                    <td colspan="5" class="p-0">
                                        <div class="card card-body">
                                            <h5>Düzenleme Formu</h5>
                                            <form method="post" action="/views/settings/fields.php">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($editReservation['id']) ?>">
                                                <div class="mb-3">
                                                    <label for="saha_adi_edit" class="form-label">Saha Adı</label>
                                                    <input type="text" class="form-control" id="saha_adi_edit" name="saha_adi" value="<?= htmlspecialchars($editReservation['saha_adi']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="fiyat_edit" class="form-label">Fiyat (TL)</label>
                                                    <input type="number" step="0.01" class="form-control" id="fiyat_edit" name="fiyat" value="<?= htmlspecialchars($editReservation['fiyat']) ?>" required>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" value="1" id="aktif_edit" name="aktif" <?= $editReservation['aktif'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="aktif_edit">
                                                        Aktif
                                                    </label>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Güncelle</button>
                                                <a href="/views/settings/fields.php" class="btn btn-secondary">İptal</a>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Henüz eklenmiş saha bulunmamaktadır.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
