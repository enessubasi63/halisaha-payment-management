<?php
// sidebar.php
// -----------------------------------------------------
// Admin paneline özel bir yan menü. Özellikle geniş ekranlarda yatay menü yerine
// sol tarafta sabit bir navigasyon sunar. Responsive tasarım ile mobilde gizlenebilir.
// Bu dosya dashboard sayfalarında include edilebilir.

// Bu sidebar tipik olarak dashboard sayfalarında, header ve footer arasında bir layout ile kullanılır.
// Örneğin, sidebar + içerik alanı gibi bir yapı kurulabilir.
?>

<div class="sidebar bg-light border-end" style="width: 250px; min-height: 100vh; position: fixed; top:0; left:0;">
    <div class="sidebar-header p-3 bg-white border-bottom">
        <h5 class="mb-0">Yönetim Paneli</h5>
    </div>
    <nav class="nav flex-column p-3">
        <a class="nav-link" href="/views/dashboard/index.php">📊 Dashboard</a>
        <a class="nav-link" href="/views/dashboard/reservations.php">📅 Rezervasyonlar</a>
        <a class="nav-link" href="/views/settings/fields.php">🏟️ Saha Ayarları</a>
        <a class="nav-link" href="/views/settings/users.php">👤 Kullanıcı Yönetimi</a>
        <a class="nav-link" href="/views/dashboard/reports.php">📈 Raporlar</a>
        <a class="nav-link text-danger" href="/views/auth/logout.php">🚪 Çıkış Yap</a>
    </nav>
</div>

<!-- Sidebar kullanıldığı sayfalarda içerik alanının sol tarafında boşluk bırakılmalı -->
<style>
    /* Sidebar kullanıldığında içerik alanının sola yaslanmaması için margin veriyoruz. */
    .content-area {
        margin-left: 250px; /* Sidebar genişliği kadar boşluk */
        padding: 2rem;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: static;
            width: 100%;
            border-end: none;
            border-bottom: 1px solid #ddd;
        }
        .content-area {
            margin-left: 0;
        }
    }
</style>
