<?php
// 404.php
// -------------------------------------------
// Bu dosya bulunamayan sayfalar için özel hata sayfası gösterir.
// Kullanıcı dostu bir mesaj ve gerekirse anasayfaya dönüş linki içerir.

require_once __DIR__ . '/templates/header.php';
?>
<div class="container mt-5 text-center">
    <h1 class="display-4">404 - Sayfa Bulunamadı</h1>
    <p class="lead">Aradığınız sayfa mevcut değil veya kaldırılmış olabilir.</p>
    <a href="/" class="btn btn-primary">Ana Sayfaya Dön</a>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
