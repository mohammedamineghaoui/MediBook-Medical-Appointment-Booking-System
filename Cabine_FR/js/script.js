// ===== Mobile Menu Toggle =====
const mobileMenuToggle = document.getElementById("mobileMenuToggle");
const navMenu = document.getElementById("navMenu");
const pageBody = document.body;
const mobileMenuIcon = mobileMenuToggle
  ? mobileMenuToggle.querySelector("i")
  : null;

function closeMobileMenu() {
  if (!navMenu || !mobileMenuToggle) {
    return;
  }

  navMenu.classList.remove("active");
  mobileMenuToggle.setAttribute("aria-expanded", "false");
  pageBody.classList.remove("menu-open");
  if (mobileMenuIcon) {
    mobileMenuIcon.classList.remove("fa-xmark");
    mobileMenuIcon.classList.add("fa-bars");
  }
}

if (mobileMenuToggle && navMenu) {
  mobileMenuToggle.addEventListener("click", () => {
    const isOpen = navMenu.classList.toggle("active");
    mobileMenuToggle.setAttribute("aria-expanded", String(isOpen));
    pageBody.classList.toggle("menu-open", isOpen);
    if (mobileMenuIcon) {
      mobileMenuIcon.classList.toggle("fa-bars", !isOpen);
      mobileMenuIcon.classList.toggle("fa-xmark", isOpen);
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeMobileMenu();
      closeDashboardSidebar();
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 900) {
      closeMobileMenu();
      closeDashboardSidebar();
    }

    if (window.innerWidth <= 1024) {
      unpinDashboardSidebar();
    }
  });
}

if (!mobileMenuToggle || !navMenu) {
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeDashboardSidebar();
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 900) {
      closeDashboardSidebar();
    }

    if (window.innerWidth <= 1024) {
      unpinDashboardSidebar();
    }
  });
}

// ===== Patient Dashboard Sidebar Toggle =====
const dashboardSidebarToggle = document.getElementById("dashboardSidebarToggle");
const dashboardSidebar = document.getElementById("dashboardSidebar");
const dashboardSidebarOverlay = document.getElementById(
  "dashboardSidebarOverlay"
);
const dashboardPinSidebar = document.getElementById("dashboardPinSidebar");
const dashboardShell = document.querySelector(".dashboard-shell");
const dashboardSidebarIcon = dashboardSidebarToggle
  ? dashboardSidebarToggle.querySelector("i")
  : null;

function closeDashboardSidebar() {
  if (!dashboardSidebar || !dashboardSidebarToggle) {
    return;
  }

  dashboardSidebar.classList.remove("is-open");
  if (dashboardSidebarOverlay) {
    dashboardSidebarOverlay.classList.remove("is-open");
  }
  dashboardSidebarToggle.setAttribute("aria-expanded", "false");
  pageBody.classList.remove("dashboard-sidebar-open");
  if (dashboardSidebarIcon) {
    dashboardSidebarIcon.classList.remove("fa-xmark");
    dashboardSidebarIcon.classList.add("fa-bars");
  }
}

function openDashboardSidebar() {
  if (!dashboardSidebar || !dashboardSidebarToggle) {
    return;
  }

  dashboardSidebar.classList.add("is-open");
  if (dashboardSidebarOverlay) {
    dashboardSidebarOverlay.classList.add("is-open");
  }
  dashboardSidebarToggle.setAttribute("aria-expanded", "true");
  pageBody.classList.add("dashboard-sidebar-open");
  if (dashboardSidebarIcon) {
    dashboardSidebarIcon.classList.remove("fa-bars");
    dashboardSidebarIcon.classList.add("fa-xmark");
  }
}

function unpinDashboardSidebar() {
  if (!dashboardSidebar || !dashboardShell || !dashboardPinSidebar) {
    return;
  }

  dashboardSidebar.classList.remove("is-pinned");
  dashboardShell.classList.remove("sidebar-pinned");
  dashboardPinSidebar.classList.remove("active");
  dashboardPinSidebar.setAttribute("aria-pressed", "false");
  dashboardPinSidebar.setAttribute("aria-label", "Keep sidebar open");
}

if (dashboardSidebarToggle && dashboardSidebar) {
  dashboardSidebarToggle.addEventListener("click", () => {
    if (dashboardSidebar.classList.contains("is-open")) {
      closeDashboardSidebar();
    } else {
      openDashboardSidebar();
    }
  });

  if (dashboardSidebarOverlay) {
    dashboardSidebarOverlay.addEventListener("click", closeDashboardSidebar);
  }

  dashboardSidebar.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeDashboardSidebar);
  });
}

if (dashboardPinSidebar && dashboardSidebar && dashboardShell) {
  dashboardPinSidebar.addEventListener("click", () => {
    const isPinned = dashboardSidebar.classList.toggle("is-pinned");
    dashboardShell.classList.toggle("sidebar-pinned", isPinned);
    dashboardPinSidebar.classList.toggle("active", isPinned);
    dashboardPinSidebar.setAttribute("aria-pressed", String(isPinned));
    dashboardPinSidebar.setAttribute(
      "aria-label",
      isPinned ? "Allow sidebar to collapse" : "Keep sidebar open"
    );
  });
}

// Close mobile menu when clicking on a link
const navLinks = document.querySelectorAll(".nav-link");
navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    closeMobileMenu();
  });
});

// ===== Smooth Scrolling =====
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

// ===== FAQ Accordion =====
const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach((item) => {
  const question = item.querySelector(".faq-question");

  question.addEventListener("click", () => {
    const isActive = item.classList.contains("active");

    // Close all FAQ items
    faqItems.forEach((faqItem) => {
      faqItem.classList.remove("active");
    });

    // Open clicked item if it wasn't active
    if (!isActive) {
      item.classList.add("active");
    }
  });
});

// ===== Doctor Data =====
const doctorsData = {
  dentistry: [
    { name: "Dr. James Wilson", experience: "18 years" },
    { name: "Dr. Lisa Anderson", experience: "12 years" },
    { name: "Dr. Amine Ghaoui", experience: "12 years" },
  ],
  pediatrics: [
    { name: "Dr. Michael Chen", experience: "12 years" },
    { name: "Dr. Sarah Martinez", experience: "10 years" },
  ],
  gynecology: [
    { name: "Dr. Emily Rodriguez", experience: "10 years" },
    { name: "Dr. Jennifer Brown", experience: "15 years" },
  ],
  cardiology: [
    { name: "Dr. Sarah Johnson", experience: "15 years" },
    { name: "Dr. Robert Taylor", experience: "20 years" },
  ],
  laboratory: [
    { name: "Dr. David Kim", experience: "8 years" },
    { name: "Dr. Amanda White", experience: "11 years" },
  ],
  general: [
    { name: "Dr. Thomas Moore", experience: "14 years" },
    { name: "Dr. Patricia Davis", experience: "16 years" },
  ],
};

// ===== Dynamic Doctor List =====
const specialtySelect = document.getElementById("specialty");
const doctorSelect = document.getElementById("doctor");

if (specialtySelect && doctorSelect) {
  specialtySelect.addEventListener("change", (e) => {
    const selectedSpecialty = e.target.value;
    doctorSelect.innerHTML = '<option value="">Choose a doctor</option>';

    if (selectedSpecialty && doctorsData[selectedSpecialty]) {
      doctorsData[selectedSpecialty].forEach((doctor) => {
        const option = document.createElement("option");
        option.value = doctor.name.toLowerCase().replace(/\s+/g, "-");
        option.textContent = doctor.name;
        doctorSelect.appendChild(option);
      });
    }
  });
}

// ===== Quick Filter Chips =====
const filterChips = document.querySelectorAll(".filter-chip");
filterChips.forEach((chip) => {
  chip.addEventListener("click", () => {
    filterChips.forEach((c) => c.classList.remove("active"));
    chip.classList.add("active");

    const filter = chip.getAttribute("data-filter");
    // Apply filter logic (can be enhanced with actual filtering)
    console.log("Filter applied:", filter);
  });
});

// ===== Clear Filters Button =====
const clearFiltersBtn = document.getElementById("clearFilters");
if (clearFiltersBtn) {
  clearFiltersBtn.addEventListener("click", () => {
    // Reset all form fields
    const searchForm = document.getElementById("searchForm");
    if (searchForm) {
      searchForm.reset();
    }

    // Reset filter chips
    filterChips.forEach((chip) => {
      chip.classList.remove("active");
      if (chip.getAttribute("data-filter") === "all") {
        chip.classList.add("active");
      }
    });

    // Reset doctor select
    const doctorSelect = document.getElementById("doctor");
    if (doctorSelect) {
      doctorSelect.innerHTML = '<option value="">Choose a doctor</option>';
    }
  });
}

// ===== Search Name Autocomplete (Simple) =====
const searchNameInput = document.getElementById("searchName");
if (searchNameInput) {
  // Simple search suggestions (can be enhanced with API call)
  const doctorNames = [
    "Dr. Sarah Johnson",
    "Dr. Michael Chen",
    "Dr. Emily Rodriguez",
    "Dr. James Wilson",
    "Dr. David Kim",
    "Dr. Lisa Anderson",
  ];

  searchNameInput.addEventListener("input", (e) => {
    const value = e.target.value.toLowerCase();
    // In a real app, this would show autocomplete suggestions
    if (value.length > 0) {
      console.log("Searching for:", value);
    }
  });
}

// ===== Set Minimum Date to Today =====
const dateInput = document.getElementById("date");
if (dateInput) {
  const today = new Date().toISOString().split("T")[0];
  dateInput.setAttribute("min", today);
}

// ===== Book Appointment Buttons =====

// ===== Header Scroll Effect =====
const header = document.querySelector(".header");

if (header) {
  window.addEventListener("scroll", () => {
    const currentScroll = window.pageYOffset;

    if (currentScroll > 100) {
      header.style.boxShadow = "0 10px 30px rgba(15, 23, 42, 0.12)";
    } else {
      header.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.1)";
    }
  });
}

// ===== Animate on Scroll (Simple) =====
const observerOptions = {
  threshold: 0.1,
  rootMargin: "0px 0px -50px 0px",
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = "1";
      entry.target.style.transform = "translateY(0)";
    }
  });
}, observerOptions);

// Observe all cards and sections
document.addEventListener("DOMContentLoaded", () => {
  const animatedElements = document.querySelectorAll(
    ".specialty-card, .doctor-card, .feature-card, .testimonial-card"
  );

  animatedElements.forEach((el) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    el.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(el);
  });
});

// ===== Close Mobile Menu on Outside Click =====
document.addEventListener("click", (e) => {
  const alertClose = e.target.closest("[data-alert-close]");
  if (alertClose) {
    const alertEl = alertClose.closest(".alert");
    if (alertEl) {
      alertEl.classList.add("is-hiding");
      window.setTimeout(() => alertEl.remove(), 220);
    }
    return;
  }

  if (
    navMenu &&
    mobileMenuToggle &&
    navMenu.classList.contains("active") &&
    !navMenu.contains(e.target) &&
    !mobileMenuToggle.contains(e.target)
  ) {
    closeMobileMenu();
  }
});
