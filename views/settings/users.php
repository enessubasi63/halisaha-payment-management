<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

$message = '';

// Yeni kullanıcı ekleme örneği
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $full_name && $password) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name) VALUES (:u, :p, :f)");
        $stmt->execute([
            'u' => $username,
            'p' => $hashed,
            'f' => $full_name
        ]);
        $message = "Kullanıcı eklendi.";
    } else {
        $message = "Tüm alanları doldurun.";
    }
}

$users = $pdo->query("SELECT id, username, full_name, aktif, created_at FROM users ORDER BY created_at DESC")->fetchAll();
require_once __DIR__ . '/../../templates/header.php';
?>
<h2>Kullanıcı Yönetimi</h2>
<?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-md-4">
        <h4>Yeni Kullanıcı Ekle</h4>
        <form method="post">
            <input type="hidden" name="action" value="add_user">
            <div class="mb-3">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Ad Soyad</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Şifre</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary">Ekle</button>
        </form>
    </div>
    <div class="col-md-8">
        <h4>Mevcut Kullanıcılar</h4>
        <table class="table table-striped">
            <thead>
                <tr><th>ID</th><th>Kullanıcı Adı</th><th>Ad Soyad</th><th>Durum</th><th>Oluşturma Tarihi</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= $user['aktif'] ? 'Aktif' : 'Pasif' ?></td>
                    <td><?= $user['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
