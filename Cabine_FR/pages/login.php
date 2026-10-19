<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MediBook</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<?php
include '../php/conn.php';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "L'adresse e-mail et le mot de passe sont obligatoires.";
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
                $errors[] = "Mot de passe incorrect.";
            }
        } else {
            $errors[] = "Aucun compte trouvé avec cet e-mail.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>


<body>
    <?php include 'header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <?php
                            if(count($errors) > 0) {
                               foreach($errors as $error) {
                                    echo "<div class='error'>$error</div>";
                                }       
                            }
                ?>

                <div class="auth-form active" id="loginForm">
                    <div class="auth-header">
                        <h1>Bon retour parmi nous</h1>
                        <p>Connectez-vous à votre compte pour gérer vos rendez-vous</p>
                    </div>
                    <form id="loginFormElement" action="login.php" method="POST">
                        <div class="form-group">
                            <label for="loginEmail"><i class="fas fa-envelope"></i> Adresse e-mail</label>
                            <input type="email" id="loginEmail" name="email" placeholder="Entrez votre e-mail">
                        </div>
                        <div class="form-group">
                            <label for="loginPassword"><i class="fas fa-lock"></i> Mot de passe</label>
                            <div class="password-input">
                                <input type="password" id="loginPassword" name="password"
                                    placeholder="Entrez votre mot de passe">
                                <button type="button" name="singin" class="toggle-password"
                                    onclick="togglePassword('loginPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember">
                                <span>Se souvenir de moi</span>
                            </label>
                            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                        </div>
                        <button type="submit" name="login" class="btn-primary btn-large btn-full">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                        <div class="auth-divider">
                            <span>ou</span>
                        </div>
                        <div class="social-login">
                            <button type="button" class="btn-social">
                                Nouveau ici ?
                                <a class="fab" href="registration.php">Créer un compte</a>
                            </button>
                        </div>
                    </form>
                </div>

    </section>

    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>MediBook</span>
                    </div>
                    <p>Votre partenaire de confiance en gestion de la santé.</p>
                </div>
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="index.php#doctors">Médecins</a></li>
                        <li><a href="index.php#specialties">Spécialités</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Informations de contact</h3>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Allée du Centre Médical, Ville Santé, VS 12345</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@medibook.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MediBook. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="../js/login.js"></script>
</body>

</html>
