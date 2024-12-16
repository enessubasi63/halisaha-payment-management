<?php
/**
 * session.php
 * ------------------------------------
 * PHP oturumlarını güvenli biçimde başlatır ve yönetir.
 * Bu dosya tüm sayfalardan include edilmelidir ki oturum yönetimi tutarlı olsun.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Güvenli oturum ayarları
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0, // Tarayıcı kapanana kadar geçerli
        'path'     => $cookieParams['path'],
        'domain'   => 'halisaha.enessubasi.com.tr', // Domain adı
        'secure'   => true,  // HTTPS kullanıyorsanız true yapın, aksi halde false
        'httponly' => true,  // JavaScript erişimini engeller
        'samesite' => 'Strict'
    ]);

    session_name('halisaha_session'); // Oturum adı
    session_start();
    session_regenerate_id(true); // Oturum sabitleme saldırılarına karşı koruma.
}
