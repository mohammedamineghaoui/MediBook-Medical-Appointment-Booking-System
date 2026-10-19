<?php
header('Content-Type: application/json');
include 'conn.php';

$doctor_id = (int)$_GET['doctor_id'];
$date = $_GET['date']; // YYYY-MM-DD

if (!$doctor_id || !$date) {
    echo json_encode(['available' => [], 'unavailable' => []]);
    exit();
}

// يوم الأسبوع (Sunday = 0)
$weekday = date('l', strtotime($date)); 

/* 1) جلب جدول العمل لهذا اليوم */
$sql_sched = "SELECT * FROM doctor_schedule 
              WHERE doctor_id = ? AND day_of_week = ?";
$stmt = $conn->prepare($sql_sched);
$stmt->bind_param("is", $doctor_id, $weekday);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

if (!$schedule || $schedule['is_working_day'] == 0) {
    // الطبيب لا يعمل هذا اليوم
    echo json_encode(['available' => [], 'unavailable' => 'closed']);
    exit();
}

$work_start = $schedule['work_start_time'];  // 08:30:00
$work_end   = $schedule['work_end_time'];    // 16:00:00
$break_start = $schedule['break_start_time']; // ممكن null
$break_end   = $schedule['break_end_time'];

/* 2) جلب المواعيد المحجوزة */
$sql2 = "SELECT appointment_time FROM appointments
         WHERE doctor_id = ? AND DATE(appointment_time)=?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("is", $doctor_id, $date);
$stmt2->execute();
$res2 = $stmt2->get_result();

$booked = [];
while ($row = $res2->fetch_assoc()) {
    $booked[] = date('H:i', strtotime($row['appointment_time']));
}

/* 3) توليد كل time slots داخل وقت العمل */
$slots = [];
$start = new DateTime($work_start);
$end   = new DateTime($work_end);

while ($start < $end) {
    $slots[] = $start->format('H:i');
    $start->modify('+30 minutes');
}

/* 4) حساب أوقات الاستراحة */
$break_slots = [];
if ($break_start && $break_end) {
    $b_start = new DateTime($break_start);
    $b_end   = new DateTime($break_end);

    while ($b_start < $b_end) {
        $break_slots[] = $b_start->format('H:i');
        $b_start->modify('+30 minutes');
    }
}

/* 5) دمج المحجوز + الاستراحة */
$unavailable = array_unique(array_merge($booked, $break_slots));

echo json_encode([
    'available' => $slots,
    'unavailable' => $unavailable
]);