<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || !(int) $_SESSION['admin_id']) {
    header('Location: Admin_auth.php');
    exit;
}
?>
