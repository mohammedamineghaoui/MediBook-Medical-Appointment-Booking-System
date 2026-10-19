<?php
include 'require_doctor.php';
$doctor_id = $_SESSION['doctor_id'] ?? 1;
include '../php/conn.php';

$doctor_columns = [];
$columns_result = $conn->query("SHOW COLUMNS FROM doctors");
while ($column = $columns_result->fetch_assoc()) {
    $doctor_columns[] = $column['Field'];
}

$preferred_fields = ['full_name', 'email', 'phone', 'experience', 'location', 'avatar'];
$available_fields = array_values(array_intersect($preferred_fields, $doctor_columns));

if (in_array('specialty_id', $doctor_columns, true)) {
    $available_fields[] = 'specialty_id';
}

$select_fields = !empty($available_fields) ? implode(', ', $available_fields) : '*';
$doctor_sql = "SELECT $select_fields FROM doctors WHERE doctor_id = ?";
$doctor_stmt = $conn->prepare($doctor_sql);
$doctor_stmt->bind_param("i", $doctor_id);
$doctor_stmt->execute();
$doctor = $doctor_stmt->get_result()->fetch_assoc();

if (!$doctor) {
    die('Doctor not found.');
}

$specialties = [];
if (in_array('specialty_id', $doctor_columns, true)) {
    $specialties_result = $conn->query("SELECT specialty_id, specialty_name FROM specialties ORDER BY specialty_name ASC");
    if ($specialties_result) {
        while ($specialty = $specialties_result->fetch_assoc()) {
            $specialties[] = $specialty;
        }
    }
}

$days_of_week = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$default_work_start = '08:00';
$default_work_end = '16:00';
$default_break_start = '12:00';
$default_break_end = '13:00';
$schedule_map = [];
$schedule_sql = "SELECT day_of_week, is_working_day, work_start_time, work_end_time, break_start_time, break_end_time
                 FROM doctor_schedule
                 WHERE doctor_id = ?
                 ORDER BY FIELD(day_of_week,'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday')";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $doctor_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

while ($row = $schedule_result->fetch_assoc()) {
    $schedule_map[$row['day_of_week']] = $row;
}

$message = '';
$message_type = '';

if (isset($_GET['success']) && $_GET['success'] === '1') {
    $message = 'Doctor information updated successfully.';
    $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $errors = [
        'invalid_request' => 'Invalid request.',
        'doctor_not_found' => 'Doctor account was not found.',
        'duplicate_email' => 'That email is already used by another doctor.',
        'duplicate_phone' => 'That phone number is already used by another doctor.',
        'invalid_email' => 'Enter a valid email address.',
        'invalid_image' => 'Upload a valid image file.',
        'upload_failed' => 'Could not upload the profile photo.',
        'password_mismatch' => 'New password and confirm password must match.',
        'password_short' => 'New password must be at least 8 characters.',
        'schedule_invalid' => 'One or more working days have invalid time values.',
        'save_failed' => 'Could not save the changes.',
    ];
    $message = $errors[$_GET['error']] ?? 'Something went wrong.';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor Profile | MediBook</title>
    <meta name="description" content="Edit doctor profile information and working schedule">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="edit_profile.css">
</head>

<body>
    <div class="app-container">
        <?php
        $active_page = 'profile';
        include 'doctor_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Doctor settings</p>
                        <h1 class="page-title">Edit Profile & Schedule</h1>
                    </div>
                </div>
            </header>

            <section class="edit-page">
                <?php if ($message !== '') : ?>
                <div class="flash-message <?=$message_type?>">
                    <i
                        class="fa-solid <?=$message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'?>"></i>
                    <span><?=$message?></span>
                </div>
                <?php endif; ?>

                <form action="save_doctor_profile.php" method="POST" enctype="multipart/form-data" class="edit-layout">
                    <div class="panel">
                        <div class="panel-header">
                            <h2>Doctor Information</h2>
                            <p>Update the profile details shown around the platform.</p>
                        </div>

                        <div class="form-grid">
                            <?php if (in_array('full_name', $doctor_columns, true)) : ?>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name"
                                    value="<?=htmlspecialchars($doctor['full_name'] ?? '', ENT_QUOTES)?>">
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('email', $doctor_columns, true)) : ?>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email"
                                    value="<?=htmlspecialchars($doctor['email'] ?? '', ENT_QUOTES)?>">
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('phone', $doctor_columns, true)) : ?>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" name="phone"
                                    value="<?=htmlspecialchars($doctor['phone'] ?? '', ENT_QUOTES)?>">
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('experience', $doctor_columns, true)) : ?>
                            <div class="form-group">
                                <label for="experience">Experience</label>
                                <input type="text" id="experience" name="experience"
                                    value="<?=htmlspecialchars($doctor['experience'] ?? '', ENT_QUOTES)?>">
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('location', $doctor_columns, true)) : ?>
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location"
                                    value="<?=htmlspecialchars($doctor['location'] ?? '', ENT_QUOTES)?>">
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('avatar', $doctor_columns, true)) : ?>
                            <div class="form-group full-width">
                                <label for="avatar_file">Profile Photo</label>
                                <div class="avatar-upload">
                                    <img src="<?=htmlspecialchars($doctor['avatar'] ?? '../../images/user.png', ENT_QUOTES)?>"
                                        alt="Doctor avatar" class="avatar-preview">
                                    <div class="avatar-upload-copy">
                                        <input type="file" id="avatar_file" name="avatar_file" accept="image/*">
                                        <small>Upload a JPG, PNG, GIF, or WEBP image. The file will be saved in the
                                            `images` folder.</small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('specialty_id', $doctor_columns, true) && !empty($specialties)) : ?>
                            <div class="form-group full-width">
                                <label for="specialty_id">Specialty</label>
                                <select id="specialty_id" name="specialty_id">
                                    <option value="">Select specialty</option>
                                    <?php foreach ($specialties as $specialty) : ?>
                                    <option value="<?=$specialty['specialty_id']?>"
                                        <?=isset($doctor['specialty_id']) && (int) $doctor['specialty_id'] === (int) $specialty['specialty_id'] ? 'selected' : ''?>>
                                        <?=htmlspecialchars($specialty['specialty_name'])?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('password_hash', $doctor_columns, true)) : ?>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password"
                                    placeholder="Leave empty to keep current password">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                    placeholder="Repeat new password">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h2>Weekly Working Schedule</h2>
                            <p>Choose which days you work and set working hours and break times.</p>
                        </div>

                        <div class="schedule-list">
                            <?php foreach ($days_of_week as $day) :
                                $day_schedule = $schedule_map[$day] ?? null;
                                $is_working = !$day_schedule ? 0 : (int) $day_schedule['is_working_day'];
                                $work_start_value = substr((string) ($day_schedule['work_start_time'] ?? ''), 0, 5);
                                $work_end_value = substr((string) ($day_schedule['work_end_time'] ?? ''), 0, 5);
                                $break_start_value = substr((string) ($day_schedule['break_start_time'] ?? ''), 0, 5);
                                $break_end_value = substr((string) ($day_schedule['break_end_time'] ?? ''), 0, 5);

                                if ($is_working) {
                                    $work_start_value = $work_start_value !== '' ? $work_start_value : $default_work_start;
                                    $work_end_value = $work_end_value !== '' ? $work_end_value : $default_work_end;
                                    $break_start_value = $break_start_value !== '' ? $break_start_value : $default_break_start;
                                    $break_end_value = $break_end_value !== '' ? $break_end_value : $default_break_end;
                                }
                            ?>
                            <div class="schedule-card">
                                <div class="schedule-top">
                                    <div>
                                        <h3><?=$day?></h3>
                                        <p>Set working hours for <?=$day?></p>
                                    </div>
                                    <label class="toggle-wrap">
                                        <input type="checkbox" name="schedule[<?=$day?>][is_working_day]" value="1"
                                            <?=$is_working ? 'checked' : ''?>>
                                        <span>Working day</span>
                                    </label>
                                </div>

                                <div class="schedule-grid">
                                    <div class="form-group">
                                        <label>Start Time</label>
                                        <input type="time" name="schedule[<?=$day?>][work_start_time]"
                                            value="<?=htmlspecialchars($work_start_value, ENT_QUOTES)?>">
                                    </div>

                                    <div class="form-group">
                                        <label>End Time</label>
                                        <input type="time" name="schedule[<?=$day?>][work_end_time]"
                                            value="<?=htmlspecialchars($work_end_value, ENT_QUOTES)?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Break Start</label>
                                        <input type="time" name="schedule[<?=$day?>][break_start_time]"
                                            value="<?=htmlspecialchars($break_start_value, ENT_QUOTES)?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Break End</label>
                                        <input type="time" name="schedule[<?=$day?>][break_end_time]"
                                            value="<?=htmlspecialchars($break_end_value, ENT_QUOTES)?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="actions-bar">
                        <a href="appointments.php" class="btn-light">Cancel</a>
                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
</body>

</html>
