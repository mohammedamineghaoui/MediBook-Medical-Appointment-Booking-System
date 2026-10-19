document.addEventListener("DOMContentLoaded", function () {
  const params = new URLSearchParams(window.location.search);
  const popup = document.getElementById("popup");
  const closeBtn = document.getElementById("closePopup");
  const statusSelect = document.getElementById("status");
  const appointmentIdInput = document.getElementById("appointment_id");
  const nameEl = document.getElementById("p_name");
  const emailEl = document.getElementById("p_email");
  const phoneEl = document.getElementById("p_phone");
  const dateEl = document.getElementById("p_date");
  const timeEl = document.getElementById("p_time");
  const createdAtEl = document.getElementById("p_createdAt");

  function showToast(message, type) {
    const toast = document.createElement("div");
    toast.className = `status-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      toast.classList.add("show");
    });

    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 250);
    }, 3200);
  }

  const errorMessages = {
    invalid_data: "Invalid appointment data.",
    past_scheduled: "Past appointments cannot be marked as scheduled.",
    update_failed: "Could not update appointment status.",
    invalid_request: "Invalid request.",
  };

  if (params.get("error") && errorMessages[params.get("error")]) {
    showToast(errorMessages[params.get("error")], "error");
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete("error");
    window.history.replaceState({}, "", cleanUrl);
  }

  if (params.get("updated") === "1") {
    showToast("Appointment status updated successfully.", "success");
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete("updated");
    window.history.replaceState({}, "", cleanUrl);
  }

  if (!popup) {
    return;
  }

  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".view-btn");
    if (!btn) return;

    if (appointmentIdInput) {
      appointmentIdInput.value = btn.dataset.id || "";
    }

    if (nameEl) {
      nameEl.textContent = btn.dataset.name || "";
    }

    if (emailEl) {
      const email = btn.dataset.email || "";
      emailEl.textContent = email;
      emailEl.href = email ? "mailto:" + email : "#";
    }

    if (phoneEl) {
      const rawPhone = btn.dataset.phone || "";
      let whatsappPhone = rawPhone.replace(/\s+/g, "").replace("+", "");

      if (whatsappPhone.startsWith("0")) {
        whatsappPhone = "213" + whatsappPhone.substring(1);
      }

      phoneEl.textContent = rawPhone;

      if (whatsappPhone) {
        const message =
          "Hello " + (btn.dataset.name || "") + ", about your appointment";
        phoneEl.href =
          "https://wa.me/" +
          whatsappPhone +
          "?text=" +
          encodeURIComponent(message);
        phoneEl.target = "_blank";
      } else {
        phoneEl.href = "#";
        phoneEl.removeAttribute("target");
      }
    }

    if (dateEl) {
      dateEl.textContent = btn.dataset.date || "";
    }

    if (timeEl) {
      timeEl.textContent = btn.dataset.time || "";
    }

    if (createdAtEl) {
      createdAtEl.textContent = btn.dataset.createdat || "";
    }

    if (statusSelect) {
      const normalizedStatus = (btn.dataset.status || "scheduled")
        .trim()
        .toLowerCase();

      statusSelect.value = ["scheduled", "completed", "cancelled"].includes(
        normalizedStatus,
      )
        ? normalizedStatus
        : "scheduled";
    }

    popup.classList.add("show");
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      popup.classList.remove("show");
    });
  }

  window.addEventListener("click", function (e) {
    if (e.target === popup) {
      popup.classList.remove("show");
    }
  });
});
