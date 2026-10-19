document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("doctorModal");
  const closeBtn = document.getElementById("closeDoctorModal");
  const historyContainer = document.getElementById("modalDoctorHistory");

  if (!modal || !historyContainer) {
    return;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = value || "-";
    }
  }

  document.addEventListener("click", function (event) {
    const btn = event.target.closest(".doctor-view-btn");
    if (!btn) {
      return;
    }

    setText("modalDoctorName", btn.dataset.name || "Doctor Details");
    setText(
      "modalDoctorMeta",
      `Doctor ID #${btn.dataset.id || "-"} | ${btn.dataset.bookings || 0} appointments`,
    );
    setText("modalDoctorEmail", btn.dataset.email || "-");
    setText("modalDoctorPhone", btn.dataset.phone || "-");
    setText("modalDoctorSpecialty", btn.dataset.specialty || "-");
    setText("modalDoctorLocation", btn.dataset.location || "-");
    setText("modalDoctorExperience", btn.dataset.experience || "-");
    setText("modalDoctorCreated", btn.dataset.created || "-");
    setText("modalDoctorBookings", btn.dataset.bookings || "0");
    setText("modalDoctorScheduled", btn.dataset.scheduled || "0");
    setText("modalDoctorCompleted", btn.dataset.completed || "0");
    setText("modalDoctorCancelled", btn.dataset.cancelled || "0");
    setText("modalDoctorLast", btn.dataset.last || "No bookings");

    const avatar = document.getElementById("modalDoctorAvatar");
    if (avatar) {
      avatar.src = btn.dataset.avatar || "../../images/user.png";
      avatar.alt = btn.dataset.name || "Doctor";
    }

    historyContainer.innerHTML = "";
    const history = JSON.parse(btn.dataset.history || "[]");

    if (!history.length) {
      historyContainer.innerHTML =
        '<p class="history-empty">This doctor has no appointment history yet.</p>';
    } else {
      history.forEach((item) => {
        const row = document.createElement("div");
        row.className = "history-item";
        row.innerHTML = `
          <div>
            <strong>${item.patient}</strong>
            <span>${item.email}</span>
          </div>
          <div>
            <strong>${item.time}</strong>
            <span class="history-status ${item.status}">${item.status}</span>
          </div>
        `;
        historyContainer.appendChild(row);
      });
    }

    modal.classList.add("show");
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      modal.classList.remove("show");
    });
  }

  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      modal.classList.remove("show");
    }
  });
});
