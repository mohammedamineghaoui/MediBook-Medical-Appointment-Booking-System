<?php
include '../php/conn.php'; 

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$dateOfBirth = $_POST['dateOfBirth'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$phone = $_POST['phone'] ?? '';

// التحقق من الحقول
if(empty($fullname) || empty($email) || empty($phone) || empty($dateOfBirth) || empty($password)) {

$errors[] = "All fields are required.";
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$errors[] = "Email is not valid.";
}


$phone = preg_replace("/[^0-9+]/", "", $_POST['phone']);
if (!preg_match("/^\+?[0-9]{8,15}$/", $phone)) {
$errors[] = "Please enter a valid phone number (8–15 digits).";
}

if(strlen($password) < 8) { $errors[]="Password must be at least 8 characters long." ; } if($password
    !==$confirmPassword) { $errors[]="Passwords do not match." ; }
    $sqlCheck="SELECT user_id FROM patient WHERE email = ?" ; $stmtCheck=$conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if($stmtCheck->num_rows > 0){
    $errors[] = "This email is already registered.";
    }
    $stmtCheck->close();
    // phone number uniqueness check
    $sqlCheckPhone = "SELECT user_id FROM patient WHERE phone = ?";
    $stmtCheckPhone = $conn->prepare($sqlCheckPhone);
    $stmtCheckPhone->bind_param("s", $phone);
    $stmtCheckPhone->execute();
    $stmtCheckPhone->store_result();
    if($stmtCheckPhone->num_rows > 0){
    $errors[] = "This phone number is already registered.";
    }
    $stmtCheckPhone->close();

    if(count($errors) === 0) {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $defaultAvatar = '../../images/user.png';
    $sql = "INSERT INTO patient (full_name, email, phone, birth_date, password_hash, avatar)
    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $fullname, $email, $phone, $dateOfBirth, $passwordHash, $defaultAvatar);
    if($stmt->execute()) {
    $successMsg = "User registered successfully!";
    session_start();

    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['full_name'] = $fullname;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['birth_date'] = $dateOfBirth;
    $_SESSION['avatar'] = $defaultAvatar;
    header("Location: index.php");
    exit;
    $fullname = $email = $phone = $dateOfBirth = $password = $confirmPassword = '';
    } else {
    $errors[] = "Error: " . $stmt->error;
    }
    $stmt->close();
    }
    $conn->close();
    }
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MediBook</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>


<body>
    <section class="auth-section registration-page">
        <div class="container">
            <div class="auth-container registration-container">
                <?php if(count($errors) > 0): ?>
                <div class="auth-messages">
                    <?php foreach($errors as $error): ?>
                    <div class="error"><?= htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="auth-form active registration-form" id="registerForm">
                    <div class="auth-header">
                        <div class="auth-icon"><i class="fas fa-user-plus"></i></div>
                        <h1>Create Account</h1>
                        <p>Sign up to start booking appointments</p>
                    </div>

                    <form id="registerFormElement" action="registration.php" method="POST">
                        <div class="form-group">
                            <label for="firstName"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="firstName" name="fullname" placeholder="Full name"
                                value="<?= htmlspecialchars($fullname ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="registerEmail"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" id="registerEmail" name="email" placeholder="Enter your email"
                                    value="<?= htmlspecialchars($email ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number"
                                    value="<?= htmlspecialchars($phone ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="dateOfBirth"><i class="fas fa-calendar"></i> Date of Birth</label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth"
                                value="<?= htmlspecialchars($dateOfBirth ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="registerPassword"><i class="fas fa-lock"></i> Password</label>
                                <div class="password-input">
                                    <input type="password" id="registerPassword" name="password"
                                        placeholder="Create a password">
                                    <button type="button" class="toggle-password"
                                        onclick="togglePassword('registerPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="password-hint">Must be at least 8 characters.</small>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password</label>
                                <div class="password-input">
                                    <input type="password" id="confirmPassword" name="confirmPassword"
                                        placeholder="Confirm your password">
                                    <button type="button" class="toggle-password"
                                        onclick="togglePassword('confirmPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <label class="checkbox-label registration-terms">
                            <input type="checkbox" name="terms">
                            <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy
                                    Policy</a></span>
                        </label>

                        <button type="submit" name="register" class="btn-primary btn-large btn-full">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>

                        <p class="auth-switch">Already have an account? <a href="login.php">Login</a></p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="../js/login.js"></script>

</body>

</html>
