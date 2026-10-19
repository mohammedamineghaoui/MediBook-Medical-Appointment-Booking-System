<?php
include 'require_admin.php';
include '../php/conn.php';

$search_term = trim($_GET['search'] ?? '');

function format_experience(?string $experience): string
{
    if ($experience === null || trim($experience) === '') {
        return 'Not set';
    }

    if (is_numeric($experience)) {
        return (int) $experience . ' years';
    }

    try {
        $experience_date = new DateTime($experience);
        $years = $experience_date->diff(new DateTime())->y;
        return $years . ' years';
    } catch (Exception $exception) {
        return $experience;
    }
}

$doctors_sql = "SELECT
                    d.doctor_id,
                    d.full_name,
                    d.email,
                    d.phone,
                    d.experience,
                    d.location,
                    d.avatar,
                    d.created_at,
                    s.specialty_name,
                    COUNT(a.appointment_id) AS appointment_count,
                    SUM(CASE WHEN a.status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled_count,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
                    MAX(a.appointment_time) AS last_appointment
                FROM doctors d
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                LEFT JOIN appointments a ON a.doctor_id = d.doctor_id
                WHERE (? = ''
                    OR d.full_name LIKE ?
                    OR d.email LIKE ?
                    OR d.phone LIKE ?
                    OR d.location LIKE ?
                    OR s.specialty_name LIKE ?)
                GROUP BY d.doctor_id, d.full_name, d.email, d.phone, d.experience, d.location, d.avatar, d.created_at, s.specialty_name
                ORDER BY d.full_name ASC";
$doctors_stmt = $conn->prepare($doctors_sql);
$search_like = '%' . $search_term . '%';
$doctors_stmt->bind_param("ssssss", $search_term, $search_like, $search_like, $search_like, $search_like, $search_like);
$doctors_stmt->execute();
$doctors_result = $doctors_stmt->get_result();

$doctor_rows = [];
while ($doctor = $doctors_result->fetch_assoc()) {
    $doctor_rows[] = $doctor;
}

$history_map = [];
$history_sql = "SELECT
                    a.doctor_id,
                    a.appointment_time,
                    a.status,
                    p.full_name AS patient_name,
                    p.email AS patient_email
                FROM appointments a
                LEFT JOIN patient p ON a.patient_id = p.user_id
                ORDER BY a.appointment_time DESC";
$history_result = $conn->query($history_sql);
if ($history_result) {
    while ($history = $history_result->fetch_assoc()) {
        $history_map[$history['doctor_id']][] = [
            'patient' => $history['patient_name'] ?: 'Unknown patient',
            'email' => $history['patient_email'] ?: '-',
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
    <title>Doctors | MediBook Admin</title>
    <meta name="description" content="Manage doctors and inspect their appointment history">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patients.css">
</head>

<body>
    <div class="app-container admin-app">
        <?php
        $active_page = 'doctors';
        include 'admin_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle" aria-label="Open sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Doctors</p>
                        <h1 class="page-title">Manage Doctors</h1>
                    </div>
                </div>
                <div class="header-right">
                    <form method="GET" class="search-bar">
                        <button type="submit" class="search-icon-btn" aria-label="Search doctors">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <input type="text" name="search" placeholder="Search by name, email, specialty, or location"
                            value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                    </form>
                </div>
            </header>

            <section class="patients-page">
                <div class="patients-intro">
                    <div>
                        <h2>Doctor Directory</h2>
                        <p>See every doctor account, specialty, appointment totals, and recent patient activity.</p>
                    </div>
                    <div class="patients-badge">
                        <i class="fa-solid fa-user-doctor"></i>
                        <span><?=count($doctor_rows)?> Doctors</span>
                    </div>
                </div>

                <div class="card patients-card">
                    <div class="table-responsive">
                        <table class="data-table patients-table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Specialty</th>
                                    <th>Appointments</th>
                                    <th>Last Appointment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($doctor_rows)) : ?>
                                <?php foreach ($doctor_rows as $doctor) :
                                    $avatar = !empty($doctor['avatar']) ? $doctor['avatar'] : '../../images/user.png';
                                    $history_json = htmlspecialchars(
                                        json_encode($history_map[$doctor['doctor_id']] ?? [], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG),
                                        ENT_QUOTES
                                    );
                                    $experience_text = format_experience($doctor['experience'] ?? null);
                                ?>
                                <tr>
                                    <td>
                                        <div class="patient-cell">
                                            <img src="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($doctor['full_name'], ENT_QUOTES)?>">
                                            <div class="patient-info">
                                                <span class="patient-name"><?=htmlspecialchars($doctor['full_name'])?></span>
                                                <span class="patient-id">ID #<?=$doctor['doctor_id']?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?=htmlspecialchars($doctor['email'] ?? '-')?></td>
                                    <td><?=htmlspecialchars($doctor['phone'] ?? '-')?></td>
                                    <td><?=htmlspecialchars($doctor['specialty_name'] ?? 'No specialty')?></td>
                                    <td><span class="count-badge"><?=$doctor['appointment_count']?></span></td>
                                    <td><?= $doctor['last_appointment'] ? date('Y-m-d H:i', strtotime($doctor['last_appointment'])) : 'No bookings' ?></td>
                                    <td>
                                        <button class="btn-icon doctor-view-btn" data-id="<?=$doctor['doctor_id']?>"
                                            data-name="<?=htmlspecialchars($doctor['full_name'], ENT_QUOTES)?>"
                                            data-email="<?=htmlspecialchars($doctor['email'] ?? '', ENT_QUOTES)?>"
                                            data-phone="<?=htmlspecialchars($doctor['phone'] ?? '', ENT_QUOTES)?>"
                                            data-specialty="<?=htmlspecialchars($doctor['specialty_name'] ?? 'No specialty', ENT_QUOTES)?>"
                                            data-location="<?=htmlspecialchars($doctor['location'] ?? 'No location', ENT_QUOTES)?>"
                                            data-experience="<?=htmlspecialchars($experience_text, ENT_QUOTES)?>"
                                            data-created="<?=htmlspecialchars($doctor['created_at'] ?? '', ENT_QUOTES)?>"
                                            data-bookings="<?=$doctor['appointment_count']?>"
                                            data-scheduled="<?=$doctor['scheduled_count'] ?? 0?>"
                                            data-completed="<?=$doctor['completed_count'] ?? 0?>"
                                            data-cancelled="<?=$doctor['cancelled_count'] ?? 0?>"
                                            data-avatar="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                            data-last="<?=htmlspecialchars($doctor['last_appointment'] ? date('Y-m-d H:i', strtotime($doctor['last_appointment'])) : 'No bookings', ENT_QUOTES)?>"
                                            data-history="<?=$history_json?>">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else : ?>
                                <tr>
                                    <td colspan="7" class="empty-state-cell">No doctors found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="patient-modal" id="doctorModal">
        <div class="patient-modal-card">
            <button class="modal-close" id="closeDoctorModal" aria-label="Close doctor details">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="modal-head">
                <div class="modal-avatar">
                    <img src="../../images/user.png" alt="Doctor" id="modalDoctorAvatar">
                </div>
                <div>
                    <h2 id="modalDoctorName">Doctor Details</h2>
                    <p id="modalDoctorMeta">Appointment information</p>
                </div>
            </div>

            <div class="modal-grid">
                <div class="modal-info-card">
                    <h3>Doctor Info</h3>
                    <div class="info-row"><span>Email</span><strong id="modalDoctorEmail"></strong></div>
                    <div class="info-row"><span>Phone</span><strong id="modalDoctorPhone"></strong></div>
                    <div class="info-row"><span>Specialty</span><strong id="modalDoctorSpecialty"></strong></div>
                    <div class="info-row"><span>Location</span><strong id="modalDoctorLocation"></strong></div>
                    <div class="info-row"><span>Experience</span><strong id="modalDoctorExperience"></strong></div>
                    <div class="info-row"><span>Joined</span><strong id="modalDoctorCreated"></strong></div>
                    <div class="info-row"><span>Total Appointments</span><strong id="modalDoctorBookings"></strong></div>
                    <div class="info-row"><span>Scheduled</span><strong id="modalDoctorScheduled"></strong></div>
                    <div class="info-row"><span>Completed</span><strong id="modalDoctorCompleted"></strong></div>
                    <div class="info-row"><span>Cancelled</span><strong id="modalDoctorCancelled"></strong></div>
                    <div class="info-row"><span>Last Appointment</span><strong id="modalDoctorLast"></strong></div>
                </div>

                <div class="modal-info-card">
                    <h3>Recent Appointments</h3>
                    <div class="history-list" id="modalDoctorHistory"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
    <script src="doctors.js"></script>
</body>

</html>
