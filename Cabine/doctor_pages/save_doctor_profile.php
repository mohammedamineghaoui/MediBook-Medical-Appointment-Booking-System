<?php
include 'require_doctor.php';
$doctor_id = $_SESSION['doctor_id'] ?? 1;
include '../php/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit_profile.php?error=invalid_request');
    exit;
}

$doctor_check_stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ?");
$doctor_check_stmt->bind_param("i", $doctor_id);
$doctor_check_stmt->execute();
$doctor_exists = $doctor_check_stmt->get_result()->fetch_assoc();

if (!$doctor_exists) {
    header('Location: edit_profile.php?error=doctor_not_found');
    exit;
}

$doctor_columns = [];
$columns_result = $conn->query("SHOW COLUMNS FROM doctors");
while ($column = $columns_result->fetch_assoc()) {
    $doctor_columns[] = $column['Field'];
}

$update_fields = [];
$update_values = [];
$update_types = '';

$field_map = [
    'full_name' => 's',
    'email' => 's',
    'phone' => 's',
    'experience' => 's',
    'location' => 's',
    'specialty_id' => 'i',
];

foreach ($field_map as $field => $type) {
    if (!in_array($field, $doctor_columns, true)) {
        continue;
    }

    if ($field === 'specialty_id') {
        $value = isset($_POST[$field]) && $_POST[$field] !== '' ? (int) $_POST[$field] : null;
    } else {
        $value = trim($_POST[$field] ?? '');
    }

    if ($field === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        header('Location: edit_profile.php?error=invalid_email');
        exit;
    }

    if ($field === 'email' && $value !== '') {
        $check_stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE email = ? AND doctor_id != ?");
        $check_stmt->bind_param("si", $value, $doctor_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            header('Location: edit_profile.php?error=duplicate_email');
            exit;
        }
    }

    if ($field === 'phone' && $value !== '') {
        $check_stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE phone = ? AND doctor_id != ?");
        $check_stmt->bind_param("si", $value, $doctor_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            header('Location: edit_profile.php?error=duplicate_phone');
            exit;
        }
    }

    $update_fields[] = "$field = ?";
    $update_values[] = $value;
    $update_types .= $type;
}

if (in_array('password_hash', $doctor_columns, true)) {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($new_password !== '' || $confirm_password !== '') {
        if ($new_password !== $confirm_password) {
            header('Location: edit_profile.php?error=password_mismatch');
            exit;
        }

        if (strlen($new_password) < 8) {
            header('Location: edit_profile.php?error=password_short');
            exit;
        }

        $update_fields[] = "password_hash = ?";
        $update_values[] = password_hash($new_password, PASSWORD_DEFAULT);
        $update_types .= 's';
    }
}

if (in_array('avatar', $doctor_columns, true) && isset($_FILES['avatar_file'])) {
    $avatar_file = $_FILES['avatar_file'];

    if ($avatar_file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($avatar_file['error'] !== UPLOAD_ERR_OK) {
            header('Location: edit_profile.php?error=upload_failed');
            exit;
        }

        $image_info = @getimagesize($avatar_file['tmp_name']);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($avatar_file['name'], PATHINFO_EXTENSION));

        if ($image_info === false || !in_array($extension, $allowed_extensions, true)) {
            header('Location: edit_profile.php?error=invalid_image');
            exit;
        }

        $upload_dir = realpath(__DIR__ . '/../images');
        if ($upload_dir === false) {
            header('Location: edit_profile.php?error=upload_failed');
            exit;
        }

        $filename = 'doctor_' . $doctor_id . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($avatar_file['tmp_name'], $target_path)) {
            header('Location: edit_profile.php?error=upload_failed');
            exit;
        }

        $update_fields[] = "avatar = ?";
        $update_values[] = '../../images/' . $filename;
        $update_types .= 's';
    }
}

if (!empty($update_fields)) {
    $doctor_update_sql = "UPDATE doctors SET " . implode(', ', $update_fields) . " WHERE doctor_id = ?";
    $doctor_update_stmt = $conn->prepare($doctor_update_sql);
    $update_types .= 'i';
    $update_values[] = $doctor_id;

    $params = [];
    $params[] = &$update_types;
    foreach ($update_values as $key => $value) {
      $params[] = &$update_values[$key];
    }

    call_user_func_array([$doctor_update_stmt, 'bind_param'], $params);

    if (!$doctor_update_stmt->execute()) {
        header('Location: edit_profile.php?error=save_failed');
        exit;
    }
}

$days_of_week = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$default_work_start = '08:00:00';
$default_work_end = '16:00:00';
$default_break_start = '12:00:00';
$default_break_end = '13:00:00';
$schedule_data = $_POST['schedule'] ?? [];

foreach ($days_of_week as $day) {
    $day_data = $schedule_data[$day] ?? [];
    $is_working_day = isset($day_data['is_working_day']) ? 1 : 0;
    $work_start_time = $day_data['work_start_time'] ?? null;
    $work_end_time = $day_data['work_end_time'] ?? null;
    $break_start_time = $day_data['break_start_time'] ?? null;
    $break_end_time = $day_data['break_end_time'] ?? null;

    $work_start_time = $work_start_time !== '' ? $work_start_time . ':00' : null;
    $work_end_time = $work_end_time !== '' ? $work_end_time . ':00' : null;
    $break_start_time = $break_start_time !== '' ? $break_start_time . ':00' : null;
    $break_end_time = $break_end_time !== '' ? $break_end_time . ':00' : null;

    if ($is_working_day) {
        $work_start_time = $work_start_time ?: $default_work_start;
        $work_end_time = $work_end_time ?: $default_work_end;
        $break_start_time = $break_start_time ?: $default_break_start;
        $break_end_time = $break_end_time ?: $default_break_end;

        if (!$work_start_time || !$work_end_time || strtotime($work_start_time) >= strtotime($work_end_time)) {
            header('Location: edit_profile.php?error=schedule_invalid');
            exit;
        }

        if (($break_start_time && !$break_end_time) || (!$break_start_time && $break_end_time)) {
            header('Location: edit_profile.php?error=schedule_invalid');
            exit;
        }

        if ($break_start_time && $break_end_time) {
            if (
                strtotime($break_start_time) >= strtotime($break_end_time) ||
                strtotime($break_start_time) < strtotime($work_start_time) ||
                strtotime($break_end_time) > strtotime($work_end_time)
            ) {
                header('Location: edit_profile.php?error=schedule_invalid');
                exit;
            }
        }
    } else {
        $work_start_time = null;
        $work_end_time = null;
        $break_start_time = null;
        $break_end_time = null;
    }

    $schedule_exists_stmt = $conn->prepare("SELECT schedule_id FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ?");
    $schedule_exists_stmt->bind_param("is", $doctor_id, $day);
    $schedule_exists_stmt->execute();
    $existing_row = $schedule_exists_stmt->get_result()->fetch_assoc();

    if ($existing_row) {
        $update_schedule_sql = "UPDATE doctor_schedule
                                SET is_working_day = ?, work_start_time = ?, work_end_time = ?, break_start_time = ?, break_end_time = ?
                                WHERE doctor_id = ? AND day_of_week = ?";
        $update_schedule_stmt = $conn->prepare($update_schedule_sql);
        $update_schedule_stmt->bind_param(
            "issssis",
            $is_working_day,
            $work_start_time,
            $work_end_time,
            $break_start_time,
            $break_end_time,
            $doctor_id,
            $day
        );
        if (!$update_schedule_stmt->execute()) {
            header('Location: edit_profile.php?error=save_failed');
            exit;
        }
    } else {
        $insert_schedule_sql = "INSERT INTO doctor_schedule
                                (doctor_id, day_of_week, is_working_day, work_start_time, work_end_time, break_start_time, break_end_time)
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_schedule_stmt = $conn->prepare($insert_schedule_sql);
        $insert_schedule_stmt->bind_param(
            "isissss",
            $doctor_id,
            $day,
            $is_working_day,
            $work_start_time,
            $work_end_time,
            $break_start_time,
            $break_end_time
        );
        if (!$insert_schedule_stmt->execute()) {
            header('Location: edit_profile.php?error=save_failed');
            exit;
        }
    }
}

header('Location: edit_profile.php?success=1');
exit;
?>
