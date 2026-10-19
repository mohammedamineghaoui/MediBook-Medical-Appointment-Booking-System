<?php
include 'require_admin.php';
include '../php/conn.php';

$search_term = trim($_GET['search'] ?? '');
$selected_rating = isset($_GET['rating']) ? (int) $_GET['rating'] : 0;
$message_key = $_GET['message'] ?? '';
$flash_messages = [
    'deleted' => 'Rating removed successfully.',
    'not_found' => 'Rating was not found.',
    'delete_failed' => 'Could not remove rating.',
    'invalid_request' => 'Invalid rating request.',
];

function redirect_to_ratings(string $result_key): void
{
    $query = [];
    if (isset($_GET['search'])) {
        $query['search'] = $_GET['search'];
    }
    if (isset($_GET['rating'])) {
        $query['rating'] = $_GET['rating'];
    }
    $query['message'] = $result_key;

    header('Location: ratings.php?' . http_build_query($query));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating_id = isset($_POST['rating_id']) ? (int) $_POST['rating_id'] : 0;

    if (!$rating_id) {
        redirect_to_ratings('invalid_request');
    }

    $delete_stmt = $conn->prepare("DELETE FROM ratings WHERE rating_id = ?");
    $delete_stmt->bind_param("i", $rating_id);

    if ($delete_stmt->execute()) {
        redirect_to_ratings($delete_stmt->affected_rows > 0 ? 'deleted' : 'not_found');
    }

    redirect_to_ratings('delete_failed');
}

if ($selected_rating < 0 || $selected_rating > 5) {
    $selected_rating = 0;
}

$where_sql = "WHERE (? = ''
    OR p.full_name LIKE ?
    OR d.full_name LIKE ?
    OR r.comment LIKE ?
    OR a.status LIKE ?)";
$bind_types = "sssss";
$search_like = '%' . $search_term . '%';
$bind_values = [$search_term, $search_like, $search_like, $search_like, $search_like];

if ($selected_rating > 0) {
    $where_sql .= " AND r.rating = ?";
    $bind_types .= "i";
    $bind_values[] = $selected_rating;
}

$ratings_sql = "SELECT
        r.rating_id,
        r.appointment_id,
        r.rating,
        r.comment,
        a.appointment_time,
        a.status AS appointment_status,
        p.full_name AS patient_name,
        p.email AS patient_email,
        p.avatar AS patient_avatar,
        d.full_name AS doctor_name,
        s.specialty_name
    FROM ratings r
    INNER JOIN appointments a ON r.appointment_id = a.appointment_id
    INNER JOIN patient p ON a.patient_id = p.user_id
    INNER JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
    $where_sql
    ORDER BY r.rating_id DESC";

$ratings_stmt = $conn->prepare($ratings_sql);
$params = [$bind_types];
foreach ($bind_values as &$value) {
    $params[] = &$value;
}
call_user_func_array([$ratings_stmt, 'bind_param'], $params);
$ratings_stmt->execute();
$ratings_result = $ratings_stmt->get_result();

$ratings = [];
while ($rating = $ratings_result->fetch_assoc()) {
    $ratings[] = $rating;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings | MediBook Admin</title>
    <meta name="description" content="Manage patient ratings for appointments">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="patients.css">
    <link rel="stylesheet" href="ratings.css">
</head>

<body>
    <div class="app-container admin-app">
        <?php
        $active_page = 'ratings';
        include 'admin_nav.php';
        ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle-btn" id="menuToggle" aria-label="Open sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <p class="page-kicker">Ratings</p>
                        <h1 class="page-title">Manage Ratings</h1>
                    </div>
                </div>
            </header>

            <section class="patients-page ratings-page">
                <div class="patients-intro">
                    <div>
                        <h2>Patient Rating Directory</h2>
                        <p>Review every rating patients submitted after completed appointments.</p>
                    </div>
                    <div class="patients-badge">
                        <i class="fa-solid fa-star"></i>
                        <span><?=count($ratings)?> Ratings</span>
                    </div>
                </div>

                <form method="GET" class="ratings-filter-form">
                    <div class="rating-filter-field">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Patient, doctor, comment, or status"
                            value="<?=htmlspecialchars($search_term, ENT_QUOTES)?>">
                    </div>
                    <div class="rating-filter-field">
                        <label for="rating">Stars</label>
                        <select id="rating" name="rating">
                            <option value="0">All ratings</option>
                            <?php for ($stars = 5; $stars >= 1; $stars--) : ?>
                            <option value="<?=$stars?>" <?=$selected_rating === $stars ? 'selected' : ''?>>
                                <?=$stars?> star<?=$stars === 1 ? '' : 's'?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="rating-filter-actions">
                        <button type="submit" class="ratings-filter-submit">
                            <i class="fa-solid fa-filter"></i>
                            <span>Apply</span>
                        </button>
                        <a href="ratings.php" class="ratings-filter-reset">Reset</a>
                    </div>
                </form>

                <?php if ($message_key !== '' && isset($flash_messages[$message_key])) :
                    $is_error = $message_key !== 'deleted';
                ?>
                <div class="ratings-flash <?=$is_error ? 'error' : 'success'?>">
                    <?=htmlspecialchars($flash_messages[$message_key])?>
                </div>
                <?php endif; ?>

                <div class="card patients-card">
                    <div class="table-responsive">
                        <table class="data-table patients-table ratings-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Appointment</th>
                                    <th>Statut</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ratings)) : ?>
                                <?php foreach ($ratings as $rating) :
                                    $avatar = !empty($rating['patient_avatar']) ? $rating['patient_avatar'] : '../../images/user.png';
                                    $appointment_status = (string) ($rating['appointment_status'] ?? '');
                                    $status_class = $appointment_status === 'cancelled' ? 'status-cancelled' : ($appointment_status === 'completed' ? 'status-confirmed' : 'status-waiting');
                                ?>
                                <tr>
                                    <td>
                                        <div class="patient-cell">
                                            <img src="<?=htmlspecialchars($avatar, ENT_QUOTES)?>"
                                                alt="<?=htmlspecialchars($rating['patient_name'], ENT_QUOTES)?>">
                                            <div class="patient-info">
                                                <span class="patient-name"><?=htmlspecialchars($rating['patient_name'])?></span>
                                                <span class="patient-id"><?=htmlspecialchars($rating['patient_email'] ?? '-')?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?=htmlspecialchars($rating['doctor_name'])?></strong>
                                        <div class="rating-muted"><?=htmlspecialchars($rating['specialty_name'] ?? 'Specialist')?></div>
                                    </td>
                                    <td>
                                        <?=date('Y-m-d', strtotime($rating['appointment_time']))?>
                                        <div class="rating-muted"><?=date('H:i', strtotime($rating['appointment_time']))?></div>
                                    </td>
                                    <td><span class="status-badge <?=$status_class?>"><?=htmlspecialchars(ucfirst($appointment_status ?: 'Unknown'))?></span></td>
                                    <td>
                                        <span class="rating-stars-pill" aria-label="<?=$rating['rating']?> out of 5 stars">
                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <i class="<?=$i <= (int) $rating['rating'] ? 'fa-solid' : 'fa-regular'?> fa-star"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="rating-comment-btn"
                                            data-patient="<?=htmlspecialchars($rating['patient_name'], ENT_QUOTES)?>"
                                            data-patient-email="<?=htmlspecialchars($rating['patient_email'] ?? '-', ENT_QUOTES)?>"
                                            data-doctor="<?=htmlspecialchars($rating['doctor_name'], ENT_QUOTES)?>"
                                            data-specialty="<?=htmlspecialchars($rating['specialty_name'] ?? 'Specialist', ENT_QUOTES)?>"
                                            data-date="<?=htmlspecialchars(date('Y-m-d', strtotime($rating['appointment_time'])), ENT_QUOTES)?>"
                                            data-time="<?=htmlspecialchars(date('H:i', strtotime($rating['appointment_time'])), ENT_QUOTES)?>"
                                            data-status="<?=htmlspecialchars(ucfirst($appointment_status ?: 'Unknown'), ENT_QUOTES)?>"
                                            data-rating="<?=htmlspecialchars((string) $rating['rating'], ENT_QUOTES)?>"
                                            data-comment="<?=htmlspecialchars($rating['comment'] !== '' ? $rating['comment'] : 'No comment provided.', ENT_QUOTES)?>">
                                            <i class="fa-regular fa-comment-dots"></i>
                                            <span>View comment</span>
                                        </button>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Remove this patient rating?');">
                                            <input type="hidden" name="rating_id" value="<?=$rating['rating_id']?>">
                                            <button type="submit" class="rating-delete-btn">
                                                <i class="fa-solid fa-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else : ?>
                                <tr>
                                    <td colspan="7" class="empty-state-cell">No ratings found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="rating-modal" id="ratingModal">
        <div class="rating-modal-card">
            <button class="rating-modal-close" id="closeRatingModal" type="button" aria-label="Close rating details">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="rating-modal-head">
                <div>
                    <p class="page-kicker">Rating Summary</p>
                    <h2 id="modalRatingTitle">Appointment Rating</h2>
                </div>
                <div class="modal-stars" id="modalRatingStars"></div>
            </div>

            <div class="rating-summary-grid">
                <div class="summary-info-card">
                    <span>Patient</span>
                    <strong id="modalPatientName"></strong>
                    <p id="modalPatientEmail"></p>
                </div>
                <div class="summary-info-card">
                    <span>Doctor</span>
                    <strong id="modalDoctorName"></strong>
                    <p id="modalSpecialty"></p>
                </div>
                <div class="summary-info-card">
                    <span>Appointment</span>
                    <strong id="modalAppointmentDate"></strong>
                    <p id="modalAppointmentTime"></p>
                </div>
                <div class="summary-info-card">
                    <span>Status</span>
                    <strong id="modalAppointmentStatus"></strong>
                </div>
            </div>

            <div class="comment-summary-card">
                <h3>Full Comment</h3>
                <p id="modalRatingComment"></p>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="script.js"></script>
    <script src="ratings.js"></script>
</body>

</html>
