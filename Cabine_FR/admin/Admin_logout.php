<?php
session_start();
unset($_SESSION['admin_id'], $_SESSION['full_name'], $_SESSION['email_admin']);
header('Location: Admin_auth.php');
exit;
