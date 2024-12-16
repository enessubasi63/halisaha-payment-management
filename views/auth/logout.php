<?php
require_once __DIR__ . '/../../config/session.php';
// Oturumu sonlandır
session_unset();
session_destroy();
// Giriş sayfasına yönlendir
header('Location: /views/auth/login.php');
exit;
