<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include('../php/conn.php');

$patientFullName = $_SESSION['full_name'] ?? 'unkown';
$patientPhone =$_SESSION['phone'] ?? 'unkown';
$patientEmail = $_SESSION['email'] ?? 'unkown';
$patientbirthday = $_SESSION['birth_date'] ?? 'unkown';
$user_id = $_SESSION['user_id'];
$user_hashpassword = $_SESSION['password_hash'] ?? '**********';
$patientAvatar = $_SESSION['avatar'] ?? '../../images/user.png';
$patientInitials = strtoupper(substr(trim($patientFullName), 0, 1));

$sql = " SELECT 
            a.appointment_id,
            a.doctor_id,
            a.appointment_time,
            a.status,
            d.full_name AS doctor_name,
            d.location,
            r.rating_id AS existing_rating_id,
            r.rating AS existing_rating
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN ratings r ON r.appointment_id = a.appointment_id
        WHERE a.patient_id = ?
        ORDER BY FIELD(a.status, 'scheduled', 'completed', 'cancelled')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$appointments = [];
$summaryCounts = [
    'scheduled' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'total' => 0,
];

while ($appointment = $result->fetch_assoc()) {
    $appointments[] = $appointment;
    $summaryCounts['total']++;
    if (isset($summaryCounts[$appointment['status']])) {
        $summaryCounts[$appointment['status']]++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | MediBook</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="dashboard-page">
    <?php include 'header.php'; ?>

    <main class="dashboard-shell">
        <button class="dashboard-sidebar-toggle" id="dashboardSidebarToggle" type="button"
            aria-controls="dashboardSidebar" aria-expanded="false" aria-label="Open dashboard navigation">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </button>

        <aside class="dashboard-sidebar patient-dashboard-nav" id="dashboardSidebar">
            <div class="sidebar-brand-row">
                <a href="patient_dashboard.php" class="sidebar-brand" title="MediBook">
                    <i class="fas fa-heartbeat"></i>
                    <span>MediBook</span>
                </a>
                <button class="dashboard-pin-sidebar" id="dashboardPinSidebar" type="button"
                    aria-label="Keep sidebar open" aria-pressed="false">
                    <i class="fa-solid fa-thumbtack"></i>
                </button>
            </div>

            <nav class="sidebar-nav patient-nav-links" aria-label="Patient dashboard navigation">
                <a href="patient_dashboard.php" class="is-active" title="Dashboard">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#profile" title="Profile">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Profile</span>
                </a>
                <a href="#appointments" title="Appointments">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Appointments</span>
                </a>
                <a href="../php/editprofile.php" title="Edit Profile">
                    <i class="fa-solid fa-user-pen"></i>
                    <span>Edit Profile</span>
                </a>
            </nav>

            <div class="patient-nav-footer">
                <a href="/Cabine/php/logout.php" class="dashboard-logout" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <div class="dashboard-sidebar-overlay" id="dashboardSidebarOverlay"></div>


        <section class="dashboard-main">
            <?php if (isset($_SESSION['message'])): ?>
            <?php
                $is_success = (($_SESSION['message_type'] ?? '') === 'success');
                $alert_class = $is_success ? 'success' : 'error';
                $alert_role = $is_success ? 'status' : 'alert';
                $alert_icon = $is_success ? 'fa-regular fa-circle-check' : 'fa-solid fa-triangle-exclamation';
                $message = (string) ($_SESSION['message'] ?? '');
                unset($_SESSION['message'], $_SESSION['message_type']);
            ?>
            <div class="alert dashboard-alert is-dismissible <?= htmlspecialchars($alert_class); ?>"
                role="<?= htmlspecialchars($alert_role); ?>">
                <i class="<?= htmlspecialchars($alert_icon); ?>"></i>
                <div><?= htmlspecialchars($message); ?></div>
                <button class="alert-close" type="button" aria-label="Dismiss message" data-alert-close>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php endif; ?>
            <div class="dashboard-top-bar">
                <div class="dashboard-title-group">
                    <p class="eyebrow">Patient Portal</p>
                    <div>
                        <h1>Patient Dashboard</h1>
                        <p class="top-subtitle">Welcome back, <strong><?= htmlspecialchars($patientFullName); ?></strong>
                        </p>
                    </div>
                </div>

            </div>

            <article id="profile" class="card-panel patient-profile-card">
                <div class="card-header">
                    <div class="profile-title-row">
                        <div class="profile-photo-large">
                            <img src="<?= htmlspecialchars($patientAvatar, ENT_QUOTES); ?>" alt="Profile photo">
                        </div>
                        <div>
                            <h2>Your Profile</h2>
                            <p>Update your information from Edit profile .</p>
                        </div>
                    </div>

                </div>

                <div class="profile-details">
                    <div class="profile-detail-card">
                        <span><i class="fas fa-user"></i> Full Name</span>
                        <strong><?= htmlspecialchars($patientFullName); ?></strong>
                    </div>
                    <div class="profile-detail-card">
                        <span><i class="fas fa-phone"></i> Phone Number</span>
                        <strong><?= htmlspecialchars($patientPhone); ?></strong>
                    </div>
                    <div class="profile-detail-card">
                        <span><i class="fas fa-envelope"></i> Email Address</span>
                        <strong><?= htmlspecialchars($patientEmail); ?></strong>
                    </div>
                    <div class="profile-detail-card">
                        <span><i class="fas fa-lock"></i>Birth day </span>
                        <strong><?= htmlspecialchars($patientbirthday); ?></strong>
                    </div>
                </div>

                <form id="profileForm" class="profile-form" novalidate>
                    <div class="form-grid">
                        <label>
                            Full Name
                            <input type="text" name="full_name" value="<?= htmlspecialchars($patientFullName); ?>"
                                required minlength="3">
                        </label>
                        <label>
                            Phone Number
                            <input type="tel" name="phone" value="<?= htmlspecialchars($patientPhone); ?>" required
                                pattern="\+?[0-9\s\-\(\)]{7,25}">
                        </label>
                        <label>
                            Email Address
                            <input type="email" name="email" value="<?= htmlspecialchars($patientEmail); ?>" required>
                        </label>
                        <label>
                            Password
                            <input type="password" name="password" placeholder="**************"
                                autocomplete="new-password" minlength="8">
                            <small>Minimum 8 characters (optional unless changing).</small>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-ghost" id="cancelEditBtn">Cancel</button>
                        <button type="submit" class="btn-primary btn-small">Save changes</button>
                    </div>
                    <div class="form-feedback" id="profileFeedback" aria-live="polite"></div>
                </form>
            </article>

            <article id="appointments" class="card-panel appointments-section">
                <div class="card-header section-header">
                    <div>
                        <h2>Appointments</h2>
                        <p>Track upcoming visits, recall completed follow-ups, and revisit canceled sessions.</p>
                    </div>
                    <div class="filter-pills">
                        <button class="filter-pill is-active" type="button" data-appt-filter="all">All</button>
                        <button class="filter-pill" type="button" data-appt-filter="upcoming">Upcoming</button>
                        <button class="filter-pill" type="button" data-appt-filter="completed">Completed</button>
                        <button class="filter-pill" type="button" data-appt-filter="canceled">Canceled</button>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['scheduled']); ?></strong>
                        <span>Upcoming Visits</span>
                    </div>
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['completed']); ?></strong>
                        <span>Completed</span>
                    </div>
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['cancelled']); ?></strong>
                        <span>Canceled</span>
                    </div>
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['total']); ?></strong>
                        <span>Total Tracked</span>
                    </div>
                </div>

                <div class="appointments-table-wrap">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Doctor</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $row): ?>
                            <tr data-status="<?= htmlspecialchars($row['status']); ?>">
                                <td data-label="Date">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($row['appointment_time']))); ?>
                                </td>
                                <td data-label="Time">
                                    <strong><?= date("H:i", strtotime($row['appointment_time'])); ?></strong>
                                </td>
                                <td data-label="Doctor">
                                    <strong><?= htmlspecialchars($row['doctor_name']); ?></strong>
                                </td>
                                <td data-label="Location">
                                    <strong> <?= htmlspecialchars($row['location']); ?></strong>
                                    <div class="location-details">

                                    </div>
                                </td>
                                <?php
                                    $status = (string) $row['status'];
                                    $status_text = ucfirst($status);
                                    $status_class = 'status-badge';

                                    if ($status === 'scheduled') {
                                        $status_text = 'Scheduled';
                                        $status_class .= ' status-upcoming';
                                    } elseif ($status === 'completed') {
                                        $status_text = 'Completed';
                                        $status_class .= ' status-completed';
                                    } elseif ($status === 'cancelled') {
                                        $status_text = 'Cancelled';
                                        $status_class .= ' status-canceled';
                                    } else {
                                        $status_class .= ' status-upcoming';
                                    }
                                ?>
                                <td data-label="Status">
                                    <span class="<?= htmlspecialchars($status_class); ?>">
                                        <i class="fa-solid fa-circle"></i>
                                        <?= htmlspecialchars($status_text); ?>
                                    </span>
                                </td>
                                <td data-label="action">
                                    <?php if ($row['status'] === 'scheduled'): ?>
                                    <form action="../php/cansel.php" method="post">
                                        <input name="appointment_id" type="hidden"
                                            value="<?= $row['appointment_id'] ?>">
                                        <button class="btn-action btn-action-danger" name="cancel" type="submit">
                                            <i class="fa-regular fa-circle-xmark"></i>
                                            Cancel appointment
                                        </button>
                                    </form>

                                    <?php elseif ($row['status'] === 'completed'): ?>
                                    <?php
                                        $existing_rating = (int)($row['existing_rating']);
                                    ?>
                                    <?php if ($existing_rating > 0): ?>
                                    <div class="rating-inline" aria-label="Your rating">
                                        <span class="rating-stars"
                                            aria-label="Rated <?= htmlspecialchars((string) $existing_rating); ?> out of 5">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $existing_rating): ?>
                                            <i class="fa-solid fa-star"></i>
                                            <?php else: ?>
                                            <i class="fa-regular fa-star"></i>
                                            <?php endif; ?>
                                            <?php endfor; ?>
                                        </span>

                                    </div>
                                    <?php endif; ?>

                                    <form action="rate_appointment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id"
                                            value="<?= htmlspecialchars((string)$row['appointment_id']); ?>">

                                        <button type="submit" class="btn-action btn-action-success">
                                            <i class="fa-solid fa-star"></i>

                                            <?php if ($existing_rating > 0): ?>
                                            <span>
                                                Your rate :
                                                <?= htmlspecialchars((string)$existing_rating); ?>/5
                                            </span>
                                            <?php else: ?>
                                            Rate appointment
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="muted">-</span>
                                    <?php endif?>
                                </td>


                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>



    <script src="../js/script.js"></script>
</body>

</html>
