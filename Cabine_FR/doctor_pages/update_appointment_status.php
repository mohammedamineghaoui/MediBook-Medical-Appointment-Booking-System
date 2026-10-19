<?php
include 'require_doctor.php';
include '../php/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $appointment_id = (int) $appointment_id;
    $status = strtolower(trim($_POST['status'] ?? ''));

    $allowed_statuses = ['scheduled', 'completed', 'cancelled'];

    if (!$appointment_id || !in_array($status, $allowed_statuses, true)) {
        header("Location: appointments.php?error=invalid_data");
        exit;
    }

    if ($status === 'scheduled') {
        $time_check_sql = "SELECT appointment_time FROM appointments WHERE appointment_id = ?";
        $time_check_stmt = $conn->prepare($time_check_sql);
        $time_check_stmt->bind_param("i", $appointment_id);
        $time_check_stmt->execute();
        $time_check_result = $time_check_stmt->get_result()->fetch_assoc();

        if (!$time_check_result || strtotime($time_check_result['appointment_time']) <= time()) {
            header("Location: appointments.php?error=past_scheduled");
            exit;
        }
    }

    $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $appointment_id);

    if ($stmt->execute()) {
        header("Location: appointments.php?updated=1");
        exit;
    } else {
        header("Location: appointments.php?error=update_failed");
        exit;
    }
} else {
    header("Location: appointments.php?error=invalid_request");
    exit;
}
?>
