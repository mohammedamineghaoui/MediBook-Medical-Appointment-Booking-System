<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediBook</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>




<!---------------------------------------->
<!----- PHP Code for singin Handling ----->
<!---------------------------------------->
<?php
include '../php/conn.php';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        $sql = "SELECT user_id, full_name, phone, email, password_hash, avatar, birth_date FROM patient WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $fullName, $phone, $userEmail, $passwordHash, $avatar, $birthDate);
            $stmt->fetch();

            if (password_verify($password, $passwordHash)) {
                session_start();

                $_SESSION['user_id'] = $userId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['phone'] = $phone;
                $_SESSION['email'] = $userEmail;
                $_SESSION['avatar'] = $avatar ?: '../../images/user.png';
                $_SESSION['birth_date'] = $birthDate;

                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No account found with that email.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>


<body>
    <!-- Header Section -->

    <?php
            include 'header.php';
    ?>


    <!-- Login/Register Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <!-- رسائل الخطأ -->
                <?php
                            if(count($errors) > 0) {
                               foreach($errors as $error) {
                                    echo "<div class='error'>$error</div>";
                                }       
                            }
                ?>


                <!-- Login Form -->
                <div class="auth-form active" id="loginForm">
                    <div class="auth-header">
                        <h1>Welcome Back</h1>
                        <p>Login to your account to manage your appointments</p>
                    </div>
                    <form id="loginFormElement" action="login.php" method="POST">
                        <div class="form-group">
                            <label for="loginEmail"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="loginEmail" name="email" placeholder="Enter your email">
                        </div>
                        <div class="form-group">
                            <label for="loginPassword"><i class="fas fa-lock"></i> Password</label>
                            <div class="password-input">
                                <input type="password" id="loginPassword" name="password"
                                    placeholder="Enter your password">
                                <button type="button" name="singin" class="toggle-password"
                                    onclick="togglePassword('loginPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>
                            <a href="#" class="forgot-password">Forgot Password?</a>
                        </div>
                        <button type="submit" name="login" class="btn-primary btn-large btn-full">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                        <div class="auth-divider">
                            <span>or</span>
                        </div>
                        <div class="social-login">
                            <button type="button" class="btn-social">
                                New here ?
                                <a class="fab" href="registration.php">register new</a>
                            </button>
                        </div>
                    </form>
                </div>


    </section>

    <!-- Footer Section -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>MediBook</span>
                    </div>
                    <p>Your trusted partner in healthcare management.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="index.html#doctors">Doctors</a></li>
                        <li><a href="index.html#specialties">Specialties</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Information</h3>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Medical Center Drive, Health City, HC 12345</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@medibook.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MediBook. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../js/login.js"></script>
</body>

</html>