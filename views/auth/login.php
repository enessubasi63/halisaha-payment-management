<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

// Eğer kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: /views/dashboard/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username AND aktif = 1 LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Oturum değişkenlerini ayarla
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header('Location: /views/dashboard/index.php');
            exit;
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    } else {
        $error = 'Lütfen kullanıcı adı ve şifre giriniz.';
    }
}

// Header olmadan login sayfası: Giriş öncesi minimal tasarım
require_once __DIR__ . '/../../templates/header.php'; 
?>
<div class="login-page">
    <div class="login-box">
        <h2>Giriş Yap</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Giriş Yap</button>
            <div class="forgot-password">
                <a href="/views/auth/reset_password.php">Şifremi Unuttum</a>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
