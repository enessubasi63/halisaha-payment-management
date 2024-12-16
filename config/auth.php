<?php
/**
 * auth.php
 * ------------------------------------
 * Bu dosya kullanıcının oturum açıp açmadığını kontrol eder.
 * Eğer kullanıcı giriş yapmadıysa login sayfasına yönlendirir.
 * Bu dosya, her sayfanın en üstünde include edilmelidir.
 */

// Oturum yönetim dosyasını çağır
require_once __DIR__ . '/session.php';

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Kullanıcı giriş yapmamışsa login sayfasına yönlendir
    header('Location: /views/auth/login.php'); 
    exit;
}
