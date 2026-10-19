<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language switcher: detect current page and build the EN equivalent path
$currentPage = basename($_SERVER['PHP_SELF']);
// The EN version lives one level up: /Cabine/pages/<page>
$enUrl = '/Cabine/pages/' . $currentPage;
// Preserve query string if any
if (!empty($_SERVER['QUERY_STRING'])) {
    $enUrl .= '?' . $_SERVER['QUERY_STRING'];
}
?>

<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-heartbeat"></i>
                    <span>MediBook</span>
                </div>
                <a href="index.php" class="nav-link header-home-link">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
            </div>

            <div class="header-right">
                <nav class="nav-menu" id="navMenu" aria-label="Navigation principale">
                    <a href="index.php" class="nav-link nav-home-mobile">
                        <i class="fas fa-home"></i>
                        <span>Accueil</span>
                    </a>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="nav-link nav-link-auth header-link-btn">Connexion</a>
                    <?php else: ?>
                    <a href="patient_dashboard.php" class="dashboard-btn header-link-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>Tableau de bord</span>
                    </a>
                    <?php endif; ?>
                    <button class="btn-primary header-cta header-cta-mobile" type="button" onclick="checkLogin()">
                        <i class="fas fa-calendar-check"></i>
                        <span>Prendre rendez-vous</span>
                    </button>
                </nav>

                <div class="header-actions">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="nav-link nav-link-auth header-link-btn">Connexion</a>
                    <?php else: ?>
                    <a href="patient_dashboard.php" class="dashboard-btn header-link-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>Tableau de bord</span>
                    </a>
                    <?php endif; ?>
                    <button class="btn-primary header-cta" type="button" onclick="checkLogin()">
                        <i class="fas fa-calendar-check"></i>
                        <span>Prendre rendez-vous</span>
                    </button>

                    <!-- Language Switcher -->
                    <div class="lang-switcher" aria-label="Sélecteur de langue">
                        <span class="lang-active">
                            <img src="https://flagcdn.com/w20/fr.png" alt="FR" width="20" height="14"> FR
                        </span>
                        <span class="lang-divider">|</span>
                        <a href="<?= htmlspecialchars($enUrl, ENT_QUOTES) ?>" class="lang-option" title="Switch to English">
                            <img src="https://flagcdn.com/w20/gb.png" alt="EN" width="20" height="14"> EN
                        </a>
                    </div>
                </div>
            </div>

            <button class="mobile-menu-toggle" id="mobileMenuToggle" type="button" aria-controls="navMenu"
                aria-expanded="false" aria-label="Ouvrir le menu de navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<style>
.lang-switcher {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 20px;
    padding: 4px 12px;
    margin-left: 6px;
    transition: background 0.2s;
}
.lang-switcher:hover {
    background: rgba(255,255,255,0.15);
}
.lang-active {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--primary-color, #2563eb);
    font-weight: 700;
}
.lang-active img {
    border-radius: 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.lang-divider {
    color: rgba(100,116,139,0.5);
    font-weight: 300;
}
.lang-option {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--text-secondary, #64748b);
    text-decoration: none;
    transition: color 0.2s;
}
.lang-option img {
    border-radius: 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    opacity: 0.75;
    transition: opacity 0.2s;
}
.lang-option:hover {
    color: var(--primary-color, #2563eb);
}
.lang-option:hover img {
    opacity: 1;
}
</style>

<script>
function checkLogin() {
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    if (isLoggedIn) {
        window.location.href = "appointment-slots.php";
    } else {
        alert("Vous devez vous connecter avant de prendre un rendez-vous.");
    }
}
</script>
