<?php
/**
 * db.php
 * ------------------------------------
 * Bu dosya MySQL veritabanına güvenli bir PDO bağlantısı sağlar.
 * Veritabanı bilgileri üretim ortamında güvenli bir şekilde saklanmalıdır.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'u644550780_halisaha');
define('DB_USER', 'u644550780_halisaha1');
define('DB_PASS', 'Subasi_Enes63');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hata ayıklamayı kolaylaştırır.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Varsayılan fetch modunu düzenler.
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek prepared statement kullanımı.
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Üretim ortamında hata mesajını loglamak ve kullanıcıya göstermemek önerilir.
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
