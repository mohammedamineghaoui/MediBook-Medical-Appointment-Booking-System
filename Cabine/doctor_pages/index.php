<!DOCTYPE html>
<html lang="en">
<?php
    include 'require_doctor.php';
    $doctor_id = $_SESSION['doctor_id'] ?? 5;
    include '../php/conn.php';

    $auto_complete_sql = "UPDATE appointments
        SET status = 'completed'
        WHERE doctor_id = ?
        AND status = 'scheduled'
        AND appointment_time < NOW()";
    $auto_complete_stmt = $conn->prepare($auto_complete_sql);
    $auto_complete_stmt->bind_param("i", $doctor_id);
    $auto_complete_stmt->execute();

    $sql_count_users = "SELECT COUNT(*) AS total_users FROM patient";
    $result = $conn->query($sql_count_users);
    $row_users = $result->fetch_assoc();
    /*-------------------------------------------------------------------------------*/
        $sql_count_ap = " SELECT COUNT(*) AS total_ap FROM appointments 
        where doctor_id = ? AND 
        status =  'scheduled' 
        AND DATE(appointment_time) = CURDATE() ";
        $stmt = $conn->prepare($sql_count_ap);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row_ap = $result->fetch_assoc();
    /*-------------------------------------------------------------------------------*/
    $sql_get_name = "SELECT full_name FROM doctors
                    WHERE doctor_id = ?";
    
    $stmt = $conn->prepare($sql_get_name);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row_name = $result->fetch_assoc();
    
    $sql = "SELECT a.*, p.full_name, p.avatar
        FROM appointments a
        JOIN patient p ON a.patient_id = p.user_id
        WHERE a.doctor_id = ?
        AND a.appointment_time >= CURDATE()
        AND a.appointment_time < CURDATE() + INTERVAL 1 DAY
        ORDER BY a.appointment_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "No appointments found.";
}

$recent_patients_sql = "SELECT
        p.user_id,
        p.full_name,
        p.birth_date,
        p.avatar,
        MAX(a.appointment_time) AS last_visit
    FROM appointments a
    INNER JOIN patient p ON a.patient_id = p.user_id
    WHERE a.doctor_id = ?
    GROUP BY p.user_id, p.full_name, p.birth_date, p.avatar
    ORDER BY last_visit DESC
    LIMIT 5";
$recent_patients_stmt = $conn->prepare($recent_patients_sql);
$recent_patients_stmt->bind_param("i", $doctor_id);
$recent_patients_stmt->execute();
$recent_patients_result = $recent_patients_stmt->get_result();









?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | MediBook</title>
    <meta name="description" content="Modern doctor dashboard for healthcare management">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="app-container">
        <?php
        $active_page = 'dashboard';
        include 'doctor_nav.php';
        ?>

        <!-- Main Content Area -->
        <main class="main-content">

            <!-- Dashboard Content -->
            <div class="dashboard-content">

                <!-- Welcome Banner -->
                <section class="welcome-banner">
                    <div class="welcome-text">
                        <h2>Good Morning,<?=$row_name['full_name']?>👋</h2>
                        <p>You have <span class="highlight">6 appointments</span> and <span class="highlight">3
                                meetings</span> scheduled for today.</p>
                    </div>
                    <div class="welcome-date" id="currentDate">
                        <!-- Date filled by JS -->
                    </div>
                </section>

                <!-- Summary Cards -->
                <section class="summary-cards">
                    <div class="card summary-card">
                        <div class="card-icon blue">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">Total Patients</span>
                            <h3 class="card-value"><?=$row_users['total_users']?></h3>
                        </div>
                    </div>

                    <div class="card summary-card">
                        <div class="card-icon green">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">Today's Appointments</span>
                            <h3 class="card-value"><?=$row_ap['total_ap']?></h3>
                        </div>
                    </div>



                    <div class="card summary-card">
                        <div class="card-icon purple">
                            <i class="fa-regular fa-message"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-label">New Messages</span>
                            <h3 class="card-value">5</h3>
                            <span class="trend neutral">From 3 patients</span>
                        </div>
                    </div>
                </section>

                <!-- Complex Layout Grid -->
                <div class="content-grid">

                    <!-- Left Column (Appointments & Recent Patients) -->
                    <div class="grid-col-left">

                        <!-- Appointments Table -->
                        <section class="card appointments-section">
                            <div class="card-header">
                                <h3>Today's Appointments</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php  while ($row = $result->fetch_assoc()) : ?>
                                        <tr>

                                            <td>
                                                <div class="patient-cell">
                                                    <img src="<?=htmlspecialchars($row['avatar'] ?: '../../images/user.png', ENT_QUOTES)?>"
                                                        alt="<?=htmlspecialchars($row['full_name'], ENT_QUOTES)?>">
                                                    <div class="patient-info">
                                                        <span class="patient-name"><?=$row['full_name']?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?=date('Y-m-d', strtotime($row['appointment_time']));?></td>
                                            <td><?=date('H:i', strtotime($row['appointment_time']));?></td>
                                            <td><span class="status-badge status-confirmed"><?=$row['status']?></span>
                                            </td>

                                            <td><button class="btn-icon"><i
                                                        class="fa-solid fa-ellipsis-vertical"></i></button></td>

                                        </tr>
                                        <?php  endwhile;
                                        ?>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- Recent Patients -->
                        <section class="card recent-patients-section">
                            <div class="card-header">
                                <h3>Recent Patients</h3>
                                <a href="patients.php" class="btn-link">View All</a>
                            </div>
                            <div class="patient-list">
                                <?php if ($recent_patients_result->num_rows > 0) : ?>
                                <?php while ($patient = $recent_patients_result->fetch_assoc()) :
                                    $avatar = !empty($patient['avatar']) ? $patient['avatar'] : '../../images/user.png';
                                    $age_text = 'Age not set';
                                    if (!empty($patient['birth_date'])) {
                                        $birth_date = new DateTime($patient['birth_date']);
                                        $age_text = $birth_date->diff(new DateTime())->y . ' yrs';
                                    }
                                ?>
                                <div class="patient-list-item">
                                    <div class="patient-item-left">
                                        <div class="patient-avatar">
                                            <img src="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($patient['full_name'], ENT_QUOTES)?>">
                                        </div>
                                        <div class="patient-details">
                                            <h4><?=htmlspecialchars($patient['full_name'])?></h4>
                                            <p><?=$age_text?></p>
                                        </div>
                                    </div>
                                    <div class="patient-item-right">
                                        <span class="last-visit">Last visit:
                                            <?=date('Y-m-d H:i', strtotime($patient['last_visit']))?></span>

                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php else : ?>
                                <div class="patient-list-item">
                                    <div class="patient-item-left">
                                        <div class="patient-details">
                                            <h4>No recent patients</h4>
                                            <p>This doctor has no patient visit history yet.</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (false) : ?>
                                <div class="patient-list-item">
                                    <div class="patient-item-left">
                                        <div class="patient-avatar">R</div>
                                        <div class="patient-details">
                                            <h4>Robert Johnson</h4>
                                            <p>45 yrs • Hypertension</p>
                                        </div>
                                    </div>
                                    <div class="patient-item-right">
                                        <span class="last-visit">Last visit: 2 days ago</span>
                                        <button class="btn-outline-small">View Record</button>
                                    </div>
                                </div>

                                <div class="patient-list-item">
                                    <div class="patient-item-left">
                                        <div class="patient-avatar bg-purple">S</div>
                                        <div class="patient-details">
                                            <h4>Sophia Miller</h4>
                                            <p>28 yrs • Routine Check</p>
                                        </div>
                                    </div>
                                    <div class="patient-item-right">
                                        <span class="last-visit">Last visit: 5 days ago</span>
                                        <button class="btn-outline-small">View Record</button>
                                    </div>
                                </div>

                                <div class="patient-list-item">
                                    <div class="patient-item-left">
                                        <div class="patient-avatar bg-green">D</div>
                                        <div class="patient-details">
                                            <h4>David Garcia</h4>
                                            <p>52 yrs • Cardiology Review</p>
                                        </div>
                                    </div>
                                    <div class="patient-item-right">
                                        <span class="last-visit">Last visit: 1 week ago</span>
                                        <button class="btn-outline-small">View Record</button>
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

    <!-- User Overlay for Mobile Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="script.js"></script>
</body>

</html>
