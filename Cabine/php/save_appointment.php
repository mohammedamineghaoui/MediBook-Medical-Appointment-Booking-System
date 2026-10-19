<?php

session_start();
include 'conn.php'; // تأكد أن المسار صحيح

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// استلام البيانات من الفورم المخفي
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$date      = $_POST['appointment_date'] ?? null; // YYYY-MM-DD
$time      = $_POST['appointment_time'] ?? null; // HH:MM

if (!$doctor_id || !$date || !$time) {
    die("Missing required fields.");
}

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$patient_id = (int) $_SESSION['user_id'];

// لو الوقت مثل "09:30" نحوله "09:30:00"
if (strlen($time) === 5) {
    $time .= ':00';
}

// ندمج التاريخ مع الوقت في حقل DATETIME
$appointment_datetime = $date . ' ' . $time;

/* 1) التأكد أن الطبيب يعمل في هذا اليوم وهذا الوقت */

// نحدد اليوم باسم (Sunday, Monday, ...)
$weekdayName = date('l', strtotime($date));

// نجيب جدول عمل الطبيب من doctor_schedule
$sched_sql = "SELECT * FROM doctor_schedule 
              WHERE doctor_id = ? AND day_of_week = ?";
$sched_stmt = $conn->prepare($sched_sql);
$sched_stmt->bind_param("is", $doctor_id, $weekdayName);
$sched_stmt->execute();
$schedule = $sched_stmt->get_result()->fetch_assoc();

if (!$schedule || (int)$schedule['is_working_day'] === 0) {
    header("Location: ../pages/appointment.php?doctor_id=$doctor_id&error=not_working_day");
    exit();
}

// نتحقق أن الوقت داخل وقت العمل
$work_start   = $schedule['work_start_time'];
$work_end     = $schedule['work_end_time'];
$break_start  = $schedule['break_start_time'];
$break_end    = $schedule['break_end_time'];

$time_ts        = strtotime($time);
$work_start_ts  = strtotime($work_start);
$work_end_ts    = strtotime($work_end);

if ($time_ts < $work_start_ts || $time_ts >= $work_end_ts) {
    header("Location: ../pages/appointment.php?doctor_id=$doctor_id&error=out_of_working_hours");
    exit();
}

// نتحقق من وقت الاستراحة
if (!empty($break_start) && !empty($break_end)) {
    $break_start_ts = strtotime($break_start);
    $break_end_ts   = strtotime($break_end);

    if ($time_ts >= $break_start_ts && $time_ts < $break_end_ts) {
        header("Location: ../pages/appointment.php?doctor_id=$doctor_id&error=break_time");
        exit();
    }
}

/* 2) منع حجز نفس الوقت مرتين لنفس الطبيب */
$check_sql = "SELECT COUNT(*) AS cnt
              FROM appointments
              WHERE doctor_id = ?
                AND appointment_time = ?
                AND status = 'scheduled'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $doctor_id, $appointment_datetime);
$check_stmt->execute();
$check = $check_stmt->get_result()->fetch_assoc();

if ($check['cnt'] > 0) {
    header("Location: ../pages/appointment.php?doctor_id=$doctor_id&error=already_booked");
    exit();
}

/* 3) منع المريض من أخذ موعدين scheduled في نفس اليوم */
$patient_check_sql = "SELECT COUNT(*) AS cnt
                      FROM appointments
                      WHERE patient_id = ?
                        AND DATE(appointment_time) = ?
                        AND status = 'scheduled'";
$patient_check_stmt = $conn->prepare($patient_check_sql);
$patient_check_stmt->bind_param("is", $patient_id, $date);
$patient_check_stmt->execute();
$patient_check = $patient_check_stmt->get_result()->fetch_assoc();

if ($patient_check['cnt'] > 0) {
    header("Location: ../pages/appointment-slots.php?doctor_id=$doctor_id&error=patient_has_appointment");
    exit();
}

/* 4) إدخال الموعد في قاعدة البيانات */
$insert_sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_time, status)
               VALUES (?, ?, ?, 'scheduled')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iis", $patient_id, $doctor_id, $appointment_datetime);

if ($insert_stmt->execute()) {
    header("Location: ../pages/patient_dashboard.php?success=1");
    exit();
} else {
    echo "Error saving appointment: " . $conn->error;
}
?>