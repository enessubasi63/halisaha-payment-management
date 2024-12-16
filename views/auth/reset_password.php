<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

// Burada basit bir örnek gösterelim. Gerçek senaryoda e-posta onayı ya da güvenlik soruları gerekli olabilir.
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($username && $new_password && $new_password === $confirm_password) {
        // Kullanıcıyı kontrol et
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Şifre güncelle
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $update->execute(['password' => $hashed, 'id' => $user['id']]);
            $message = 'Şifre başarıyla güncellendi. <a href="/views/auth/login.php">Giriş yap</a>';
        } else {
            $message = 'Kullanıcı bulunamadı.';
        }
    } else {
        $message = 'Bilgileri kontrol edin. Şifreler eşleşmiyor veya alanlar boş.';
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>
<div class="container mt-5">
    <h2>Şifre Sıfırlama</h2>
    <?php if($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" style="max-width:400px;">
        <div class="mb-3">
            <label>Kullanıcı Adı</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Yeni Şifre</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Yeni Şifre (Tekrar)</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button class="btn btn-primary">Şifreyi Sıfırla</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
