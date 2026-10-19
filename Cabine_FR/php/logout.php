<?php
include "conn.php";

session_start();
session_destroy();

header("Location: /CABINE/pages/login.php");
    exit();


?>