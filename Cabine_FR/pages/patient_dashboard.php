<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include('../php/conn.php');

$patientFullName = $_SESSION['full_name'] ?? 'Inconnu';
$patientPhone = $_SESSION['phone'] ?? 'Inconnu';
$patientEmail = $_SESSION['email'] ?? 'Inconnu';
$patientbirthday = $_SESSION['birth_date'] ?? 'Inconnu';
$user_id = $_SESSION['user_id'];
$user_hashpassword = $_SESSION['password_hash'] ?? '**********';
$patientAvatar = $_SESSION['avatar'] ?? '../../images/user.png';
$patientInitials = strtoupper(substr(trim($patientFullName), 0, 1));

$sql = " SELECT 
            a.appointment_id,
            a.doctor_id,
            a.appointment_time,
            a.status,
            d.full_name AS doctor_name,
            d.location,
            r.rating_id AS existing_rating_id,
            r.rating AS existing_rating
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN ratings r ON r.appointment_id = a.appointment_id
        WHERE a.patient_id = ?
        ORDER BY FIELD(a.status, 'scheduled', 'completed', 'cancelled')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$appointments = [];
$summaryCounts = [
    'scheduled' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'total' => 0,
];

while ($appointment = $result->fetch_assoc()) {
    $appointments[] = $appointment;
    $summaryCounts['total']++;
    if (isset($summaryCounts[$appointment['status']])) {
        $summaryCounts[$appointment['status']]++;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord patient | MediBook</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="dashboard-page">
    <?php include 'header.php'; ?>

    <main class="dashboard-shell">
        <button class="dashboard-sidebar-toggle" id="dashboardSidebarToggle" type="button"
            aria-controls="dashboardSidebar" aria-expanded="false" aria-label="Ouvrir la navigation du tableau de bord">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </button>

        <aside class="dashboard-sidebar patient-dashboard-nav" id="dashboardSidebar">
            <div class="sidebar-brand-row">
                <a href="patient_dashboard.php" class="sidebar-brand" title="MediBook">
                    <i class="fas fa-heartbeat"></i>
                    <span>MediBook</span>
                </a>
                <button class="dashboard-pin-sidebar" id="dashboardPinSidebar" type="button"
                    aria-label="Garder la barre latérale ouverte" aria-pressed="false">
                    <i class="fa-solid fa-thumbtack"></i>
                </button>
            </div>

            <nav class="sidebar-nav patient-nav-links" aria-label="Navigation du tableau de bord patient">
                <a href="patient_dashboard.php" class="is-active" title="Tableau de bord">
                    <i class="fa-solid fa-house"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="#profile" title="Profil">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Profil</span>
                </a>
                <a href="#appointments" title="Rendez-vous">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Rendez-vous</span>
                </a>
                <a href="../php/editprofile.php" title="Modifier le profil">
                    <i class="fa-solid fa-user-pen"></i>
                    <span>Modifier le profil</span>
                </a>
            </nav>

            <div class="patient-nav-footer">
                <a href="/Cabine/php/logout.php" class="dashboard-logout" title="Déconnexion">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <div class="dashboard-sidebar-overlay" id="dashboardSidebarOverlay"></div>

        <section class="dashboard-main">
            <?php if (isset($_SESSION['message'])): ?>
            <?php
                $is_success = (($_SESSION['message_type'] ?? '') === 'success');
                $alert_class = $is_success ? 'success' : 'error';
                $alert_role = $is_success ? 'status' : 'alert';
                $alert_icon = $is_success ? 'fa-regular fa-circle-check' : 'fa-solid fa-triangle-exclamation';
                $message = (string) ($_SESSION['message'] ?? '');
                unset($_SESSION['message'], $_SESSION['message_type']);
            ?>
            <div class="alert dashboard-alert is-dismissible <?= htmlspecialchars($alert_class); ?>"
                role="<?= htmlspecialchars($alert_role); ?>">
                <i class="<?= htmlspecialchars($alert_icon); ?>"></i>
                <div><?= htmlspecialchars($message); ?></div>
                <button class="alert-close" type="button" aria-label="Fermer le message" data-alert-close>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php endif; ?>
            <div class="dashboard-top-bar">
                <div class="dashboard-title-group">
                    <p class="eyebrow">Espace Patient</p>
                    <div>
                        <h1>Tableau de bord patient</h1>
                        <p class="top-subtitle">Bon retour, <strong><?= htmlspecialchars($patientFullName); ?></strong></p>
                    </div>
                </div>
            </div>

            <article id="profile" class="card-panel patient-profile-card">
                <div class="card-header">
                    <div class="profile-title-row">
                        <div class="profile-photo-large">
                            <img src="<?= htmlspecialchars($patientAvatar, ENT_QUOTES); ?>" alt="Photo de profil">
                        </div>
                        <div>
                            <h2>Votre profil</h2>
                            <p>Mettez à jour vos informations depuis « Modifier le profil ».</p>
                        </div>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="profile-detail-card">
                        <span><i class="fas fa-user"></i> Nom complet</span>
                        <strong><?= htmlspecialchars($patientFullName); ?></strong>
                    </div>
                    <div class="profile-detail-card">
                        <span><i class="fas fa-phone"></i> Numéro de téléphone</span>
                        <strong><?= htmlspecialchars($patientPhone); ?></strong>
                    </div>
                    <div class="profile-detail-card">
                        <span><i class="fas fa-envelope"></i> Adresse e-mail</span>
                        <strong><?= htmlspecialchars($patientEmail); ?></strong>
                    </div>
                    <div class="profile-detail-card">
                        <span><i class="fas fa-lock"></i> Date de naissance</span>
                        <strong><?= htmlspecialchars($patientbirthday); ?></strong>
                    </div>
                </div>

                <form id="profileForm" class="profile-form" novalidate>
                    <div class="form-grid">
                        <label>
                            Nom complet
                            <input type="text" name="full_name" value="<?= htmlspecialchars($patientFullName); ?>"
                                required minlength="3">
                        </label>
                        <label>
                            Numéro de téléphone
                            <input type="tel" name="phone" value="<?= htmlspecialchars($patientPhone); ?>" required
                                pattern="\+?[0-9\s\-\(\)]{7,25}">
                        </label>
                        <label>
                            Adresse e-mail
                            <input type="email" name="email" value="<?= htmlspecialchars($patientEmail); ?>" required>
                        </label>
                        <label>
                            Mot de passe
                            <input type="password" name="password" placeholder="**************"
                                autocomplete="new-password" minlength="8">
                            <small>Minimum 8 caractères (optionnel sauf modification).</small>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-ghost" id="cancelEditBtn">Annuler</button>
                        <button type="submit" class="btn-primary btn-small">Enregistrer les modifications</button>
                    </div>
                    <div class="form-feedback" id="profileFeedback" aria-live="polite"></div>
                </form>
            </article>

            <article id="appointments" class="card-panel appointments-section">
                <div class="card-header section-header">
                    <div>
                        <h2>Rendez-vous</h2>
                        <p>Suivez vos prochaines visites, consultez les suivis terminés et les séances annulées.</p>
                    </div>
                    <div class="filter-pills">
                        <button class="filter-pill is-active" type="button" data-appt-filter="all">Tous</button>
                        <button class="filter-pill" type="button" data-appt-filter="upcoming">À venir</button>
                        <button class="filter-pill" type="button" data-appt-filter="completed">Terminés</button>
                        <button class="filter-pill" type="button" data-appt-filter="canceled">Annulés</button>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['scheduled']); ?></strong>
                        <span>Visites à venir</span>
                    </div>
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['completed']); ?></strong>
                        <span>Terminés</span>
                    </div>
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['cancelled']); ?></strong>
                        <span>Annulés</span>
                    </div>
                    <div class="summary-card">
                        <strong><?= htmlspecialchars((string) $summaryCounts['total']); ?></strong>
                        <span>Total suivi</span>
                    </div>
                </div>

                <div class="appointments-table-wrap">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Médecin</th>
                                <th>Lieu</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $row): ?>
                            <tr data-status="<?= htmlspecialchars($row['status']); ?>">
                                <td data-label="Date">
                                    <?= htmlspecialchars(date('d M Y', strtotime($row['appointment_time']))); ?>
                                </td>
                                <td data-label="Heure">
                                    <strong><?= date("H:i", strtotime($row['appointment_time'])); ?></strong>
                                </td>
                                <td data-label="Médecin">
                                    <strong><?= htmlspecialchars($row['doctor_name']); ?></strong>
                                </td>
                                <td data-label="Lieu">
                                    <strong><?= htmlspecialchars($row['location']); ?></strong>
                                </td>
                                <?php
                                    $status = (string) $row['status'];
                                    $status_class = 'status-badge';

                                    if ($status === 'scheduled') {
                                        $status_text = 'Planifié';
                                        $status_class .= ' status-upcoming';
                                    } elseif ($status === 'completed') {
                                        $status_text = 'Terminé';
                                        $status_class .= ' status-completed';
                                    } elseif ($status === 'cancelled') {
                                        $status_text = 'Annulé';
                                        $status_class .= ' status-canceled';
                                    } else {
                                        $status_text = ucfirst($status);
                                        $status_class .= ' status-upcoming';
                                    }
                                ?>
                                <td data-label="Statut">
                                    <span class="<?= htmlspecialchars($status_class); ?>">
                                        <i class="fa-solid fa-circle"></i>
                                        <?= htmlspecialchars($status_text); ?>
                                    </span>
                                </td>
                                <td data-label="Action">
                                    <?php if ($row['status'] === 'scheduled'): ?>
                                    <form action="../php/cansel.php" method="post">
                                        <input name="appointment_id" type="hidden"
                                            value="<?= $row['appointment_id'] ?>">
                                        <button class="btn-action btn-action-danger" name="cancel" type="submit">
                                            <i class="fa-regular fa-circle-xmark"></i>
                                            Annuler le rendez-vous
                                        </button>
                                    </form>

                                    <?php elseif ($row['status'] === 'completed'): ?>
                                    <?php
                                        $existing_rating = (int)($row['existing_rating']);
                                    ?>
                                    <?php if ($existing_rating > 0): ?>
                                    <div class="rating-inline" aria-label="Votre note">
                                        <span class="rating-stars"
                                            aria-label="Note : <?= htmlspecialchars((string) $existing_rating); ?> sur 5">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $existing_rating): ?>
                                            <i class="fa-solid fa-star"></i>
                                            <?php else: ?>
                                            <i class="fa-regular fa-star"></i>
                                            <?php endif; ?>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>

                                    <form action="rate_appointment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id"
                                            value="<?= htmlspecialchars((string)$row['appointment_id']); ?>">

                                        <button type="submit" class="btn-action btn-action-success">
                                            <i class="fa-solid fa-star"></i>
                                            <?php if ($existing_rating > 0): ?>
                                            <span>
                                                Votre note :
                                                <?= htmlspecialchars((string)$existing_rating); ?>/5
                                            </span>
                                            <?php else: ?>
                                            Évaluer le rendez-vous
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="muted">-</span>
                                    <?php endif?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>

    <script src="../js/script.js"></script>
</body>

</html>
