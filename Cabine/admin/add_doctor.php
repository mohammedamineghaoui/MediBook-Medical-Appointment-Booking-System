<?php
include 'require_admin.php';
include '../php/conn.php';

$errors = [];
$success_message = '';

$specialties = [];
$specialties_result = $conn->query("SELECT specialty_id, specialty_name FROM specialties ORDER BY specialty_name ASC");
if ($specialties_result) {
    while ($specialty = $specialties_result->fetch_assoc()) {
        $specialties[] = $specialty;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $specialty_id = isset($_POST['specialty_id']) && $_POST['specialty_id'] !== '' ? (int) $_POST['specialty_id'] : null;
    $experience = trim($_POST['experience'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $errors[] = 'Full name, email, and password are required.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($phone !== '') {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
            $errors[] = 'Enter a valid phone number.';
        }
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if ($experience !== '') {
        $experience_date = DateTime::createFromFormat('Y-m-d', $experience);
        if (!$experience_date || $experience_date->format('Y-m-d') !== $experience) {
            $errors[] = 'Select a valid experience date.';
        }
    }

    if (empty($errors)) {
        $email_check_stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE email = ?");
        $email_check_stmt->bind_param("s", $email);
        $email_check_stmt->execute();
        if ($email_check_stmt->get_result()->num_rows > 0) {
            $errors[] = 'This email is already used by another doctor.';
        }

        if ($phone !== '') {
            $phone_check_stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE phone = ?");
            $phone_check_stmt->bind_param("s", $phone);
            $phone_check_stmt->execute();
            if ($phone_check_stmt->get_result()->num_rows > 0) {
                $errors[] = 'This phone number is already used by another doctor.';
            }
        }
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $avatar = '../../images/user.png';
        $insert_sql = "INSERT INTO doctors (full_name, email, password_hash, phone, specialty_id, experience, location, avatar)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "ssssisss",
            $full_name,
            $email,
            $password_hash,
            $phone,
            $specialty_id,
            $experience,
            $location,
            $avatar
        );

        if ($insert_stmt->execute()) {
            $success_message = 'Doctor account created successfully.';
        } else {
            $errors[] = 'Could not create doctor account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor | MediBook</title>
    <meta name="description" content="Create another doctor account">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="add_doctor.css">
</head>

<body>
    <div class="app-container admin-app">
        <?php
        $active_page = 'add_doctor';
        include 'admin_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Doctor management</p>
                        <h1 class="page-title">Add Doctor</h1>
                    </div>
                </div>
            </header>

            <section class="add-doctor-page">
                <div class="intro-card">
                    <div>
                        <h2>Create a new doctor account</h2>
                        <p>Only a Admin can create another doctor account.</p>
                    </div>
                    <div class="intro-badge">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                </div>

                <?php if (!empty($errors)) : ?>
                <div class="flash-message error">
                    <?php foreach ($errors as $error) : ?>
                    <p><?=htmlspecialchars($error)?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($success_message !== '') : ?>
                <div class="flash-message success">
                    <p><?=htmlspecialchars($success_message)?></p>
                </div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST" class="doctor-form">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" placeholder="Doctor full name">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="doctor@email.com">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" placeholder="Phone number">
                        </div>

                        <div class="form-group">
                            <label for="specialty_id">Specialty</label>
                            <select id="specialty_id" name="specialty_id">
                                <option value="">Select specialty</option>
                                <?php foreach ($specialties as $specialty) : ?>
                                <option value="<?=$specialty['specialty_id']?>">
                                    <?=htmlspecialchars($specialty['specialty_name'])?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="experience">Experience Start Date</label>
                            <input type="date" id="experience" name="experience">
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="Clinic location">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Confirm password">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-user-plus"></i>
                                <span>Create Doctor</span>
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
