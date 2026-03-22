<?php
session_start();

require_once 'config/database.php';
require_once 'config/auth.php';

if (isLoggedIn()) {
    auditLog('logout', 'users', $_SESSION['user_id']);
}

session_destroy();
header('Location: login.php');
exit();
?>
