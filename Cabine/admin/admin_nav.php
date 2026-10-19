<?php
$active_page = $active_page ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
            <h2>MediBook</h2>
        </div>
        <button class="close-sidebar-btn" id="closeSidebar" aria-label="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <button class="pin-sidebar-btn" id="pinSidebar" type="button" aria-label="Keep sidebar open"
            aria-pressed="false">
            <i class="fa-solid fa-thumbtack"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?=$active_page === 'dashboard' ? 'active' : ''?>">
                <a href="index.php" title="Dashboard"><i class="fa-solid fa-border-all"></i> <span>Dashboard</span></a>
            </li>
            <li class="<?=$active_page === 'add_doctor' ? 'active' : ''?>">
                <a href="add_doctor.php" title="Add Doctor"><i class="fa-solid fa-user-plus"></i> <span>Add Doctor</span></a>
            </li>
            <li class="<?=$active_page === 'doctors' ? 'active' : ''?>">
                <a href="doctors.php" title="Doctors"><i class="fa-solid fa-user-doctor"></i> <span>Doctors</span></a>
            </li>
            <li class="<?=$active_page === 'patients' ? 'active' : ''?>">
                <a href="patients.php" title="Patients"><i class="fa-solid fa-user-group"></i> <span>Patients</span></a>
            </li>
            <li class="<?=$active_page === 'appointments' ? 'active' : ''?>">
                <a href="appointments.php" title="Appointments"><i class="fa-regular fa-calendar-check"></i> <span>Appointments</span></a>
            </li>
            <li class="<?=$active_page === 'ratings' ? 'active' : ''?>">
                <a href="ratings.php" title="Ratings"><i class="fa-solid fa-star"></i> <span>Ratings</span></a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <ul>
            <li>
                <a href="Admin_logout.php" class="logout" title="Logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
