document.addEventListener('DOMContentLoaded', () => {
  const dateContainer = document.getElementById('currentDate');
  if (dateContainer) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateContainer.textContent = new Date().toLocaleDateString('en-US', options);
  }

  const menuToggle = document.getElementById('menuToggle');
  const closeSidebarBtn = document.getElementById('closeSidebar');
  const pinSidebarBtn = document.getElementById('pinSidebar');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const appContainer = document.querySelector('.admin-app');

  function closeSidebar() {
    if (!sidebar || !overlay) {
      return;
    }

    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  function toggleSidebar() {
    if (!sidebar || !overlay) {
      return;
    }

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
  }

  if (menuToggle) {
    menuToggle.addEventListener('click', toggleSidebar);
  }

  if (closeSidebarBtn) {
    closeSidebarBtn.addEventListener('click', toggleSidebar);
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

  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
      closeSidebar();
    } else if (sidebar && appContainer && pinSidebarBtn) {
      sidebar.classList.remove('is-pinned');
      appContainer.classList.remove('sidebar-pinned');
      pinSidebarBtn.classList.remove('active');
      pinSidebarBtn.setAttribute('aria-pressed', 'false');
    }
  });

  const notifBtn = document.querySelector('.notification-btn');
  if (notifBtn) {
    notifBtn.addEventListener('click', function () {
      const dot = this.querySelector('.notification-dot');
      if (dot) {
        dot.style.display = 'none';
      }
    });
  }
});
