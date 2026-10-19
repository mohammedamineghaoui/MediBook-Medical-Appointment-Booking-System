/**
 * MediBook Doctor Dashboard Simple Interactivity
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Current Date generation for welcome banner
    const dateContainer = document.getElementById('currentDate');
    if (dateContainer) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const today = new Date();
        dateContainer.textContent = today.toLocaleDateString('en-US', options);
    }

    // 2. Sidebar Toggle for Mobile Devices
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebarBtn = document.getElementById('closeSidebar');
    const pinSidebarBtn = document.getElementById('pinSidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const appContainer = document.querySelector('.app-container');

    function openSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.add('active');
        if (overlay) {
            overlay.classList.add('active');
        }
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('active');
        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.style.overflow = '';
    }

    function toggleSidebar() {
        if (!sidebar) {
            return;
        }

        if (sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function unpinSidebar() {
        if (!sidebar || !appContainer || !pinSidebarBtn) {
            return;
        }

        sidebar.classList.remove('is-pinned');
        appContainer.classList.remove('sidebar-pinned');
        pinSidebarBtn.classList.remove('active');
        pinSidebarBtn.setAttribute('aria-pressed', 'false');
        pinSidebarBtn.setAttribute('aria-label', 'Keep sidebar open');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (pinSidebarBtn && sidebar && appContainer) {
        pinSidebarBtn.addEventListener('click', () => {
            const isPinned = sidebar.classList.toggle('is-pinned');
            appContainer.classList.toggle('sidebar-pinned', isPinned);
            pinSidebarBtn.classList.toggle('active', isPinned);
            pinSidebarBtn.setAttribute('aria-pressed', String(isPinned));
            pinSidebarBtn.setAttribute('aria-label', isPinned ? 'Allow sidebar to collapse' : 'Keep sidebar open');
        });
    }

    // Handle resize to reset styles if moving from mobile to desktop view
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        } else {
            unpinSidebar();
        }
    });

    // 3. Navigation Active State Handling
    const navItems = document.querySelectorAll('.sidebar-nav ul li');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Remove active class from all
            navItems.forEach(nav => nav.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
        });
    });

    // 4. Simple Interaction for Notification Button
    const notifBtn = document.querySelector('.notification-btn');
    if (notifBtn) {
        notifBtn.addEventListener('click', function() {
            const dot = this.querySelector('.notification-dot');
            if (dot) {
                // Clear notification badge
                dot.style.display = 'none';
            }
        });
    }

    // 5. Action Menus interaction (just visual feedback)
    const actionBtns = document.querySelectorAll('.btn-icon');
    actionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Optional: Toggle a small context menu here
            // Removing focus just for cleaner visual
            this.blur();
        });
    });
});
