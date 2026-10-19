<?php
include 'require_doctor.php';
include '../php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: new_appointment.php?error=save_failed');
    exit;
}

$doctor_id = isset($_SESSION['doctor_id'])
    ? (int) $_SESSION['doctor_id']
    : (isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : 0);
$patient_id = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
$date = $_POST['appointment_date'] ?? '';
$time = $_POST['appointment_time'] ?? '';

if (!$doctor_id || !$patient_id || !$date || !$time) {
    header('Location: new_appointment.php?error=missing_fields');
    exit;
}

$date_object = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_object || $date_object->format('Y-m-d') !== $date) {
    header('Location: new_appointment.php?error=missing_fields');
    exit;
}

if (strlen($time) === 5) {
    $time .= ':00';
}

$appointment_datetime = $date . ' ' . $time;

if (strtotime($appointment_datetime) <= time()) {
    header('Location: new_appointment.php?error=past_time');
    exit;
}

$patient_sql = "SELECT user_id FROM patient WHERE user_id = ?";
$patient_stmt = $conn->prepare($patient_sql);
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();

if ($patient_result->num_rows === 0) {
    header('Location: new_appointment.php?error=invalid_patient');
    exit;
}

$weekday_name = date('l', strtotime($date));

$schedule_sql = "SELECT work_start_time, work_end_time, break_start_time, break_end_time, is_working_day
                 FROM doctor_schedule
                 WHERE doctor_id = ? AND day_of_week = ?";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("is", $doctor_id, $weekday_name);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();

if (!$schedule || (int) $schedule['is_working_day'] === 0) {
    header('Location: new_appointment.php?error=not_working_day');
    exit;
}

$time_ts = strtotime($time);
$work_start_ts = strtotime($schedule['work_start_time']);
$work_end_ts = strtotime($schedule['work_end_time']);

if ($time_ts < $work_start_ts || $time_ts >= $work_end_ts) {
    header('Location: new_appointment.php?error=out_of_working_hours');
    exit;
}

if (!empty($schedule['break_start_time']) && !empty($schedule['break_end_time'])) {
    $break_start_ts = strtotime($schedule['break_start_time']);
    $break_end_ts = strtotime($schedule['break_end_time']);

    if ($time_ts >= $break_start_ts && $time_ts < $break_end_ts) {
        header('Location: new_appointment.php?error=break_time');
        exit;
    }
}

$duplicate_slot_sql = "SELECT COUNT(*) AS total
                       FROM appointments
                       WHERE doctor_id = ?
                       AND appointment_time = ?
                       AND status = 'scheduled'";
$duplicate_slot_stmt = $conn->prepare($duplicate_slot_sql);
$duplicate_slot_stmt->bind_param("is", $doctor_id, $appointment_datetime);
$duplicate_slot_stmt->execute();
$duplicate_slot = $duplicate_slot_stmt->get_result()->fetch_assoc();

if ((int) $duplicate_slot['total'] > 0) {
    header('Location: new_appointment.php?error=already_booked');
    exit;
}

$patient_same_day_sql = "SELECT COUNT(*) AS total
                         FROM appointments
                         WHERE patient_id = ?
                         AND DATE(appointment_time) = ?
                         AND status = 'scheduled'";
$patient_same_day_stmt = $conn->prepare($patient_same_day_sql);
$patient_same_day_stmt->bind_param("is", $patient_id, $date);
$patient_same_day_stmt->execute();
$patient_same_day = $patient_same_day_stmt->get_result()->fetch_assoc();

if ((int) $patient_same_day['total'] > 0) {
    header('Location: new_appointment.php?error=patient_has_appointment');
    exit;
}

$insert_sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_time, status)
               VALUES (?, ?, ?, 'scheduled')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iis", $patient_id, $doctor_id, $appointment_datetime);

if ($insert_stmt->execute()) {
    header('Location: new_appointment.php?success=1');
    exit;
}

header('Location: new_appointment.php?error=save_failed');
exit;
?>
