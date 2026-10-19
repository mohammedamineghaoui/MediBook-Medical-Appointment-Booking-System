<!DOCTYPE html>
<html lang="en">
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include('../php/conn.php');

$user_id = $_SESSION['user_id'];
$user_hashpassword = $_SESSION['password_hash'] ?? '**********';

$patient_stmt = $conn->prepare("SELECT full_name, phone, email, birth_date, avatar FROM patient WHERE user_id = ?");
$patient_stmt->bind_param("i", $user_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: ../pages/login.php");
    exit();
}

$patientFullName = $patient['full_name'] ?? ($_SESSION['full_name'] ?? 'unknown');
$patientPhone = $patient['phone'] ?? ($_SESSION['phone'] ?? 'unknown');
$patientEmail = $patient['email'] ?? ($_SESSION['email'] ?? 'unknown');
$patientBirthDate = $patient['birth_date'] ?? ($_SESSION['birth_date'] ?? '');
$patientAvatar = !empty($patient['avatar']) ? $patient['avatar'] : '../../images/user.png';
$patientInitials = strtoupper(substr(trim($patientFullName), 0, 1));

$_SESSION['full_name'] = $patientFullName;
$_SESSION['phone'] = $patientPhone;
$_SESSION['email'] = $patientEmail;
$_SESSION['birth_date'] = $patientBirthDate;
$_SESSION['avatar'] = $patientAvatar;

?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/editpage.css">
</head>

<body>
    <div class="profile-wrapper">
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <img src="<?=htmlspecialchars($patientAvatar, ENT_QUOTES)?>" alt="Profile photo">
            </div>
            <h2>Patient Profile</h2>
            <p>
                Update your personal information, contact details, and password
                from this page.
            </p>
        </div>

        <div class="profile-content">
            <div class="profile-header">
                <h1>Edit Profile</h1>
                <p>Keep your account information up to date.</p>
            </div>

            <form method="post" action="../php/saveeditprofile.php" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width avatar-field">
                        <label for="avatar_file">Profile Photo</label>
                        <div class="avatar-upload-row">
                            <img src="<?=htmlspecialchars($patientAvatar, ENT_QUOTES)?>" alt="Current profile photo"
                                class="avatar-preview">
                            <div>
                                <input type="file" name="avatar_file" id="avatar_file" accept="image/*">
                                <small>Upload a JPG, PNG, GIF, or WebP image.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" name="fullname" id="fullname"
                            value="<?=htmlspecialchars($patientFullName, ENT_QUOTES)?>"
                            placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone"
                            value="<?=htmlspecialchars($patientPhone, ENT_QUOTES)?>"
                            placeholder="Enter your phone number">
                    </div>

                    <div class="form-group full-width">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email"
                            value="<?=htmlspecialchars($patientEmail, ENT_QUOTES)?>"
                            placeholder="Enter your email address">
                    </div>
                    <div class="form-group">
                        <label for="birth_date">Birth Date</label>
                        <input type="date" name="birth_date" id="birth_date"
                            value="<?=htmlspecialchars($patientBirthDate, ENT_QUOTES)?>">
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="password-box">
                            <input type="password" name="password" id="password" placeholder="Enter new password">
                            <span class="password-icon">🔒</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-box">
                            <input name="confirmpassowrd" type="password" id="confirm-password"
                                placeholder="Confirm new password">
                            <span class="password-icon">🔒</span>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <a href="../pages/patient_dashboard.php" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
