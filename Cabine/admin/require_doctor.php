<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['doctor_id']) || !(int) $_SESSION['doctor_id']) {
    header('Location: doctor_auth.php');
    exit;
}
?>
