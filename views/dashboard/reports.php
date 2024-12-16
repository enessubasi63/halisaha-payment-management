<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Örnek rapor: Son 7 gün içindeki toplam rezervasyon sayısı
$stmt = $pdo->query("SELECT COUNT(*) AS son_7_gun FROM rezervasyonlar WHERE created_at > (NOW() - INTERVAL 7 DAY)");
$son7gun = $stmt->fetch()['son_7_gun'];

require_once __DIR__ . '/../../templates/header.php';
?>
<h2>Raporlar</h2>
<div class="alert alert-info">
    Son 7 gün içinde yapılan rezervasyon sayısı: <?= $son7gun ?>
</div>
<!-- Buraya ek raporlar, grafikler (Chart.js gibi kütüphaneler kullanılarak) eklenebilir. -->
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
