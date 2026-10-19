<?php
include('../php/conn.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$appointment_id = (int)($_POST['appointment_id'] ?? ($_GET['appointment_id'] ?? 0));
$sql_get_appointment = "SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name
    FROM appointments a
    JOIN patient p ON a.patient_id = p.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.appointment_id = ?";
$stmt = $conn->prepare($sql_get_appointment);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();
$doctor_name = $row['doctor_name'];
$patient_name = $row['patient_name'];
$appointment_time = $row['appointment_time'];  
$appointment_status = $row['status'];

$errors = [];
$success_message = '';
$selected_rating = 0;
$selected_recommend = '';
$comment = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int) ($_POST['appointment_id'] ?? $appointment_id);
    $selected_rating = (int) ($_POST['rating'] ?? 0);
    $selected_recommend = (string) ($_POST['recommend'] ?? '');
    $comment = trim((string) ($_POST['comment'] ?? ''));

    if ($selected_rating < 1 || $selected_rating > 5) {
        $errors[] = 'Veuillez sélectionner une note (1 à 5 étoiles).';
    }

    if ($selected_recommend !== '' && !in_array($selected_recommend, ['yes', 'no'], true)) {
        $errors[] = 'Valeur de recommandation invalide.';
    }

    if (strlen($comment) > 1000) {
        $errors[] = 'Le commentaire est trop long (max 1000 caractères).';
    }

    if ($errors === []) {
        $success_message = 'Merci ! Votre évaluation a bien été soumise.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluer le rendez-vous | MediBook</title>
    <meta name="description" content="Évaluez votre rendez-vous avec le médecin">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/rate_appointment.css">
</head>

<body class="rating-page">
    <?php include 'header.php'; ?>

    <main class="rating-main">
        <div class="container">
            <header class="rating-top">
                <div>
                    <p class="eyebrow">Avis</p>
                    <h1 class="rating-title">Évaluer le rendez-vous</h1>
                    <p class="rating-subtitle">Dites-nous comment s'est passé votre rendez-vous.</p>
                </div>

                <div class="rating-top-actions">
                    <a class="btn btn-secondary" href="patient_dashboard.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        Retour au tableau de bord
                    </a>
                </div>
            </header>

            <section class="rating-wrap">
                <div class="rating-grid">
                    <div class="card appointment-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-regular fa-calendar-check"></i>
                                Détails du rendez-vous
                            </div>
                            <?php if ($appointment_id > 0) : ?>
                            <div class="chip">#<?=htmlspecialchars((string) $appointment_id, ENT_QUOTES)?></div>
                            <?php endif; ?>
                        </div>

                        <dl class="details">
                            <div class="row">
                                <dt>Médecin</dt>
                                <dd><?=htmlspecialchars($doctor_name, ENT_QUOTES)?></dd>
                            </div>
                            <div class="row">
                                <dt>Patient</dt>
                                <dd><?=htmlspecialchars($patient_name, ENT_QUOTES)?></dd>
                            </div>
                            <div class="row">
                                <dt>Date et heure</dt>
                                <dd><?=htmlspecialchars(date("d M Y, H:i", strtotime($appointment_time)), ENT_QUOTES)?></dd>
                            </div>
                            <div class="row">
                                <dt>Statut</dt>
                                <dd><?=htmlspecialchars($appointment_status, ENT_QUOTES)?></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="card form-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-star"></i>
                                Votre évaluation
                            </div>
                        </div>

                        <?php if ($success_message !== '') : ?>
                        <div class="alert success is-dismissible" role="status">
                            <i class="fa-regular fa-circle-check"></i>
                            <div><?=htmlspecialchars($success_message, ENT_QUOTES)?></div>
                            <button class="alert-close" type="button" aria-label="Fermer le message" data-alert-close>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if ($errors !== []) : ?>
                        <div class="alert error is-dismissible" role="alert">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <div class="alert-title">Veuillez corriger les erreurs suivantes :</div>
                                <ul class="alert-list">
                                    <?php foreach ($errors as $error) : ?>
                                    <li><?=htmlspecialchars($error, ENT_QUOTES)?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <button class="alert-close" type="button" aria-label="Fermer le message" data-alert-close>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <form method="post" class="rating-form" action="save_rating.php" novalidate>
                            <input type="hidden" name="appointment_id"
                                value="<?=htmlspecialchars((string) $appointment_id, ENT_QUOTES)?>">

                            <fieldset class="field">
                                <legend class="label">Comment s'est passé votre rendez-vous ?</legend>
                                <div class="stars" role="radiogroup" aria-label="Note (1 à 5 étoiles)">
                                    <input id="star5" type="radio" name="rating" value="5" <?= $selected_rating === 5 ? 'checked' : '' ?> required>
                                    <label for="star5" aria-label="5 étoiles">&#9733;</label>

                                    <input id="star4" type="radio" name="rating" value="4" <?= $selected_rating === 4 ? 'checked' : '' ?>>
                                    <label for="star4" aria-label="4 étoiles">&#9733;</label>

                                    <input id="star3" type="radio" name="rating" value="3" <?= $selected_rating === 3 ? 'checked' : '' ?>>
                                    <label for="star3" aria-label="3 étoiles">&#9733;</label>

                                    <input id="star2" type="radio" name="rating" value="2" <?= $selected_rating === 2 ? 'checked' : '' ?>>
                                    <label for="star2" aria-label="2 étoiles">&#9733;</label>

                                    <input id="star1" type="radio" name="rating" value="1" <?= $selected_rating === 1 ? 'checked' : '' ?>>
                                    <label for="star1" aria-label="1 étoile">&#9733;</label>
                                </div>
                                <div class="subtext">Cliquez sur une étoile pour noter de 1 à 5.</div>
                            </fieldset>

                            <div class="field">
                                <label class="label" for="comment">Rédigez un avis court (optionnel)</label>
                                <textarea id="comment" name="comment" rows="6"
                                    placeholder="Qu'est-ce qui s'est bien passé ? Qu'est-ce qui pourrait être amélioré ?"
                                    maxlength="1000"><?=htmlspecialchars($comment, ENT_QUOTES)?></textarea>
                                <div class="subtext"><span id="charCount"><?=strlen($comment)?></span>/1000 caractères</div>
                            </div>

                            <div class="actions">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    Soumettre l'évaluation
                                </button>
                                <a class="btn btn-ghost"
                                    href="rate_appointment.php<?=($appointment_id > 0 ? ('?appointment_id=' . urlencode((string) $appointment_id)) : '')?>">
                                    Réinitialiser
                                </a>
                            </div>

                            <div class="privacy">
                                <i class="fa-solid fa-shield-halved"></i>
                                Votre avis contribue à améliorer la qualité des soins.
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>

</html>
