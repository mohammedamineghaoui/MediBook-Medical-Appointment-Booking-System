// Appointment Slots Page JavaScript

let selectedDate = null;
let selectedTime = null;
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const summaryCard = document.getElementById("appointmentSummary");
const timeSlotsContainer = document.getElementById("timeSlots");
const closeModalButton = document.querySelector(".close-modal");
const loginModal = document.getElementById("loginModal");

// Initialize calendar
function initCalendar() {
  const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  document.getElementById(
    "currentMonth"
  ).textContent = `${monthNames[currentMonth]} ${currentYear}`;

  renderCalendar();
}

function renderCalendar() {
  const calendarGrid = document.getElementById("calendarGrid");
  calendarGrid.innerHTML = "";

  // Day headers
  const dayHeaders = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  dayHeaders.forEach((day) => {
    const header = document.createElement("div");
    header.className = "calendar-day-header";
    header.textContent = day;
    calendarGrid.appendChild(header);
  });

  // Get first day of month and number of days
  const firstDay = new Date(currentYear, currentMonth, 1).getDay();
  const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
  const today = new Date();

  // Previous month days
  const prevMonthDays = new Date(currentYear, currentMonth, 0).getDate();
  for (let i = firstDay - 1; i >= 0; i--) {
    const day = document.createElement("div");
    day.className = "calendar-day other-month";
    day.textContent = prevMonthDays - i;
    calendarGrid.appendChild(day);
  }

  // Current month days
  for (let i = 1; i <= daysInMonth; i++) {
    const day = document.createElement("div");
    const date = new Date(currentYear, currentMonth, i);
    day.className = "calendar-day available";
    day.textContent = i;

    // Mark today
    if (date.toDateString() === today.toDateString()) {
      day.classList.add("today");
    }

    // Mark past dates as unavailable
    if (date < today) {
      day.classList.remove("available");
      day.classList.add("unavailable");
    }

    // Add click handler
    if (!day.classList.contains("unavailable")) {
      day.addEventListener("click", () => {
        document
          .querySelectorAll(".calendar-day")
          .forEach((d) => d.classList.remove("selected"));
        day.classList.add("selected");
        selectedDate = new Date(currentYear, currentMonth, i);
        updateTimeSlots();
        updateSummary();

        if (window.innerWidth < 969) {
          timeSlotsContainer.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }
      });
    }

    calendarGrid.appendChild(day);
  }

  // Next month days
  const totalCells = calendarGrid.children.length;
  const remainingCells = 42 - totalCells; // 6 rows * 7 days
  for (let i = 1; i <= remainingCells; i++) {
    const day = document.createElement("div");
    day.className = "calendar-day other-month";
    day.textContent = i;
    calendarGrid.appendChild(day);
  }
}

// Calendar navigation
document.getElementById("prevMonth").addEventListener("click", () => {
  currentMonth--;
  if (currentMonth < 0) {
    currentMonth = 11;
    currentYear--;
  }
  initCalendar();
});

document.getElementById("nextMonth").addEventListener("click", () => {
  currentMonth++;
  if (currentMonth > 11) {
    currentMonth = 0;
    currentYear++;
  }
  initCalendar();
});

// Generate time slots
function updateTimeSlots() {
  timeSlotsContainer.innerHTML = "";

  if (!selectedDate) {
    timeSlotsContainer.innerHTML = "<p>Please select a date first</p>";
    return;
  }

  const year = selectedDate.getFullYear();
  const month = String(selectedDate.getMonth() + 1).padStart(2, "0");
  const day = String(selectedDate.getDate()).padStart(2, "0");
  const dateISO = `${year}-${month}-${day}`;

  fetch(
    `../php/get-unavailable-slots.php?doctor_id=${doctorId}&date=${dateISO}`
  )
    .then((res) => res.json())
    .then((data) => {
      // الطبيب لا يعمل هذا اليوم
      if (data.unavailable === "closed") {
        timeSlotsContainer.innerHTML =
          '<p style="text-align:center;color:red">Doctor does not work this day</p>';
        return;
      }

      const available = data.available;
      const unavailable = data.unavailable;

      available.forEach((slot) => {
        const div = document.createElement("div");
        div.classList.add("time-slot");
        div.textContent = slot;

        // تظليل غير المتاح
        if (unavailable.includes(slot)) {
          div.classList.add("unavailable");
        } else {
          div.addEventListener("click", () => {
            document
              .querySelectorAll(".time-slot")
              .forEach((x) => x.classList.remove("selected"));
            div.classList.add("selected");
            selectedTime = slot;
            updateSummary();

            if (window.innerWidth < 969 && summaryCard) {
              summaryCard.scrollIntoView({
                behavior: "smooth",
                block: "nearest",
              });
            }
          });
        }

        timeSlotsContainer.appendChild(div);
      });
    });
}

// Update appointment summary
function updateSummary() {
  if (selectedDate && selectedTime) {
    summaryCard.style.display = "block";
    // Get selected doctor name
    const doctorName = document.querySelector(
      ".doctor-option-info h3"
    ).textContent;
    // Format date
    const dateStr = selectedDate.toLocaleDateString("en-US", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    document.getElementById("summaryDoctor").textContent = doctorName;
    document.getElementById("summaryDate").textContent = dateStr;
    document.getElementById("summaryTime").textContent = selectedTime;
  } else {
    summaryCard.style.display = "none";
  }
}

// Close modal
if (closeModalButton && loginModal) {
  closeModalButton.addEventListener("click", () => {
    loginModal.classList.remove("active");
  });
}

// Close modal on outside click
window.addEventListener("click", (e) => {
  if (loginModal && e.target === loginModal) {
    loginModal.classList.remove("active");
  }
});

// Initialize
initCalendar();
document.getElementById("confirmBooking").addEventListener("click", () => {
  if (!selectedDate || !selectedTime) {
    alert("Please select a date and time first.");
    return;
  }

  const form = document.getElementById("appointmentForm");
  const inputDate = document.getElementById("inputAppointmentDate");
  const inputTime = document.getElementById("inputAppointmentTime");

  const year = selectedDate.getFullYear();
  const month = String(selectedDate.getMonth() + 1).padStart(2, "0");
  const day = String(selectedDate.getDate()).padStart(2, "0");
  const dateISO = `${year}-${month}-${day}`; // مثال: 2025-12-07

  inputDate.value = dateISO;
  inputTime.value = selectedTime;
  form.submit();
});
