document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("patientModal");
  const closeBtn = document.getElementById("closePatientModal");
  const historyContainer = document.getElementById("modalPatientHistory");

  if (!modal) {
    return;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = value || "-";
    }
  }

  document.addEventListener("click", function (event) {
    const btn = event.target.closest(".patient-view-btn");
    if (!btn) {
      return;
    }

    setText("modalPatientName", btn.dataset.name || "Patient Details");
    setText(
      "modalPatientMeta",
      `Patient ID #${btn.dataset.id || "-"} | ${btn.dataset.bookings || 0} bookings`,
    );
    setText("modalPatientEmail", btn.dataset.email || "-");
    setText("modalPatientPhone", btn.dataset.phone || "-");
    setText("modalPatientBirth", btn.dataset.birth || "-");
    setText("modalPatientCreated", btn.dataset.created || "-");
    setText("modalPatientBookings", btn.dataset.bookings || "0");
    setText("modalPatientWithYou", btn.dataset.withYou || "0");
    setText("modalPatientLast", btn.dataset.last || "No bookings");

    const avatar = document.getElementById("modalPatientAvatar");
    if (avatar) {
      avatar.src = btn.dataset.avatar || "../../images/user.png";
      avatar.alt = btn.dataset.name || "Patient";
    }

    historyContainer.innerHTML = "";
    const history = JSON.parse(btn.dataset.history || "[]");

    if (!history.length) {
      historyContainer.innerHTML =
        '<p class="history-empty">This patient has no booking history yet.</p>';
    } else {
      history.forEach((item) => {
        const row = document.createElement("div");
        row.className = "history-item";
        row.innerHTML = `
          <div>
            <strong>${item.doctor}</strong>
            <span>${item.specialty}</span>
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
