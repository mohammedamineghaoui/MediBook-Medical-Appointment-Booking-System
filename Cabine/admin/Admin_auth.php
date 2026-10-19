<?php
session_start();
include '../php/conn.php';

if (isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id']) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $login_stmt = $conn->prepare("SELECT * FROM admins WHERE email_admin = ?");
        $login_stmt->bind_param("s", $email);
        $login_stmt->execute();
        $admin = $login_stmt->get_result()->fetch_assoc();

        if (!$admin) {
            $errors[] = 'No admin account found with that email.';
        } elseif (!password_verify($password, $admin['password_hash'])) {
            $errors[] = 'Incorrect password.';
        } else {
            $_SESSION['admin_id'] = (int) ($admin['admin_id'] ?? $admin['id'] ?? $admin['doctor_id'] ?? 0);
            $_SESSION['full_name'] = $admin['full_name'] ?? 'Admin';
            $_SESSION['email_admin'] = $admin['email_admin'] ?? $admin['email'] ?? $email;

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | MediBook</title>
    <meta name="description" content="Doctor login page">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../doctor_pages/style.css">
    <link rel="stylesheet" href="Admin_auth.css">
</head>

<body class="auth-body">
    <main class="auth-shell auth-shell-login">
        <section class="auth-brand">
            <div class="brand-mark"><i class="fa-solid fa-heart-pulse"></i></div>
            <h1>MediBook Admin Portal</h1>
            <p>Manage doctors, patients, and appointments from one secure dashboard.</p>
        </section>

        <section class="auth-card auth-card-login">
            <?php if (!empty($errors)) : ?>
            <div class="auth-alert error">
                <?php foreach ($errors as $error) : ?>
                <p><?=htmlspecialchars($error)?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="auth-panel auth-panel-login">
                <div class="panel-head">
                    <h2>Admin Login</h2>
                    <p>Use your Admin account to continue.</p>
                </div>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="login_email">Email</label>
                        <input type="email" id="login_email" name="login_email" placeholder="Admin@email.com">
                    </div>

                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <input type="password" id="login_password" name="login_password" placeholder="Password">
                    </div>

                    <button type="submit" class="btn-auth primary">Login</button>
                </form>
            </div>
        </section>
    </main>
</body>

</html>
