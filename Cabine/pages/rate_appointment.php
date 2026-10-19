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
$appointment_status = $row['status'] ;





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
        $errors[] = 'Please select a rating (1-5 stars).';
    }

    if ($selected_recommend !== '' && !in_array($selected_recommend, ['yes', 'no'], true)) {
        $errors[] = 'Invalid recommendation value.';
    }

    if (strlen($comment) > 1000) {
        $errors[] = 'Comment is too long (max 1000 characters).';
    }

    if ($errors === []) {
        $success_message = 'Thanks! Your rating has been submitted.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Appointment | MediBook</title>
    <meta name="description" content="Rate your appointment with the doctor">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Base + Page CSS -->
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/rate_appointment.css">
</head>

<body class="rating-page">
    <?php include 'header.php'; ?>

    <main class="rating-main">
        <div class="container">
            <header class="rating-top">
                <div>
                    <p class="eyebrow">Feedback</p>
                    <h1 class="rating-title">Rate Appointment</h1>
                    <p class="rating-subtitle">Tell us how your appointment went.</p>
                </div>

                <div class="rating-top-actions">
                    <a class="btn btn-secondary" href="patient_dashboard.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to dashboard
                    </a>
                </div>
            </header>

            <section class="rating-wrap">
                <div class="rating-grid">
                    <div class="card appointment-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-regular fa-calendar-check"></i>
                                Appointment details
                            </div>
                            <?php if ($appointment_id > 0) : ?>
                            <div class="chip">#<?=htmlspecialchars((string) $appointment_id, ENT_QUOTES)?></div>
                            <?php endif; ?>
                        </div>

                        <dl class="details">
                            <div class="row">
                                <dt>Doctor</dt>
                                <dd><?=htmlspecialchars($doctor_name, ENT_QUOTES)?></dd>
                            </div>
                            <div class="row">
                                <dt>Patient</dt>
                                <dd><?=htmlspecialchars($patient_name, ENT_QUOTES)?></dd>
                            </div>
                            <div class="row">
                                <dt>Date & time</dt>
                                <dd><?=htmlspecialchars(date(" M j, Y , H:i", strtotime($appointment_time)), ENT_QUOTES)?>
                                </dd>
                            </div>
                            <div class="row">
                                <dt>Status</dt>
                                <dd><?=htmlspecialchars($appointment_status, ENT_QUOTES)?></dd>
                            </div>
                        </dl>


                    </div>

                    <div class="card form-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-star"></i>
                                Your rating
                            </div>
                        </div>

                        <?php if ($success_message !== '') : ?>
                        <div class="alert success is-dismissible" role="status">
                            <i class="fa-regular fa-circle-check"></i>
                            <div><?=htmlspecialchars($success_message, ENT_QUOTES)?></div>
                            <button class="alert-close" type="button" aria-label="Dismiss message" data-alert-close>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if ($errors !== []) : ?>
                        <div class="alert error is-dismissible" role="alert">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <div class="alert-title">Please fix the following:</div>
                                <ul class="alert-list">
                                    <?php foreach ($errors as $error) : ?>
                                    <li><?=htmlspecialchars($error, ENT_QUOTES)?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <button class="alert-close" type="button" aria-label="Dismiss message" data-alert-close>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <form method="post" class="rating-form" action="save_rating.php" novalidate>
                            <input type="hidden" name="appointment_id"
                                value="<?=htmlspecialchars((string) $appointment_id, ENT_QUOTES)?>">

                            <fieldset class="field">
                                <legend class="label">How was your appointment?</legend>
                                <div class="stars" role="radiogroup" aria-label="Rating (1 to 5 stars)">
                                    <input id="star5" type="radio" name="rating" value="5" <?= $selected_rating === 5 ? 'checked' : '' ?> required>
                                    <label for="star5" aria-label="5 stars">&#9733;</label>

                                    <input id="star4" type="radio" name="rating" value="4" <?= $selected_rating === 4 ? 'checked' : '' ?>>
                                    <label for="star4" aria-label="4 stars">&#9733;</label>

                                    <input id="star3" type="radio" name="rating" value="3" <?= $selected_rating === 3 ? 'checked' : '' ?>>
                                    <label for="star3" aria-label="3 stars">&#9733;</label>

                                    <input id="star2" type="radio" name="rating" value="2" <?= $selected_rating === 2 ? 'checked' : '' ?>>
                                    <label for="star2" aria-label="2 stars">&#9733;</label>

                                    <input id="star1" type="radio" name="rating" value="1" <?= $selected_rating === 1 ? 'checked' : '' ?>>
                                    <label for="star1" aria-label="1 star">&#9733;</label>
                                </div>
                                <div class="subtext">Tap a star to rate from 1 to 5.</div>
                            </fieldset>



                            <div class="field">
                                <label class="label" for="comment">Write a short review (optional)</label>
                                <textarea id="comment" name="comment" rows="6"
                                    placeholder="What went well? What can be improved?"
                                    maxlength="1000"><?=htmlspecialchars($comment, ENT_QUOTES)?></textarea>
                                <div class="subtext"><span id="charCount"><?=strlen($comment)?></span>/1000 characters
                                </div>
                            </div>

                            <div class="actions">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    Submit rating
                                </button>
                                <a class="btn btn-ghost"
                                    href="rate_appointment.php<?=($appointment_id > 0 ? ('?appointment_id=' . urlencode((string) $appointment_id)) : '')?>">
                                    Reset
                                </a>
                            </div>

                            <div class="privacy">
                                <i class="fa-solid fa-shield-halved"></i>
                                Your feedback helps improve care quality.
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
