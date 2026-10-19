<?php
include "conn.php";

session_start();

if (isset($_POST['cancel']) && isset($_POST['appointment_id'])) {

    $appointment_id = (int) $_POST['appointment_id'];
    $sql = "UPDATE appointments 
            SET status = 'cancelled' 
            WHERE appointment_id = ?  
            AND status = 'scheduled'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    // ✅ Prevent refresh problem
    header("Location: ../pages/patient_dashboard.php");
    exit();
}else{
    echo "errour";
    
}


?>