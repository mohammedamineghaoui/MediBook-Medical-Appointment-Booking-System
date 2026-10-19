<?php
session_start();
include '../php/conn.php';

if (isset($_SESSION['doctor_id']) && (int) $_SESSION['doctor_id']) {
    header('Lieu: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $login_stmt = $conn->prepare("SELECT doctor_id, full_name, email, phone, password_hash FROM doctors WHERE email = ?");
        $login_stmt->bind_param("s", $email);
        $login_stmt->execute();
        $doctor = $login_stmt->get_result()->fetch_assoc();

        if (!$doctor) {
            $errors[] = 'No doctor account found with that email.';
        } elseif (!password_verify($password, $doctor['password_hash'])) {
            $errors[] = 'Incorrect password.';
        } else {
            $_SESSION['doctor_id'] = (int) $doctor['doctor_id'];
            $_SESSION['doctor_name'] = $doctor['full_name'];
            $_SESSION['doctor_email'] = $doctor['email'];
            $_SESSION['doctor_phone'] = $doctor['phone'];

            header('Lieu: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion médecin | MediBook</title>
    <meta name="description" content="Doctor login page">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="doctor_auth.css">
</head>

<body class="auth-body">
    <main class="auth-shell auth-shell-login">
        <section class="auth-brand">
            <div class="brand-mark"><i class="fa-solid fa-heart-pulse"></i></div>
            <h1>MediBook Doctor Portal</h1>
            <p>Sign in to access the dashboard, appointments, profile settings, and add other doctor accounts.</p>
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
                    <h2>Connexion médecin</h2>
                    <p>Use your doctor account to continue.</p>
                </div>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="login_email">Email</label>
                        <input type="email" id="login_email" name="login_email" placeholder="doctor@email.com">
                    </div>

                    <div class="form-group">
                        <label for="login_password">Mot de passe</label>
                        <input type="password" id="login_password" name="login_password" placeholder="Mot de passe">
                    </div>

                    <button type="submit" class="btn-auth primary">Login</button>
                </form>
            </div>
        </section>
    </main>
</body>

</html>
