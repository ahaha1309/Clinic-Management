<?php
session_start();

/* ==========================
   CLEAR SESSION DATA
========================== */

// Xoá toàn bộ biến session
$_SESSION = [];

// Huỷ session hiện tại
session_destroy();

// Xoá cookie session (an toàn hơn)
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

/* ==========================
   REDIRECT
========================== */

header("Location: index.php");
exit;
?>