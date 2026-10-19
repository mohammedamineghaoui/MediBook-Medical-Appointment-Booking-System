<link rel="stylesheet" href="../css/table-doctor.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="doctors-table-wrap">
    <table class="doctors-table">
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Specialty</th>
                <th>Experience</th>
                <th>Location</th>
                <th>Rating</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php while($doctor = $result->fetch_assoc()): ?>
            <?php include '../php/get-rating.php'; ?>

            <tr>
                <td class="td-doctor">
                    <div class="doc-cell">
                        <div class="doc-avatar">
                            <img src="<?= $doctor['avatar'] ?>" alt="<?= $doctor['full_name']; ?>">
                        </div>
                        <div class="doc-meta">
                            <div class="doc-name">Dr. <?= $doctor['full_name']; ?></div>
                        </div>
                    </div>
                </td>

                <td class="td-specialty" data-label="Specialty">
                    <span class="spec-badge">
                        <?= isset($doctor['specialty_name']) ? ucfirst($doctor['specialty_name']) : 'No Specialty'; ?>
                    </span>
                </td>

                <td class="td-exp" data-label="Experience">
                    <?= $doctor['experience']; ?>
                </td>

                <td class="td-loc" data-label="Location">
                    <?= $doctor['location']; ?>
                </td>

                <td class="td-rating">
                    <?php if (!empty($rating_row['total_votes'])): ?>
                    <span class="rating-pill">
                        <i class="fa-solid fa-star"></i>
                        <span class="rv"><?= round($rating_row['avg_rating'], 1); ?></span>
                        <span class="rm">/5</span>
                        <span class="rc">(<?= $rating_row['total_votes']; ?>)</span>
                    </span>
                    <?php else: ?>
                    <span class="rating-pill no-rating">No ratings</span>
                    <?php endif; ?>
                </td>

                <td class="td-action" data-label="Action">
                    <a class="btn-book" href="appointment-slots.php?doctor_id=<?= $doctor['doctor_id']; ?>">
                        <i onclick="checkLogin()" class="fa-regular fa-calendar-days"></i> Book
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>