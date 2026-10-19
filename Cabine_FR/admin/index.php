<?php
include 'require_admin.php';
include '../php/conn.php';

$admin_name = $_SESSION['full_name'] ?? 'Admin';

function count_rows(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

$total_doctors = count_rows($conn, "SELECT COUNT(*) AS total FROM doctors");
$total_patients = count_rows($conn, "SELECT COUNT(*) AS total FROM patient");
$total_appointments = count_rows($conn, "SELECT COUNT(*) AS total FROM appointments");
$today_appointments = count_rows(
    $conn,
    "SELECT COUNT(*) AS total FROM appointments WHERE DATE(appointment_time) = CURDATE()"
);
$scheduled_appointments = count_rows($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'scheduled'");
$completed_appointments = count_rows($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'completed'");
$cancelled_appointments = count_rows($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'cancelled'");

$recent_appointments_sql = "SELECT
        a.appointment_id,
        a.appointment_time,
        a.status,
        p.full_name AS patient_name,
        p.avatar AS patient_avatar,
        d.full_name AS doctor_name
    FROM appointments a
    LEFT JOIN patient p ON a.patient_id = p.user_id
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    ORDER BY a.appointment_time DESC
    LIMIT 6";
$recent_appointments_result = $conn->query($recent_appointments_sql);

$recent_doctors_sql = "SELECT
        d.doctor_id,
        d.full_name,
        d.avatar,
        d.location,
        s.specialty_name,
        COUNT(a.appointment_id) AS appointment_count
    FROM doctors d
    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
    LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
    GROUP BY d.doctor_id, d.full_name, d.avatar, d.location, s.specialty_name
    ORDER BY d.doctor_id DESC
    LIMIT 5";
$recent_doctors_result = $conn->query($recent_doctors_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MediBook</title>
    <meta name="description" content="Admin dashboard for MediBook healthcare management">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Admin_dashboard.css">
</head>

<body>
    <div class="app-container admin-app">
        <?php
        $active_page = 'dashboard';
        include 'admin_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle" aria-label="Open sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Administration</p>
                        <h1 class="page-title">Admin Dashboard</h1>
                    </div>
                </div>

                <div class="header-right">
                    <button class="icon-btn notification-btn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                    <div class="doctor-profile">
                        <div class="avatar admin-avatar">
                            <?=htmlspecialchars(strtoupper(substr(trim($admin_name), 0, 1)))?>
                        </div>
                        <div class="profile-info">
                            <span class="dr-name"><?=htmlspecialchars($admin_name)?></span>
                            <span class="dr-specialty">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <section class="welcome-banner admin-welcome">
                    <div class="welcome-text">
                        <h2>Welcome back, <?=htmlspecialchars($admin_name)?></h2>
                        <p>Monitor doctors, patients, and appointments from one green-themed control center.</p>
                    </div>
                    <div class="welcome-date" id="currentDate"></div>
                </section>

                <section class="summary-cards">
                    <div class="card summary-card">
                        <div class="card-icon green">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">Total Doctors</span>
                            <h3 class="card-value"><?=$total_doctors?></h3>
                        </div>
                    </div>

                    <div class="card summary-card">
                        <div class="card-icon blue">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">Total Patients</span>
                            <h3 class="card-value"><?=$total_patients?></h3>
                        </div>
                    </div>

                    <div class="card summary-card">
                        <div class="card-icon orange">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">Today's Appointments</span>
                            <h3 class="card-value"><?=$today_appointments?></h3>
                        </div>
                    </div>

                    <div class="card summary-card">
                        <div class="card-icon purple">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">All Appointments</span>
                            <h3 class="card-value"><?=$total_appointments?></h3>
                        </div>
                    </div>
                </section>

                <div class="content-grid">
                    <div class="grid-col-left">
                        <section class="card appointments-section">
                            <div class="card-header">
                                <h3>Recent Appointments</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_appointments_result && $recent_appointments_result->num_rows > 0) : ?>
                                        <?php while ($appointment = $recent_appointments_result->fetch_assoc()) :
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
                                                        <span
                                                            class="patient-name"><?=htmlspecialchars($appointment['patient_name'] ?? 'Unknown patient')?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?=htmlspecialchars($appointment['doctor_name'] ?? 'Unknown doctor')?>
                                            </td>
                                            <td><?=date('Y-m-d', strtotime($appointment['appointment_time']))?></td>
                                            <td><?=date('H:i', strtotime($appointment['appointment_time']))?></td>
                                            <td><span
                                                    class="status-badge <?=$status_class?>"><?=htmlspecialchars(ucfirst($status))?></span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else : ?>
                                        <tr>
                                            <td colspan="5" class="empty-table">No appointments found.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="grid-col-right">
                        <section class="card admin-status-card">
                            <div class="card-header">
                                <h3>Appointment Status</h3>
                            </div>
                            <div class="admin-metrics">
                                <div class="metric-row">
                                    <span>Scheduled</span>
                                    <strong><?=$scheduled_appointments?></strong>
                                </div>
                                <div class="metric-row">
                                    <span>Completed</span>
                                    <strong><?=$completed_appointments?></strong>
                                </div>
                                <div class="metric-row">
                                    <span>Cancelled</span>
                                    <strong><?=$cancelled_appointments?></strong>
                                </div>
                            </div>
                        </section>

                        <section class="card recent-patients-section">
                            <div class="card-header">
                                <h3>Recent Doctors</h3>
                            </div>
                            <div class="patient-list">
                                <?php if ($recent_doctors_result && $recent_doctors_result->num_rows > 0) : ?>
                                <?php while ($doctor = $recent_doctors_result->fetch_assoc()) :
                                    $doctor_avatar = !empty($doctor['avatar']) ? $doctor['avatar'] : '../../images/user.png';
                                ?>
                                <div class="patient-list-item doctor-list-item">
                                    <div class="patient-item-left">
                                        <div class="patient-avatar">
                                            <img src="<?=htmlspecialchars($doctor_avatar, ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($doctor['full_name'], ENT_QUOTES)?>">
                                        </div>
                                        <div class="patient-details">
                                            <h4><?=htmlspecialchars($doctor['full_name'])?></h4>
                                            <p><?=htmlspecialchars($doctor['specialty_name'] ?? 'No specialty')?> ·
                                                <?=htmlspecialchars($doctor['location'] ?? 'No location')?></p>
                                        </div>
                                    </div>
                                    <div class="patient-item-right">
                                        <span class="last-visit"><?=$doctor['appointment_count']?> appointments</span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php else : ?>
                                <div class="patient-list-item">
                                    <div class="patient-details">
                                        <h4>No doctors found</h4>
                                        <p>Create doctor accounts to populate this list.</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
</body>

</html>
