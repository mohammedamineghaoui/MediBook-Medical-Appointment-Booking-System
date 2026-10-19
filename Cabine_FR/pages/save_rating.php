<?php
session_start();
include('../php/conn.php');

$appointment_id = (int)($_POST['appointment_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = $_POST['comment'] ?? '';

if ($rating < 1 || $rating > 5) {
    $_SESSION['message'] = "Please select a valid rating (1–5 stars) and try again.";
    $_SESSION['message_type'] = "error";
    header("Location: patient_dashboard.php");
    exit;
}

$sql_check = "SELECT rating_id FROM ratings WHERE appointment_id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $sql_update_rating = "UPDATE ratings
                          SET rating = ?, comment = ?
                          WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql_update_rating);
    $stmt->bind_param("isi", $rating, $comment, $appointment_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Your rating was updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Sorry, we couldn’t update your rating right now.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $sql_insert = "INSERT INTO ratings (appointment_id, rating, comment)
                   VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("iis", $appointment_id, $rating, $comment);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Thanks! Your rating was submitted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Sorry, we couldn’t submit your rating right now.";
        $_SESSION['message_type'] = "error";
    }
}

header("Location: patient_dashboard.php");
exit;
?>
