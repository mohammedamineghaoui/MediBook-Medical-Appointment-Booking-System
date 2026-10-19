<?php
include 'require_admin.php';
include '../php/conn.php';

$search_term = trim($_GET['search'] ?? '');
$selected_status = $_GET['status'] ?? 'all';
$selected_doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
$selected_date = trim($_GET['date'] ?? '');
$selected_time = trim($_GET['time'] ?? '');
$allowed_statuses = ['all', 'scheduled', 'completed', 'cancelled'];
$flash_messages = [
    'deleted' => 'Appointment removed successfully.',
    'updated' => 'Appointment status updated successfully.',
    'invalid_request' => 'Invalid appointment request.',
    'delete_failed' => 'Could not remove appointment.',
    'update_failed' => 'Could not update appointment status.',
    'not_found' => 'Appointment was not found.',
    'past_scheduled' => 'Past appointments cannot be marked as scheduled.',
    'not_working_day' => 'This doctor is not working on the appointment day.',
    'out_of_working_hours' => 'The appointment time is outside the doctor working hours.',
    'break_time' => 'The appointment time is during the doctor break time.',
    'already_booked' => 'This doctor already has a scheduled appointment at that time.',
    'patient_has_appointment' => 'This patient already has a scheduled appointment on that day.',
];

function redirect_to_appointments(string $result_key): void
{
    $query = [];
    if (isset($_GET['status'])) {
        $query['status'] = $_GET['status'];
    }
    if (isset($_GET['search'])) {
        $query['search'] = $_GET['search'];
    }
    if (isset($_GET['doctor_id'])) {
        $query['doctor_id'] = $_GET['doctor_id'];
    }
    if (isset($_GET['date'])) {
        $query['date'] = $_GET['date'];
    }
    if (isset($_GET['time'])) {
        $query['time'] = $_GET['time'];
    }
    $query['message'] = $result_key;

    header('Location: appointments.php?' . http_build_query($query));
    exit;
}

function bind_dynamic_params(mysqli_stmt $stmt, string $types, array &$values): void
{
    $params = [$types];
    foreach ($values as &$value) {
        $params[] = &$value;
    }

    call_user_func_array([$stmt, 'bind_param'], $params);
}

function validate_scheduled_rules(mysqli $conn, int $appointment_id): ?string
{
    $appointment_sql = "SELECT appointment_id, patient_id, doctor_id, appointment_time
                        FROM appointments
                        WHERE appointment_id = ?";
    $appointment_stmt = $conn->prepare($appointment_sql);
    $appointment_stmt->bind_param("i", $appointment_id);
    $appointment_stmt->execute();
    $appointment = $appointment_stmt->get_result()->fetch_assoc();

    if (!$appointment) {
        return 'not_found';
    }

    $appointment_timestamp = strtotime($appointment['appointment_time']);
    if ($appointment_timestamp <= time()) {
        return 'past_scheduled';
    }

    $date = date('Y-m-d', $appointment_timestamp);
    $time = date('H:i:s', $appointment_timestamp);
    $weekday_name = date('l', $appointment_timestamp);
    $doctor_id = (int) $appointment['doctor_id'];
    $patient_id = (int) $appointment['patient_id'];

    $schedule_sql = "SELECT work_start_time, work_end_time, break_start_time, break_end_time, is_working_day
                     FROM doctor_schedule
                     WHERE doctor_id = ? AND day_of_week = ?";
    $schedule_stmt = $conn->prepare($schedule_sql);
    $schedule_stmt->bind_param("is", $doctor_id, $weekday_name);
    $schedule_stmt->execute();
    $schedule = $schedule_stmt->get_result()->fetch_assoc();

    if (!$schedule || (int) $schedule['is_working_day'] === 0) {
        return 'not_working_day';
    }

    $time_ts = strtotime($time);
    $work_start_ts = strtotime($schedule['work_start_time']);
    $work_end_ts = strtotime($schedule['work_end_time']);

    if ($time_ts < $work_start_ts || $time_ts >= $work_end_ts) {
        return 'out_of_working_hours';
    }

    if (!empty($schedule['break_start_time']) && !empty($schedule['break_end_time'])) {
        $break_start_ts = strtotime($schedule['break_start_time']);
        $break_end_ts = strtotime($schedule['break_end_time']);

        if ($time_ts >= $break_start_ts && $time_ts < $break_end_ts) {
            return 'break_time';
        }
    }

    $duplicate_slot_sql = "SELECT COUNT(*) AS total
                           FROM appointments
                           WHERE doctor_id = ?
                           AND appointment_time = ?
                           AND status = 'scheduled'
                           AND appointment_id != ?";
    $duplicate_slot_stmt = $conn->prepare($duplicate_slot_sql);
    $duplicate_slot_stmt->bind_param("isi", $doctor_id, $appointment['appointment_time'], $appointment_id);
    $duplicate_slot_stmt->execute();
    $duplicate_slot = $duplicate_slot_stmt->get_result()->fetch_assoc();

    if ((int) $duplicate_slot['total'] > 0) {
        return 'already_booked';
    }

    $patient_same_day_sql = "SELECT COUNT(*) AS total
                             FROM appointments
                             WHERE patient_id = ?
                             AND DATE(appointment_time) = ?
                             AND status = 'scheduled'
                             AND appointment_id != ?";
    $patient_same_day_stmt = $conn->prepare($patient_same_day_sql);
    $patient_same_day_stmt->bind_param("isi", $patient_id, $date, $appointment_id);
    $patient_same_day_stmt->execute();
    $patient_same_day = $patient_same_day_stmt->get_result()->fetch_assoc();

    if ((int) $patient_same_day['total'] > 0) {
        return 'patient_has_appointment';
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
    $action = $_POST['action'] ?? '';

    if (!$appointment_id || !in_array($action, ['delete', 'status'], true)) {
        redirect_to_appointments('invalid_request');
    }

    if ($action === 'delete') {
        $exists_stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = ?");
        $exists_stmt->bind_param("i", $appointment_id);
        $exists_stmt->execute();

        if ($exists_stmt->get_result()->num_rows === 0) {
            redirect_to_appointments('not_found');
        }

        $conn->begin_transaction();

        try {
            $ratings_delete_stmt = $conn->prepare("DELETE FROM ratings WHERE appointment_id = ?");
            $ratings_delete_stmt->bind_param("i", $appointment_id);
            $ratings_delete_stmt->execute();

            $delete_stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $delete_stmt->bind_param("i", $appointment_id);
            $delete_stmt->execute();

            if ($delete_stmt->affected_rows > 0) {
                $conn->commit();
                redirect_to_appointments('deleted');
            }

            $conn->rollback();
            redirect_to_appointments('not_found');
        } catch (Throwable $exception) {
            $conn->rollback();
            redirect_to_appointments('delete_failed');
        }
    }

    $new_status = strtolower(trim($_POST['new_status'] ?? ''));
    $status_values = ['scheduled', 'completed', 'cancelled'];

    if (!in_array($new_status, $status_values, true)) {
        redirect_to_appointments('invalid_request');
    }

    if ($new_status === 'scheduled') {
        $rule_error = validate_scheduled_rules($conn, $appointment_id);
        if ($rule_error !== null) {
            redirect_to_appointments($rule_error);
        }
    } else {
        $exists_stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = ?");
        $exists_stmt->bind_param("i", $appointment_id);
        $exists_stmt->execute();

        if ($exists_stmt->get_result()->num_rows === 0) {
            redirect_to_appointments('not_found');
        }
    }

    $update_stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $update_stmt->bind_param("si", $new_status, $appointment_id);

    if ($update_stmt->execute()) {
        redirect_to_appointments('updated');
    }

    redirect_to_appointments('update_failed');
}

if (!in_array($selected_status, $allowed_statuses, true)) {
    $selected_status = 'all';
}

$date_object = $selected_date !== '' ? DateTime::createFromFormat('Y-m-d', $selected_date) : false;
if ($selected_date !== '' && (!$date_object || $date_object->format('Y-m-d') !== $selected_date)) {
    $selected_date = '';
}

if ($selected_time !== '' && !preg_match('/^\d{2}:\d{2}$/', $selected_time)) {
    $selected_time = '';
}

$doctor_options = [];
$doctor_options_result = $conn->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name ASC");
if ($doctor_options_result) {
    while ($doctor = $doctor_options_result->fetch_assoc()) {
        $doctor_options[] = $doctor;
    }
}

$where_sql = "WHERE (? = '' OR p.full_name LIKE ? OR d.full_name LIKE ? OR a.status LIKE ?)";
$bind_types = "ssss";
$bind_values = [$search_term, '%' . $search_term . '%', '%' . $search_term . '%', '%' . $search_term . '%'];

if ($selected_status !== 'all') {
    $where_sql .= " AND a.status = ?";
    $bind_types .= "s";
    $bind_values[] = $selected_status;
}

if ($selected_doctor_id > 0) {
    $where_sql .= " AND a.doctor_id = ?";
    $bind_types .= "i";
    $bind_values[] = $selected_doctor_id;
}

if ($selected_date !== '') {
    $where_sql .= " AND DATE(a.appointment_time) = ?";
    $bind_types .= "s";
    $bind_values[] = $selected_date;
}

if ($selected_time !== '') {
    $where_sql .= " AND TIME(a.appointment_time) = ?";
    $bind_types .= "s";
    $bind_values[] = $selected_time . ':00';
}

$appointments_sql = "SELECT
        a.appointment_id,
        a.appointment_time,
        a.status,
        p.full_name AS patient_name,
        p.email AS patient_email,
        p.avatar AS patient_avatar,
        d.full_name AS doctor_name,
        s.specialty_name
    FROM appointments a
    LEFT JOIN patient p ON a.patient_id = p.user_id
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
    $where_sql
    ORDER BY a.appointment_time DESC";

$appointments_stmt = $conn->prepare($appointments_sql);
bind_dynamic_params($appointments_stmt, $bind_types, $bind_values);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

$appointments = [];
while ($appointment = $appointments_result->fetch_assoc()) {
    $appointments[] = $appointment;
}

$total_appointments = count($appointments);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | MediBook Admin</title>
    <meta name="description" content="View all appointments across doctors and patients">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patients.css">
    <link rel="stylesheet" href="appointments.css">
</head>

<body>
    <div class="app-container admin-app">
        <?php
        $active_page = 'appointments';
        include 'admin_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle" aria-label="Open sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Appointments</p>
                        <h1 class="page-title">All Appointments</h1>
                    </div>
                </div>
                <div class="header-right">
                    <form method="GET" class="search-bar">
                        <?php if ($selected_status !== 'all') : ?>
                        <input type="hidden" name="status" value="<?=htmlspecialchars($selected_status, ENT_QUOTES)?>">
                        <?php endif; ?>
                        <?php if ($selected_doctor_id > 0) : ?>
                        <input type="hidden" name="doctor_id" value="<?=$selected_doctor_id?>">
                        <?php endif; ?>
                        <?php if ($selected_date !== '') : ?>
                        <input type="hidden" name="date" value="<?=htmlspecialchars($selected_date, ENT_QUOTES)?>">
                        <?php endif; ?>
                        <?php if ($selected_time !== '') : ?>
                        <input type="hidden" name="time" value="<?=htmlspecialchars($selected_time, ENT_QUOTES)?>">
                        <?php endif; ?>
                        <button type="submit" class="search-icon-btn" aria-label="Search appointments">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <input type="text" name="search" placeholder="Search patient, doctor, or status"
                            value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                    </form>
                </div>
            </header>

            <section class="patients-page appointments-page">
                <div class="patients-intro">
                    <div>
                        <h2>Appointment Directory</h2>
                        <p>View every appointment with its date, patient, doctor, specialty, and current status.</p>
                    </div>
                    <div class="patients-badge">
                        <i class="fa-regular fa-calendar-check"></i>
                        <span><?=$total_appointments?> Appointments</span>
                    </div>
                </div>

                <form method="GET" class="appointment-filter-form">
                    <div class="filter-field">
                        <label for="doctor_id">Doctor</label>
                        <select id="doctor_id" name="doctor_id">
                            <option value="0">All doctors</option>
                            <?php foreach ($doctor_options as $doctor) : ?>
                            <option value="<?=$doctor['doctor_id']?>" <?=$selected_doctor_id === (int) $doctor['doctor_id'] ? 'selected' : ''?>>
                                <?=htmlspecialchars($doctor['full_name'])?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach ($allowed_statuses as $status) : ?>
                            <option value="<?=$status?>" <?=$selected_status === $status ? 'selected' : ''?>>
                                <?=htmlspecialchars(ucfirst($status))?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?=htmlspecialchars($selected_date, ENT_QUOTES)?>">
                    </div>

                    <div class="filter-field">
                        <label for="time">Time</label>
                        <input type="time" id="time" name="time" value="<?=htmlspecialchars($selected_time, ENT_QUOTES)?>">
                    </div>

                    <div class="filter-field filter-search-field">
                        <label for="filter_search">Search</label>
                        <input type="text" id="filter_search" name="search" placeholder="Patient, doctor, or status"
                            value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="filter-submit">
                            <i class="fa-solid fa-filter"></i>
                            <span>Apply</span>
                        </button>
                        <a href="appointments.php" class="filter-reset">Reset</a>
                    </div>
                </form>

                <div class="appointment-filters">
                    <?php foreach ($allowed_statuses as $status) :
                        $href = 'appointments.php?status=' . urlencode($status);
                        if ($search_term !== '') {
                            $href .= '&search=' . urlencode($search_term);
                        }
                        if ($selected_doctor_id > 0) {
                            $href .= '&doctor_id=' . urlencode((string) $selected_doctor_id);
                        }
                        if ($selected_date !== '') {
                            $href .= '&date=' . urlencode($selected_date);
                        }
                        if ($selected_time !== '') {
                            $href .= '&time=' . urlencode($selected_time);
                        }
                    ?>
                    <a class="filter-pill <?=$selected_status === $status ? 'active' : ''?>" href="<?=$href?>">
                        <?=htmlspecialchars(ucfirst($status))?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($_GET['message'], $flash_messages[$_GET['message']])) :
                    $is_error = !in_array($_GET['message'], ['deleted', 'updated'], true);
                ?>
                <div class="admin-flash <?=$is_error ? 'error' : 'success'?>">
                    <?=htmlspecialchars($flash_messages[$_GET['message']])?>
                </div>
                <?php endif; ?>

                <div class="card patients-card">
                    <div class="table-responsive">
                        <table class="data-table patients-table appointments-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($appointments)) : ?>
                                <?php foreach ($appointments as $appointment) :
                                    $status = (string) ($appointment['status'] ?? '');
                                    $status_class = $status === 'cancelled' ? 'status-cancelled' : ($status === 'completed' ? 'status-confirmed' : 'status-waiting');
                                    $avatar = !empty($appointment['patient_avatar']) ? $appointment['patient_avatar'] : '../../images/user.png';
                                ?>
                                <tr>
                                    <td>
                                        <div class="patient-cell">
                                            <img src="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($appointment['patient_name'] ?? 'Patient', ENT_QUOTES)?>">
                                            <div class="patient-info">
                                                <span class="patient-name"><?=htmlspecialchars($appointment['patient_name'] ?? 'Unknown patient')?></span>
                                                <span class="patient-id"><?=htmlspecialchars($appointment['patient_email'] ?? '-')?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?=htmlspecialchars($appointment['doctor_name'] ?? 'Unknown doctor')?></td>
                                    <td><?=htmlspecialchars($appointment['specialty_name'] ?? 'Specialist')?></td>
                                    <td><?=date('Y-m-d', strtotime($appointment['appointment_time']))?></td>
                                    <td><?=date('H:i', strtotime($appointment['appointment_time']))?></td>
                                    <td><span class="status-badge <?=$status_class?>"><?=htmlspecialchars(ucfirst($status ?: 'Unknown'))?></span></td>
                                    <td>
                                        <div class="appointment-actions">
                                            <form method="POST">
                                                <input type="hidden" name="appointment_id" value="<?=$appointment['appointment_id']?>">
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="new_status" value="scheduled">
                                                <button type="submit" class="action-btn schedule" <?=$status === 'scheduled' ? 'disabled' : ''?>>
                                                    Scheduled
                                                </button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="appointment_id" value="<?=$appointment['appointment_id']?>">
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="new_status" value="completed">
                                                <button type="submit" class="action-btn complete" <?=$status === 'completed' ? 'disabled' : ''?>>
                                                    Complete
                                                </button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="appointment_id" value="<?=$appointment['appointment_id']?>">
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="new_status" value="cancelled">
                                                <button type="submit" class="action-btn cancel" <?=$status === 'cancelled' ? 'disabled' : ''?>>
                                                    Cancel
                                                </button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Remove this appointment from the database?');">
                                                <input type="hidden" name="appointment_id" value="<?=$appointment['appointment_id']?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="action-btn delete">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else : ?>
                                <tr>
                                    <td colspan="7" class="empty-state-cell">No appointments found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
</body>

</html>
