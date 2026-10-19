<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabinedb";
$conn = mysqli_connect($servername, $username, $password, $dbname);
if($conn->connect_error){
    die("error conacting : ".$conn->connect_error );
}
?>