<?php
include 'require_doctor.php';
include '../php/conn.php';

$doctor_id = $_SESSION['doctor_id'] ?? 5;
$search_term = trim($_GET['search'] ?? '');

$patients_sql = "SELECT
                    p.user_id,
                    p.full_name,
                    p.email,
                    p.phone,
                    p.birth_date,
                    p.created_at,
                    p.avatar,
                    COUNT(a.appointment_id) AS booking_count,
                    SUM(CASE WHEN a.doctor_id = ? THEN 1 ELSE 0 END) AS booking_count_with_doctor,
                    MAX(a.appointment_time) AS last_appointment
                FROM patient p
                LEFT JOIN appointments a ON a.patient_id = p.user_id
                WHERE (? = '' OR p.full_name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)
                GROUP BY p.user_id, p.full_name, p.email, p.phone, p.birth_date, p.created_at, p.avatar
                ORDER BY booking_count DESC, p.created_at DESC";
$patients_stmt = $conn->prepare($patients_sql);
$search_like = '%' . $search_term . '%';
$patients_stmt->bind_param("issss", $doctor_id, $search_term, $search_like, $search_like, $search_like);
$patients_stmt->execute();
$patients_result = $patients_stmt->get_result();

$patient_rows = [];
while ($patient = $patients_result->fetch_assoc()) {
    $patient_rows[] = $patient;
}

$history_map = [];
$history_sql = "SELECT
                    a.patient_id,
                    a.appointment_time,
                    a.status,
                    d.full_name AS doctor_name,
                    s.specialty_name
                FROM appointments a
                INNER JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                ORDER BY a.appointment_time DESC";
$history_result = $conn->query($history_sql);
if ($history_result) {
    while ($history = $history_result->fetch_assoc()) {
        $history_map[$history['patient_id']][] = [
            'doctor' => $history['doctor_name'],
            'specialty' => $history['specialty_name'] ?: 'Specialist',
            'time' => date('Y-m-d H:i', strtotime($history['appointment_time'])),
            'status' => $history['status'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients | MediBook Doctor</title>
    <meta name="description" content="Browse all patients and inspect their booking history">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patients.css">
</head>

<body>
    <div class="app-container">
        <?php
        $active_page = 'patients';
        include 'doctor_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Patients</p>
                        <h1 class="page-title">All Patients</h1>
                    </div>
                </div>
                <div class="header-right">
                    <form method="GET" class="search-bar">
                        <button type="submit" class="search-icon-btn" aria-label="Search patients">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <input type="text" name="search" id="patientSearch"
                            placeholder="Search by name, email, or phone"
                            value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                    </form>
                </div>
            </header>

            <section class="patients-page">
                <div class="patients-intro">
                    <div>
                        <h2>Patient Directory</h2>
                        <p>See every patient account, total bookings, and full booking history with doctors.</p>
                    </div>
                    <div class="patients-badge">
                        <i class="fa-solid fa-user-group"></i>
                        <span><?=count($patient_rows)?> Patients</span>
                    </div>
                </div>

                <div class="card patients-card">
                    <div class="table-responsive">
                        <table class="data-table patients-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Bookings</th>
                                    <th>With You</th>
                                    <th>Last Appointment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($patient_rows)) : ?>
                                <?php foreach ($patient_rows as $patient) :
                                    $avatar = !empty($patient['avatar']) ? $patient['avatar'] : '../../images/user.png';
                                    $history_json = htmlspecialchars(
                                        json_encode($history_map[$patient['user_id']] ?? [], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG),
                                        ENT_QUOTES
                                    );
                                ?>
                                <tr>
                                    <td>
                                        <div class="patient-cell">
                                            <img src="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($patient['full_name'], ENT_QUOTES)?>">
                                            <div class="patient-info">
                                                <span
                                                    class="patient-name"><?=htmlspecialchars($patient['full_name'])?></span>
                                                <span class="patient-id">ID #<?=$patient['user_id']?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?=htmlspecialchars($patient['email'] ?? '-')?></td>
                                    <td><?=htmlspecialchars($patient['phone'] ?? '-')?></td>
                                    <td><span class="count-badge"><?=$patient['booking_count']?></span></td>
                                    <td><span
                                            class="count-badge accent"><?=$patient['booking_count_with_doctor']?></span>
                                    </td>
                                    <td><?= $patient['last_appointment'] ? date('Y-m-d H:i', strtotime($patient['last_appointment'])) : 'No bookings' ?>
                                    </td>
                                    <td>
                                        <button class="btn-icon patient-view-btn" data-id="<?=$patient['user_id']?>"
                                            data-name="<?=htmlspecialchars($patient['full_name'], ENT_QUOTES)?>"
                                            data-email="<?=htmlspecialchars($patient['email'] ?? '', ENT_QUOTES)?>"
                                            data-phone="<?=htmlspecialchars($patient['phone'] ?? '', ENT_QUOTES)?>"
                                            data-birth="<?=htmlspecialchars($patient['birth_date'] ?? '', ENT_QUOTES)?>"
                                            data-created="<?=htmlspecialchars($patient['created_at'] ?? '', ENT_QUOTES)?>"
                                            data-bookings="<?=$patient['booking_count']?>"
                                            data-with-you="<?=$patient['booking_count_with_doctor']?>"
                                            data-avatar="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                            data-last="<?=htmlspecialchars($patient['last_appointment'] ? date('Y-m-d H:i', strtotime($patient['last_appointment'])) : 'No bookings', ENT_QUOTES)?>"
                                            data-history="<?=$history_json?>">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else : ?>
                                <tr>
                                    <td colspan="7" class="empty-state-cell">No patients found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="patient-modal" id="patientModal">
        <div class="patient-modal-card">
            <button class="modal-close" id="closePatientModal" aria-label="Close patient details">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="modal-head">
                <div class="modal-avatar">
                    <img src="../../images/user.png" alt="Patient" id="modalPatientAvatar">
                </div>
                <div>
                    <h2 id="modalPatientName">Patient Details</h2>
                    <p id="modalPatientMeta">Booking information</p>
                </div>
            </div>

            <div class="modal-grid">
                <div class="modal-info-card">
                    <h3>Patient Info</h3>
                    <div class="info-row"><span>Email</span><strong id="modalPatientEmail"></strong></div>
                    <div class="info-row"><span>Phone</span><strong id="modalPatientPhone"></strong></div>
                    <div class="info-row"><span>Birth Date</span><strong id="modalPatientBirth"></strong></div>
                    <div class="info-row"><span>Joined</span><strong id="modalPatientCreated"></strong></div>
                    <div class="info-row"><span>Total Bookings</span><strong id="modalPatientBookings"></strong></div>
                    <div class="info-row"><span>With You</span><strong id="modalPatientWithYou"></strong></div>
                    <div class="info-row"><span>Last Appointment</span><strong id="modalPatientLast"></strong></div>
                </div>

                <div class="modal-info-card">
                    <h3>Doctors Booked</h3>
                    <div class="history-list" id="modalPatientHistory"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
    <script src="patients.js"></script>
</body>

</html>
