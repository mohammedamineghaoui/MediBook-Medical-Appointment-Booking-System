<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - MediBook</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/doctor-profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Header Section -->
    <?php
            include 'header.php';
    ?>

    <!-- Doctor Profile Section -->
    <section class="doctor-profile-section">
        <div class="container">
            <div class="doctor-profile-container">
                <!-- Doctor Header -->
                <div class="doctor-header">
                    <div class="doctor-image-large">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="doctor-info-header">
                        <h1 id="doctorName">Dr. Sarah Johnson</h1>
                        <p class="doctor-specialty-large" id="doctorSpecialty">Cardiology Specialist</p>
                        <div class="doctor-badges">
                            <span class="badge"><i class="fas fa-award"></i> 15 Years Experience</span>
                            <span class="badge"><i class="fas fa-graduation-cap"></i> Board Certified</span>
                            <span class="badge"><i class="fas fa-star"></i> 4.9 Rating</span>
                        </div>
                        <button class="btn-primary btn-large" onclick="window.location.href='appointment-slots.html'">
                            <i class="fas fa-calendar-check"></i> Book Appointment
                        </button>
                    </div>
                </div>

                <!-- Doctor Details Tabs -->
                <div class="doctor-tabs">
                    <button class="tab-btn active" data-tab="about">About</button>
                    <button class="tab-btn" data-tab="education">Education</button>
                    <button class="tab-btn" data-tab="experience">Experience</button>
                    <button class="tab-btn" data-tab="reviews">Reviews</button>
                </div>

                <!-- Tab Content -->
                <div class="tab-content active" id="about">
                    <div class="content-card">
                        <h2>About Dr. Sarah Johnson</h2>
                        <p>
                            Dr. Sarah Johnson is a highly experienced cardiologist with over 15 years of practice
                            in diagnosing and treating cardiovascular diseases. She specializes in preventive
                            cardiology, heart failure management, and interventional cardiology procedures.
                        </p>
                        <p>
                            Dr. Johnson is committed to providing personalized care to each patient, focusing on
                            comprehensive treatment plans that address both immediate health concerns and long-term
                            cardiovascular wellness. Her approach combines evidence-based medicine with a compassionate
                            bedside manner.
                        </p>
                    </div>
                    <div class="content-card">
                        <h3>Specializations</h3>
                        <ul class="specialization-list">
                            <li><i class="fas fa-check-circle"></i> Preventive Cardiology</li>
                            <li><i class="fas fa-check-circle"></i> Heart Failure Management</li>
                            <li><i class="fas fa-check-circle"></i> Interventional Cardiology</li>
                            <li><i class="fas fa-check-circle"></i> Cardiac Rehabilitation</li>
                            <li><i class="fas fa-check-circle"></i> Hypertension Management</li>
                        </ul>
                    </div>
                </div>

                <div class="tab-content" id="education">
                    <div class="content-card">
                        <h2>Education & Qualifications</h2>
                        <div class="education-item">
                            <div class="education-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="education-details">
                                <h3>MD in Cardiology</h3>
                                <p class="institution">Harvard Medical School</p>
                                <p class="year">2005 - 2009</p>
                            </div>
                        </div>
                        <div class="education-item">
                            <div class="education-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="education-details">
                                <h3>Board Certification</h3>
                                <p class="institution">American Board of Cardiology</p>
                                <p class="year">2009</p>
                            </div>
                        </div>
                        <div class="education-item">
                            <div class="education-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="education-details">
                                <h3>Residency in Internal Medicine</h3>
                                <p class="institution">Johns Hopkins Hospital</p>
                                <p class="year">2009 - 2012</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="experience">
                    <div class="content-card">
                        <h2>Professional Experience</h2>
                        <div class="experience-item">
                            <div class="experience-year">2015 - Present</div>
                            <div class="experience-details">
                                <h3>Senior Cardiologist</h3>
                                <p class="institution">MediBook Medical Center</p>
                                <p>Leading the cardiology department, managing complex cardiac cases, and mentoring
                                    junior physicians.</p>
                            </div>
                        </div>
                        <div class="experience-item">
                            <div class="experience-year">2012 - 2015</div>
                            <div class="experience-details">
                                <h3>Cardiologist</h3>
                                <p class="institution">City General Hospital</p>
                                <p>Provided comprehensive cardiac care, performed diagnostic procedures, and managed
                                    outpatient clinics.</p>
                            </div>
                        </div>
                        <div class="experience-item">
                            <div class="experience-year">2009 - 2012</div>
                            <div class="experience-details">
                                <h3>Resident Physician</h3>
                                <p class="institution">Johns Hopkins Hospital</p>
                                <p>Completed residency training in internal medicine with focus on cardiovascular
                                    diseases.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="reviews">
                    <div class="content-card">
                        <h2>Patient Reviews</h2>
                        <div class="reviews-summary">
                            <div class="rating-overview">
                                <div class="rating-number">4.9</div>
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p>Based on 127 reviews</p>
                            </div>
                        </div>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h4>John Smith</h4>
                                        <div class="review-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <span class="review-date">2 weeks ago</span>
                            </div>
                            <p class="review-text">
                                "Dr. Johnson is exceptional! She took the time to explain everything clearly and
                                made me feel comfortable throughout the entire process. Highly recommend!"
                            </p>
                        </div>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h4>Maria Garcia</h4>
                                        <div class="review-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <span class="review-date">1 month ago</span>
                            </div>
                            <p class="review-text">
                                "Professional, caring, and knowledgeable. Dr. Johnson helped me manage my heart
                                condition effectively. The best cardiologist I've ever visited."
                            </p>
                        </div>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h4>David Lee</h4>
                                        <div class="review-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <span class="review-date">2 months ago</span>
                            </div>
                            <p class="review-text">
                                "Excellent doctor with great bedside manner. She's thorough, patient, and really
                                cares about her patients' well-being."
                            </p>
                        </div>
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
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="index.html#doctors">Doctors</a></li>
                        <li><a href="index.html#specialties">Specialties</a></li>
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
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MediBook. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../js/script.js"></script>
    <script src="../js/doctor-profile.js"></script>
</body>

</html>