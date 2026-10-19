document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('ratingModal');
  const closeBtn = document.getElementById('closeRatingModal');
  const starsContainer = document.getElementById('modalRatingStars');

  if (!modal || !starsContainer) {
    return;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = value || '-';
    }
  }

  function renderStars(rating) {
    starsContainer.innerHTML = '';
    for (let i = 1; i <= 5; i += 1) {
      const star = document.createElement('i');
      star.className = `${i <= rating ? 'fa-solid' : 'fa-regular'} fa-star`;
      starsContainer.appendChild(star);
    }
  }

  document.addEventListener('click', function (event) {
    const btn = event.target.closest('.rating-comment-btn');
    if (!btn) {
      return;
    }

    const rating = Number.parseInt(btn.dataset.rating || '0', 10);

    setText('modalRatingTitle', `${rating || '-'} out of 5 stars`);
    setText('modalPatientName', btn.dataset.patient);
    setText('modalPatientEmail', btn.dataset.patientEmail);
    setText('modalDoctorName', btn.dataset.doctor);
    setText('modalSpecialty', btn.dataset.specialty);
    setText('modalAppointmentDate', btn.dataset.date);
    setText('modalAppointmentTime', btn.dataset.time);
    setText('modalAppointmentStatus', btn.dataset.status);
    setText('modalRatingComment', btn.dataset.comment);
    renderStars(rating);

    modal.classList.add('show');
  });

  function closeModal() {
    modal.classList.remove('show');
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }

  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  });
});
