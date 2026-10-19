<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediBook - Book Your Medical Appointment Easily</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Header Section -->
    <?php
    session_start();
             include "../php/conn.php";
            include 'header.php';
            include '../php/get-doctors.php';

            function fetchDoctorHighlight($conn, $sql) {
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    return $result->fetch_assoc();
                }
                return null;
            }
            
            // get the average rating from the database
            $avrRating = "SELECT AVG(rating) AS avg_rating FROM ratings";   
            $result = $conn->query($avrRating); 
                $row = $result->fetch_assoc();
                $avrRating = round($row['avg_rating'], 1);

            // fallback average for weighting if no global rating exists
            $ratingGlobalAvg = is_numeric($avrRating) ? (float) $avrRating : 3.5;
            $minVotesForScore = 5; // damping factor for weighted rating
            ////////////////////////////////////////////////
            $mostBookedDoctor = fetchDoctorHighlight($conn, "
                SELECT d.*, s.specialty_name, COALESCE(bookings.booking_count, 0) AS booking_count
                FROM doctors d
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                LEFT JOIN (
                    SELECT doctor_id, COUNT(*) AS booking_count
                    FROM appointments
                    GROUP BY doctor_id
                ) AS bookings ON bookings.doctor_id = d.doctor_id
                ORDER BY booking_count DESC, d.created_at DESC
                LIMIT 1
            ");

            $topRatedDoctor = fetchDoctorHighlight($conn, "
                SELECT d.*, s.specialty_name,
                       ratings.rating_average,
                       ratings.rating_votes,
                       (
                           (COALESCE(ratings.rating_votes, 0) / (COALESCE(ratings.rating_votes, 0) + {$minVotesForScore})) * COALESCE(ratings.rating_average, 0)
                           + ({$minVotesForScore} / (COALESCE(ratings.rating_votes, 0) + {$minVotesForScore})) * {$ratingGlobalAvg}
                       ) AS weighted_score
                FROM doctors d
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                LEFT JOIN (
                    SELECT a.doctor_id, AVG(r.rating) AS rating_average, COUNT(r.rating_id) AS rating_votes
                    FROM ratings r
                    INNER JOIN appointments a ON r.appointment_id = a.appointment_id
                    GROUP BY a.doctor_id
                ) AS ratings ON ratings.doctor_id = d.doctor_id
                ORDER BY weighted_score DESC, COALESCE(ratings.rating_votes, 0) DESC, d.created_at DESC
                LIMIT 1
            ");

            $mostExperiencedDoctor = fetchDoctorHighlight($conn, "
                SELECT d.*, s.specialty_name,
                    CASE
                        WHEN d.experience REGEXP '^[0-9]+$' THEN d.experience
                        ELSE TIMESTAMPDIFF(YEAR, d.experience, CURDATE())
                    END AS experience_years
                FROM doctors d
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                ORDER BY experience_years DESC, d.experience ASC, d.created_at ASC
                LIMIT 1
            ");

            $popularDoctors = [
                [
                    'label' => 'Most Booked',
                    'icon' => 'fa-calendar-check',
                    'badge' => 'tag-booked',
                    'note' => $mostBookedDoctor ? ($mostBookedDoctor['booking_count'] ?? 0) . ' total bookings' : 'Waiting for first booking',
                    'doctor' => $mostBookedDoctor,
                ],
                [
                    'label' => 'Top Rated',
                    'icon' => 'fa-star',
                    'badge' => 'tag-rated',
                    'note' => ($topRatedDoctor && !empty($topRatedDoctor['rating_votes']))
                        ? 'Weighted ' . round($topRatedDoctor['weighted_score'], 2) . ' (avg ' . round($topRatedDoctor['rating_average'], 1) . ' from ' . $topRatedDoctor['rating_votes'] . ' ratings)'
                        : 'No ratings yet',
                    'doctor' => $topRatedDoctor,
                ],
                [
                    'label' => 'Most Experienced',
                    'icon' => 'fa-award',
                    'badge' => 'tag-experience',
                    'note' => ($mostExperiencedDoctor && isset($mostExperiencedDoctor['experience_years']))
                        ? $mostExperiencedDoctor['experience_years'] . ' years of care'
                        : 'Experience leader',
                    'doctor' => $mostExperiencedDoctor,
                ],
            ];

            $spotlightCards = [];
            foreach ($popularDoctors as $item) {
                if (!$item['doctor']) {
                    continue;
                }

                $docId = $item['doctor']['doctor_id'];
                if (!isset($spotlightCards[$docId])) {
                    $spotlightCards[$docId] = [
                        'doctor' => $item['doctor'],
                        'badges' => [],
                    ];
                }

                $spotlightCards[$docId]['badges'][] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'badge' => $item['badge'],
                    'note' => $item['note'],
                ];
            }

        $sql_get_rating = "SELECT r.rating_id, r.rating, r.comment, p.full_name ,p.avatar
                            FROM ratings r
                            JOIN appointments a ON r.appointment_id = a.appointment_id
                            JOIN patient p ON a.patient_id = p.user_id
                            ORDER BY r.rating_id DESC
                            LIMIT 3";
        $result_get_rating = $conn->query($sql_get_rating);
    ?>
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Book Your Medical Appointment Easily</h1>
                    <p class="hero-description">
                        Connect with trusted healthcare professionals and manage your health appointments
                        with ease. Fast, secure, and available 24/7.
                    </p>
                    <button class="btn-primary btn-large see-all-doctors"
                        onclick="window.location.href='appointment-slots.php'">Book
                        Now</button>
                </div>
                <div class="hero-image">
                    <div class="hero-image-placeholder">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Appointment Search Box -->

    <!-- Popular Doctors Preview -->
    <div class="popular-doctors-section">
        <div class="container">
            <div class="popular-header">
                <div>
                    <p class="popular-subtitle">take apointment fast </p>
                    <h3 class="popular-title">
                        <i class="fas fa-fire"></i> Fast booking
                    </h3>
                </div>
            </div>
            <div class="popular-doctors-grid">
                <?php foreach ($spotlightCards as $card): ?>
                <div class="popular-doctor-card">
                    <div class="popular-card-top">
                        <div class="popular-badges">
                            <?php foreach ($card['badges'] as $badge): ?>
                            <span class="popular-badge <?= $badge['badge']; ?>">
                                <i class="fas <?= $badge['icon']; ?>"></i>
                                <?= $badge['label']; ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <ul class="popular-note-list">
                            <?php foreach ($card['badges'] as $badge): ?>
                            <li><?= $badge['note']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php
                                $doctor = $card['doctor'];
                                include 'doctor-card.php';
                            ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>



    <!-- Why Choose Us Section -->
    <section class="why-choose-us">
        <div class="container">
            <h2 class="section-title">Why Choose Us</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>24/7 Online Services</h3>
                    <p>Book appointments anytime, anywhere. Our platform is available round the clock for your
                        convenience.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Verified Doctors</h3>
                    <p>All our healthcare professionals are verified, licensed, and highly qualified in their respective
                        fields.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Easy & Fast Booking</h3>
                    <p>Simple booking process that takes just a few clicks. Get your appointment confirmed instantly.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Secure Medical Records</h3>
                    <p>Your medical information is encrypted and stored securely with the highest privacy standards.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">What Our Patients Say</h2>
            <div class="testimonials-grid">
                <?php if ($result_get_rating && $result->num_rows > 0 ){?>

                <?php while($row_get_rating = $result_get_rating->fetch_assoc()){?>

                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <?php for($i = 0 ; $i < $row_get_rating['rating']; $i++){?>
                        <i class="fas fa-star"></i>
                        <?php }?>

                    </div>
                    <p class="testimonial-text">
                        <?php echo htmlspecialchars($row_get_rating['comment']); ?> </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="<?= htmlspecialchars($row_get_rating['avatar'] ?: '../../images/user.png', ENT_QUOTES); ?>"
                                alt="<?= htmlspecialchars($row_get_rating['full_name'], ENT_QUOTES); ?>">
                        </div>
                        <div class="author-info">
                            <h4> <?php echo htmlspecialchars($row_get_rating['full_name']); ?></h4>
                            <p>Patient</p>
                        </div>
                    </div>


                </div>
                <?php }?>
                <?php } 
                ?>

            </div>

        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How do I book an appointment?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Simply select your specialty, choose a doctor, pick a date, and click the search button.
                            You'll be guided through a simple booking process to confirm your appointment.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How can I cancel my appointment?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can cancel your appointment by logging into your account and accessing the "My
                            Appointments"
                            section. Click on the appointment you wish to cancel and select the cancel option.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What payment methods are accepted?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards, debit cards, and digital payment methods.
                            Payment is secure and processed through encrypted channels.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How is my privacy and data protected?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We use industry-standard encryption and security measures to protect your medical records.
                            All data is stored securely and complies with healthcare privacy regulations.
                            We never share your information with third parties without your consent.</p>
                    </div>
                </div>
            </div>
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
                    <p>Your trusted partner in healthcare management. Book appointments easily and manage your health.
                    </p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#doctors">Doctors</a></li>
                        <li><a href="#specialties">Specialties</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#contact">Contact Us</a></li>
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
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">HIPAA Compliance</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MediBook. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>

</html>