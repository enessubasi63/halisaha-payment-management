<?php
// index.php
// -------------------------------------------
// Bu dosya uygulamanın giriş noktasıdır.
// Eğer kullanıcı giriş yapmışsa dashboard'a yönlendirir, giriş yapmamışsa login'e yönlendirir.

require_once __DIR__ . '/config/session.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: /views/dashboard/index.php');
    exit;
} else {
    header('Location: /views/auth/login.php');
    exit;
}
