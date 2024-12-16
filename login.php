<?php
// login.php
// -------------------------------------------
// Bu dosya doğrudan login sayfasına yönlendirir.
// Aslında views/auth/login.php kullanılıyor.
// Bu dosyayı kısaca redirect amacıyla kullanıyoruz.

header('Location: /views/auth/login.php');
exit;
