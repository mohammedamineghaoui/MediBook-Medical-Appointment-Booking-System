<?php
include 'require_doctor.php';
$doctor_id = $_SESSION['doctor_id'] ?? 5;
include '../php/conn.php';

$auto_complete_sql = "UPDATE appointments
                      SET status = 'Terminé'
                      WHERE doctor_id = ?
                      AND status = 'Planifié'
                      AND appointment_time < NOW()";
$auto_complete_stmt = $conn->prepare($auto_complete_sql);
$auto_complete_stmt->bind_param("i", $doctor_id);
$auto_complete_stmt->execute();

$show_all = !isset($_GET['date']) || (isset($_GET['view']) && $_GET['view'] === 'all');
$selected_status = $_GET['status'] ?? 'all';
$search_term = trim($_GET['search'] ?? '');
$selected_date = $_GET['date'] ?? date('Y-m-d');
$date_object = DateTime::createFromFormat('Y-m-d', $selected_date);

if (!$date_object || $date_object->format('Y-m-d') !== $selected_date) {
    $selected_date = date('Y-m-d');
}

if (!in_array($selected_status, ['all', 'Terminé', 'Planifié', 'Annulé'], true)) {
    $selected_status = 'all';
}

$date_filter_sql = $show_all ? '' : " AND DATE(appointment_time) = ?";
$date_filter_sql_alias = $show_all ? '' : " AND DATE(a.appointment_time) = ?";
$status_filter_sql_alias = $selected_status === 'all' ? '' : " AND a.status = ?";
$search_filter_sql_alias = $search_term === '' ? '' : " AND (p.full_name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)";

$sql_count_complete = "SELECT COUNT(*) AS total 
        FROM appointments 
        WHERE doctor_id = ? 
        AND status = 'Terminé'
        $date_filter_sql";

$stmt = $conn->prepare($sql_count_complete);
if ($show_all) {
    $stmt->bind_param("i", $doctor_id);
} else {
    $stmt->bind_param("is", $doctor_id, $selected_date);
}
$stmt->execute();

$result = $stmt->get_result();
$row_complete = $result->fetch_assoc();
/**************************************************** */
$sql_count_Planifié = "SELECT COUNT(*) AS total 
        FROM appointments 
        WHERE doctor_id = ? 
        AND status = 'Planifié'
        $date_filter_sql";

$stmt = $conn->prepare($sql_count_Planifié);
if ($show_all) {
    $stmt->bind_param("i", $doctor_id);
} else {
    $stmt->bind_param("is", $doctor_id, $selected_date);
}
$stmt->execute();

$result = $stmt->get_result();
$row_Planifié = $result->fetch_assoc();
/******************************************************** */
$sql_count_Annulé = "SELECT COUNT(*) AS total 
        FROM appointments 
        WHERE doctor_id = ? 
        AND status = 'Annulé'
        $date_filter_sql";

$stmt = $conn->prepare($sql_count_Annulé);
if ($show_all) {
    $stmt->bind_param("i", $doctor_id);
} else {
    $stmt->bind_param("is", $doctor_id, $selected_date);
}
$stmt->execute();

$result = $stmt->get_result();
$row_Annulé = $result->fetch_assoc();




$total = $row_Annulé['total'] + $row_complete['total'] + $row_Planifié['total'];
/***************************************************************************** */
$sql = "SELECT a.*, p.*
        FROM appointments a
        JOIN patient p ON a.patient_id = p.user_id
        WHERE a.doctor_id = ?
        $date_filter_sql_alias
        $status_filter_sql_alias
        $search_filter_sql_alias
        ORDER BY a.appointment_time DESC";

$stmt = $conn->prepare($sql);
if ($show_all && $selected_status === 'all' && $search_term === '') {
    $stmt->bind_param("i", $doctor_id);
} elseif ($show_all && $selected_status !== 'all' && $search_term === '') {
    $stmt->bind_param("is", $doctor_id, $selected_status);
} elseif (!$show_all && $selected_status === 'all' && $search_term === '') {
    $stmt->bind_param("is", $doctor_id, $selected_date);
} elseif (!$show_all && $selected_status !== 'all' && $search_term === '') {
    $stmt->bind_param("iss", $doctor_id, $selected_date, $selected_status);
} else {
    $search_like = '%' . $search_term . '%';

    if ($show_all && $selected_status === 'all') {
        $stmt->bind_param("isss", $doctor_id, $search_like, $search_like, $search_like);
    } elseif ($show_all) {
        $stmt->bind_param("issss", $doctor_id, $selected_status, $search_like, $search_like, $search_like);
    } elseif ($selected_status === 'all') {
        $stmt->bind_param("issss", $doctor_id, $selected_date, $search_like, $search_like, $search_like);
    } else {
        $stmt->bind_param("isssss", $doctor_id, $selected_date, $selected_status, $search_like, $search_like,
            $search_like);
    }
}
$stmt->execute();

$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | MediBook Doctor</title>
    <meta name="description" content="View and manage patient appointments">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="appointments.css">
</head>

<body>
    <div class="app-container">
        <?php
        $active_page = 'appointments';
        include 'doctor_nav.php';
        ?>

        <!-- Main Content Area -->
        <main class="main-content">

            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1 class="page-title">Appointments</h1>
                </div>

                <div class="header-right">
                    <form method="GET" class="search-bar">
                        <?php if ($show_all) : ?>
                        <input type="hidden" name="view" value="all">
                        <?php else : ?>
                        <input type="hidden" name="date" value="<?=$selected_date?>">
                        <?php endif; ?>
                        <?php if ($selected_status !== 'all') : ?>
                        <input type="hidden" name="status" value="<?=$selected_status?>">
                        <?php endif; ?>
                        <button type="submit" class="search-icon-btn" aria-label="Search appointments">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <input type="text" placeholder="Search by name, phone, or email" id="appointmentSearch"
                            name="search" value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                    </form>

                    <div class="header-actions">
                        <button class="icon-btn notification-btn">
                            <i class="fa-regular fa-bell"></i>
                            <span class="notification-dot"></span>
                        </button>


                    </div>
                </div>
            </header>

            <!-- Appointments Content -->
            <div class="appointments-page-content">

                <!-- Page Controls / Filters -->
                <div class="page-controls">
                    <div class="controls-right">
                        <form method="GET" class="filter-group">
                            <?php if ($selected_status !== 'all') : ?>
                            <input type="hidden" name="status" value="<?=$selected_status?>">
                            <?php endif; ?>
                            <?php if ($search_term !== '') : ?>
                            <input type="hidden" name="search" value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                            <?php endif; ?>
                            <div class="date-picker-wrap">
                                <i class="fa-regular fa-calendar"></i>
                                <input type="date" class="date-input" id="dateFilter" name="date"
                                    value="<?=$selected_date?>" onchange="this.form.submit()">
                            </div>
                        </form>
                        <a href="appointments.php?view=all<?php if ($selected_status !== 'all') : ?>&status=<?=$selected_status?><?php endif; ?><?php if ($search_term !== '') : ?>&search=<?=urlencode($search_term)?><?php endif; ?>"
                            class="btn-secondary">All Appointments</a>
                        <a href="new_appointment.php" class="btn-primary"><i class="fa-solid fa-plus"></i> New
                            Appointment</a>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="appointment-stats">
                    <a href="appointments.php?<?php
                        echo $show_all
                            ? 'view=all'
                            : 'date=' . urlencode($selected_date);
                        if ($search_term !== '') {
                            echo '&search=' . urlencode($search_term);
                        }
                    ?>" class="stat-box-link">
                        <div class="stat-box <?=$selected_status === 'all' ? 'active' : ''?>">
                            <div class="stat-icon bg-blue"><i class="fa-solid fa-calendar-day"></i></div>
                            <div class="stat-details">
                                <h3><?=$show_all ? 'All Appointments' : 'Total Today'?></h3>
                                <p class="stat-num"><?=$total?></p>
                            </div>
                        </div>
                    </a>
                    <a href="appointments.php?status=Terminé<?php if ($show_all) : ?>&view=all<?php else : ?>&date=<?=$selected_date?><?php endif; ?><?php if ($search_term !== '') : ?>&search=<?=urlencode($search_term)?><?php endif; ?>"
                        class="stat-box-link">
                        <div class="stat-box <?=$selected_status === 'Terminé' ? 'active' : ''?>">
                            <div class="stat-icon bg-green"><i class="fa-solid fa-check"></i></div>
                            <div class="stat-details">
                                <h3>Completed</h3>
                                <p class="stat-num"><?=$row_complete['total']?></p>
                            </div>
                        </div>
                    </a>
                    <a href="appointments.php?status=Planifié<?php if ($show_all) : ?>&view=all<?php else : ?>&date=<?=$selected_date?><?php endif; ?><?php if ($search_term !== '') : ?>&search=<?=urlencode($search_term)?><?php endif; ?>"
                        class="stat-box-link">
                        <div class="stat-box <?=$selected_status === 'Planifié' ? 'active' : ''?>">
                            <div class="stat-icon bg-orange"><i class="fa-solid fa-hourglass-half"></i></div>
                            <div class="stat-details">
                                <h3>Scheduled</h3>
                                <p class="stat-num"><?=$row_Planifié['total']?></p>
                            </div>
                        </div>
                    </a>
                    <a href="appointments.php?status=Annulé<?php if ($show_all) : ?>&view=all<?php else : ?>&date=<?=$selected_date?><?php endif; ?><?php if ($search_term !== '') : ?>&search=<?=urlencode($search_term)?><?php endif; ?>"
                        class="stat-box-link">
                        <div class="stat-box <?=$selected_status === 'Annulé' ? 'active' : ''?>">
                            <div class="stat-icon bg-red"><i class="fa-solid fa-xmark"></i></div>
                            <div class="stat-details">
                                <h3>Annulerled</h3>
                                <p class="stat-num"><?=$row_Annulé['total']?></p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Main Appointments Data -->
                <div class="card appointments-main-card">
                    <div class="table-responsive">
                        <table class="data-table all-appointments-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Date</th>
                                    <th>Patient Info</th>
                                    <th>Email</th>
                                    <th>phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentsList">
                                <?php if ($result->num_rows > 0) : ?>
                                <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td>
                                        <div class="time-cell">
                                            <span
                                                class="time-main"><?=date('H:i', strtotime($row['appointment_time']));?></span>
                                            <span class="time-duration"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-cell">
                                            <span
                                                class="time-main"><?=date('Y-m-d', strtotime($row['appointment_time']));?></span>
                                            <span class="time-duration"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="patient-cell">
                                            <img src="<?=htmlspecialchars($row['avatar'] ?: '../../images/user.png', ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($row['full_name'], ENT_QUOTES)?>">
                                            <div class="patient-info">
                                                <span class="patient-name"><?=$row['full_name']?></span>

                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="type-badge"><?=$row['email']?></span></td>
                                    <td><span class="type-badge"><?=$row['phone']?></span></td>
                                    <td>
                                        <span class="status-badge <?php
                                                echo $row['status'] === 'Annulé'
                                                    ? 'status-Annulé'
                                                    : ($row['status'] === 'Terminé' ? 'status-confirmed' : 'status-waiting');
                                            ?>">
                                            <?=$row['status']?>
                                        </span>
                                    </td>

                                    <td>
                                        <button class="btn-icon view-btn" data-id="<?=$row['appointment_id']?>"
                                            data-name="<?=$row['full_name']?>" data-email="<?=$row['email']?>"
                                            data-phone="<?=$row['phone']?>"
                                            data-time="<?=date('H:i', strtotime($row['appointment_time']))?>"
                                            data-date="<?=date('Y-m-d', strtotime($row['appointment_time']))?>"
                                            data-status="<?=$row['status']?>"
                                            data-createdat="<?=date($row['created_at'])?>" title="View Details">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" title="Edit"><i
                                                class="fa-regular fa-pen-to-square"></i></button>

                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else : ?>
                                <tr>
                                    <td colspan="7" class="empty-state-cell">
                                        Aucun rendez-vous trouvé for this filter.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>


                </div>

            </div>
        </main>
    </div>


    <!-- User Overlay for Mobile Sidebar -->

    <div class="popup" id="popup">
        <div class="popup-content">
            <span id="closePopup" class="close">&times;</span>
            <h2>Appointment Details</h2>

            <form action="update_appointment_status.php" method="POST">
                <input type="hidden" name="appointment_id" id="appointment_id">

                <p><strong>👤 Name:</strong> <span id="p_name"></span></p>
                <p><strong>📧 Email:</strong>
                    <a id="p_email" href="#" class="popup-link email-link"></a>
                </p>
                <p><strong>📞 Phone:</strong>
                    <a id="p_phone" href="#" class="popup-link phone-link"></a>
                </p>
                <p><strong>📅 Date:</strong> <span id="p_date"></span></p>
                <p><strong>⏰ Time:</strong> <span id="p_time"></span></p>
                <p><strong>Created At:</strong> <span id="p_createdAt"></span></p>

                <div class="status-row">
                    <label for="status"><strong>Status:</strong></label>
                    <select name="status" id="status" class="status-select">
                        <option value="Planifié">Scheduled</option>
                        <option value="Terminé">Completed</option>
                        <option value="Annulé">Annulerled</option>
                    </select>
                </div>

                <div class="popup-actions">
                    <button type="submit" class="save-btn">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
    <script src="appointments.js"></script>
    <script>

    </script>
</body>

</html>
