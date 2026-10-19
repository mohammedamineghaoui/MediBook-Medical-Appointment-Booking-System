<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediBook - Prenez votre rendez-vous médical facilement</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
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
            
            $avrRating = "SELECT AVG(rating) AS avg_rating FROM ratings";   
            $result = $conn->query($avrRating); 
                $row = $result->fetch_assoc();
                $avrRating = round($row['avg_rating'], 1);

            $ratingGlobalAvg = is_numeric($avrRating) ? (float) $avrRating : 3.5;
            $minVotesForScore = 5;
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
                    'label' => 'Plus Réservé',
                    'icon' => 'fa-calendar-check',
                    'badge' => 'tag-booked',
                    'note' => $mostBookedDoctor ? ($mostBookedDoctor['booking_count'] ?? 0) . ' réservations au total' : 'En attente de la première réservation',
                    'doctor' => $mostBookedDoctor,
                ],
                [
                    'label' => 'Mieux Noté',
                    'icon' => 'fa-star',
                    'badge' => 'tag-rated',
                    'note' => ($topRatedDoctor && !empty($topRatedDoctor['rating_votes']))
                        ? 'Score pondéré ' . round($topRatedDoctor['weighted_score'], 2) . ' (moy. ' . round($topRatedDoctor['rating_average'], 1) . ' sur ' . $topRatedDoctor['rating_votes'] . ' avis)'
                        : 'Aucun avis pour le moment',
                    'doctor' => $topRatedDoctor,
                ],
                [
                    'label' => 'Plus Expérimenté',
                    'icon' => 'fa-award',
                    'badge' => 'tag-experience',
                    'note' => ($mostExperiencedDoctor && isset($mostExperiencedDoctor['experience_years']))
                        ? $mostExperiencedDoctor['experience_years'] . ' ans de pratique'
                        : 'Leader en expérience',
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
    <!-- Section Héros -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Prenez votre rendez-vous médical facilement</h1>
                    <p class="hero-description">
                        Connectez-vous avec des professionnels de santé de confiance et gérez vos rendez-vous
                        médicaux en toute simplicité. Rapide, sécurisé et disponible 24h/24 et 7j/7.
                    </p>
                    <button class="btn-primary btn-large see-all-doctors"
                        onclick="window.location.href='appointment-slots.php'">Réserver
                        maintenant</button>
                </div>
                <div class="hero-image">
                    <div class="hero-image-placeholder">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Médecins populaires -->
    <div class="popular-doctors-section">
        <div class="container">
            <div class="popular-header">
                <div>
                    <p class="popular-subtitle">Prenez rendez-vous rapidement</p>
                    <h3 class="popular-title">
                        <i class="fas fa-fire"></i> Réservation rapide
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

    <!-- Pourquoi nous choisir -->
    <section class="why-choose-us">
        <div class="container">
            <h2 class="section-title">Pourquoi nous choisir</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Services en ligne 24h/24</h3>
                    <p>Prenez vos rendez-vous à tout moment, de n'importe où. Notre plateforme est disponible en permanence pour votre confort.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Médecins vérifiés</h3>
                    <p>Tous nos professionnels de santé sont vérifiés, certifiés et hautement qualifiés dans leurs spécialités respectives.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Réservation simple et rapide</h3>
                    <p>Un processus de réservation simple en quelques clics. Obtenez une confirmation instantanée de votre rendez-vous.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Dossiers médicaux sécurisés</h3>
                    <p>Vos informations médicales sont chiffrées et stockées en toute sécurité selon les normes de confidentialité les plus strictes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Témoignages -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">Ce que disent nos patients</h2>
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

    <!-- FAQ -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Questions fréquemment posées</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Comment prendre un rendez-vous ?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Sélectionnez simplement votre spécialité, choisissez un médecin, sélectionnez une date et cliquez sur le bouton de recherche.
                            Vous serez guidé à travers un processus de réservation simple pour confirmer votre rendez-vous.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Comment annuler mon rendez-vous ?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Vous pouvez annuler votre rendez-vous en vous connectant à votre compte et en accédant à la section « Mes rendez-vous ».
                            Cliquez sur le rendez-vous que vous souhaitez annuler et sélectionnez l'option d'annulation.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Quels modes de paiement sont acceptés ?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Nous acceptons toutes les principales cartes de crédit, cartes de débit et méthodes de paiement numériques.
                            Le paiement est sécurisé et traité via des canaux chiffrés.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Comment ma confidentialité et mes données sont-elles protégées ?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Nous utilisons des mesures de sécurité et un chiffrement aux normes industrielles pour protéger vos dossiers médicaux.
                            Toutes les données sont stockées en toute sécurité et conformément aux réglementations sur la confidentialité des données de santé.
                            Nous ne partageons jamais vos informations avec des tiers sans votre consentement.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pied de page -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>MediBook</span>
                    </div>
                    <p>Votre partenaire de confiance en gestion de la santé. Prenez facilement des rendez-vous et gérez votre santé.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="#home">Accueil</a></li>
                        <li><a href="#doctors">Médecins</a></li>
                        <li><a href="#specialties">Spécialités</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#contact">Contactez-nous</a></li>
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
                <div class="footer-section">
                    <h3>Mentions légales</h3>
                    <ul>
                        <li><a href="#">Conditions d'utilisation</a></li>
                        <li><a href="#">Politique de confidentialité</a></li>
                        <li><a href="#">Politique de cookies</a></li>
                        <li><a href="#">Conformité RGPD</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MediBook. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>

</html>
