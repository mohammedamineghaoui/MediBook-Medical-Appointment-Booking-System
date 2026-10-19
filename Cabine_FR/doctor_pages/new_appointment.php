<?php
include 'require_doctor.php';
$doctor_id = $_SESSION['doctor_id'] ?? 5;
include '../php/conn.php';

$message = '';
$message_type = '';

if (isset($_GET['success']) && $_GET['success'] === '1') {
    $message = 'Appointment created successfully.';
    $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $errors = [
        'missing_fields' => 'Please fill in all required fields.',
        'invalid_patient' => 'Select a valid patient account.',
        'past_time' => 'Appointment time must be in the future.',
        'not_working_day' => 'You do not work on the selected day.',
        'out_of_working_hours' => 'The selected time is outside your working hours.',
        'break_time' => 'The selected time is during your break.',
        'already_booked' => 'This time slot is already booked.',
        'patient_has_appointment' => 'This patient already has a Planifié appointment on that date.',
        'save_failed' => 'The appointment could not be saved.',
    ];

    $message = $errors[$_GET['error']] ?? 'Something went wrong.';
    $message_type = 'error';
}

$patients_sql = "SELECT user_id, full_name, email, phone
                 FROM patient
                 ORDER BY full_name ASC";
$patients_result = $conn->query($patients_sql);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau rendez-vous | MediBook Doctor</title>
    <meta name="description" content="Create a new appointment for an existing patient account">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="new_appointment.css">
</head>

<body>
    <div class="app-container">
        <?php
        $active_page = 'appointments';
        include 'doctor_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Appointments</p>
                        <h1 class="page-title">Create Nouveau rendez-vous</h1>
                    </div>
                </div>
                <div class="header-right">
                    <a href="appointments.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to appointments</span>
                    </a>
                </div>
            </header>

            <section class="new-appointment-page">
                <div class="intro-card">
                    <div>
                        <h2>Book an appointment for an existing patient</h2>
                        <p>Select a patient account already in the database, then choose date and time.</p>
                    </div>
                    <div class="intro-badge">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </div>
                </div>

                <?php if ($message !== '') : ?>
                <div class="flash-message <?=$message_type?>">
                    <i
                        class="fa-solid <?=$message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'?>"></i>
                    <span><?=$message?></span>
                </div>
                <?php endif; ?>

                <div class="form-card">
                    <form action="save_doctor_appointment.php" method="POST" class="appointment-form">
                        <input type="hidden" name="doctor_id" value="<?=$doctor_id?>">

                        <div class="form-group full-width">
                            <label for="patient_id">Patient Account</label>
                            <select name="patient_id" id="patient_id" required>
                                <option value="">Select a patient</option>
                                <?php while ($patient = $patients_result->fetch_assoc()) : ?>
                                <option value="<?=$patient['user_id']?>">
                                    <?=htmlspecialchars($patient['full_name'])?> -
                                    <?=htmlspecialchars($patient['email'])?> -
                                    <?=htmlspecialchars($patient['phone'])?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small>Only registered patient accounts can receive a new appointment.</small>
                        </div>

                        <div class="form-group">
                            <label for="appointment_date">Appointment Date</label>
                            <input type="date" id="appointment_date" name="appointment_date" min="<?=date('Y-m-d')?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="appointment_time">Appointment Time</label>
                            <input type="time" id="appointment_time" name="appointment_time" required>
                        </div>

                        <div class="form-actions full-width">
                            <a href="appointments.php" class="btn-light">Annuler</a>
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Create Appointment</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
</body>

</html>
