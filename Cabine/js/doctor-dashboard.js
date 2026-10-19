// Doctor Dashboard JavaScript

// Sample data (in a real app, this would come from an API)
const appointmentsData = [
    {
        id: 1,
        patientName: 'John Smith',
        date: '2024-12-15',
        time: '09:00',
        contact: 'john.smith@email.com',
        phone: '+1 (555) 123-4567',
        reason: 'Cardiac consultation',
        status: 'scheduled'
    },
    {
        id: 2,
        patientName: 'Maria Garcia',
        date: '2024-12-15',
        time: '10:30',
        contact: 'maria.garcia@email.com',
        phone: '+1 (555) 234-5678',
        reason: 'Follow-up examination',
        status: 'scheduled'
    },
    {
        id: 3,
        patientName: 'David Lee',
        date: '2024-12-15',
        time: '14:00',
        contact: 'david.lee@email.com',
        phone: '+1 (555) 345-6789',
        reason: 'Routine checkup',
        status: 'scheduled'
    },
    {
        id: 4,
        patientName: 'Sarah Johnson',
        date: '2024-12-14',
        time: '11:00',
        contact: 'sarah.j@email.com',
        phone: '+1 (555) 456-7890',
        reason: 'ECG review',
        status: 'completed'
    },
    {
        id: 5,
        patientName: 'Michael Brown',
        date: '2024-12-14',
        time: '15:30',
        contact: 'michael.b@email.com',
        phone: '+1 (555) 567-8901',
        reason: 'Hypertension management',
        status: 'completed'
    },
    {
        id: 6,
        patientName: 'Emily Davis',
        date: '2024-12-13',
        time: '10:00',
        contact: 'emily.d@email.com',
        phone: '+1 (555) 678-9012',
        reason: 'Cardiac consultation',
        status: 'cancelled'
    }
];

const patientsData = [
    { id: 1, name: 'John Smith', lastVisit: '2024-12-10', condition: 'Hypertension' },
    { id: 2, name: 'Maria Garcia', lastVisit: '2024-12-08', condition: 'Arrhythmia' },
    { id: 3, name: 'David Lee', lastVisit: '2024-12-05', condition: 'Cardiac checkup' },
    { id: 4, name: 'Sarah Johnson', lastVisit: '2024-12-14', condition: 'ECG review' },
    { id: 5, name: 'Michael Brown', lastVisit: '2024-12-12', condition: 'Hypertension' }
];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    updateStats();
    renderTodaySchedule();
    renderRecentPatients();
    renderAppointmentsTable();
    
    // Set minimum date for appointment date input
    const appointmentDateInput = document.getElementById('appointmentDate');
    if (appointmentDateInput) {
        const today = new Date().toISOString().split('T')[0];
        appointmentDateInput.setAttribute('min', today);
    }
});

// Update statistics
function updateStats() {
    const today = new Date().toISOString().split('T')[0];
    const todayApps = appointmentsData.filter(apt => apt.date === today && apt.status !== 'cancelled');
    const pendingApps = appointmentsData.filter(apt => apt.status === 'scheduled');
    const cancelledToday = appointmentsData.filter(apt => apt.date === today && apt.status === 'cancelled');
    
    document.getElementById('todayAppointments').textContent = todayApps.length;
    document.getElementById('totalPatients').textContent = patientsData.length;
    document.getElementById('pendingAppointments').textContent = pendingApps.length;
    document.getElementById('cancelledAppointments').textContent = cancelledToday.length;
}

// Render today's schedule
function renderTodaySchedule() {
    const scheduleList = document.getElementById('scheduleList');
    const today = new Date().toISOString().split('T')[0];
    const filter = document.getElementById('scheduleFilter').value;
    
    let todayAppointments = appointmentsData.filter(apt => apt.date === today);
    
    if (filter === 'upcoming') {
        const now = new Date();
        todayAppointments = todayAppointments.filter(apt => {
            const aptTime = new Date(`${apt.date}T${apt.time}`);
            return aptTime > now && apt.status === 'scheduled';
        });
    } else if (filter === 'completed') {
        todayAppointments = todayAppointments.filter(apt => apt.status === 'completed');
    }
    
    if (todayAppointments.length === 0) {
        scheduleList.innerHTML = '<p style="text-align: center; color: var(--soft-grey); padding: 2rem;">No appointments scheduled for today</p>';
        return;
    }
    
    scheduleList.innerHTML = todayAppointments.map(apt => `
        <div class="schedule-item">
            <div class="schedule-time">${apt.time}</div>
            <div class="schedule-info">
                <h4>${apt.patientName}</h4>
                <p>${apt.reason}</p>
            </div>
            <span class="schedule-status ${apt.status}">${apt.status.charAt(0).toUpperCase() + apt.status.slice(1)}</span>
        </div>
    `).join('');
}

// Render recent patients
function renderRecentPatients() {
    const patientsList = document.getElementById('patientsList');
    const recentPatients = patientsData.slice(0, 5);
    
    patientsList.innerHTML = recentPatients.map(patient => `
        <div class="patient-item">
            <div class="patient-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="patient-info">
                <h4>${patient.name}</h4>
                <p>Last visit: ${new Date(patient.lastVisit).toLocaleDateString()}</p>
            </div>
        </div>
    `).join('');
}

// Render appointments table
function renderAppointmentsTable(filteredData = null) {
    const tbody = document.getElementById('appointmentsTableBody');
    const data = filteredData || appointmentsData;
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--soft-grey);">No appointments found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(apt => `
        <tr>
            <td>${apt.patientName}</td>
            <td>${new Date(apt.date).toLocaleDateString()} at ${apt.time}</td>
            <td>
                <div>${apt.contact}</div>
                <small style="color: var(--soft-grey);">${apt.phone}</small>
            </td>
            <td>${apt.reason}</td>
            <td><span class="status-badge ${apt.status}">${apt.status.charAt(0).toUpperCase() + apt.status.slice(1)}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon view" onclick="viewAppointment(${apt.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon edit" onclick="editAppointment(${apt.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deleteAppointment(${apt.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Filter schedule
document.getElementById('scheduleFilter').addEventListener('change', () => {
    renderTodaySchedule();
});

// Filter appointments
document.getElementById('appointmentFilter').addEventListener('change', (e) => {
    const filter = e.target.value;
    let filtered = appointmentsData;
    
    if (filter !== 'all') {
        filtered = appointmentsData.filter(apt => apt.status === filter);
    }
    
    renderAppointmentsTable(filtered);
});

// Search appointments
document.getElementById('appointmentSearch').addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const filter = document.getElementById('appointmentFilter').value;
    
    let filtered = appointmentsData;
    
    if (filter !== 'all') {
        filtered = filtered.filter(apt => apt.status === filter);
    }
    
    if (searchTerm) {
        filtered = filtered.filter(apt => 
            apt.patientName.toLowerCase().includes(searchTerm) ||
            apt.reason.toLowerCase().includes(searchTerm) ||
            apt.contact.toLowerCase().includes(searchTerm)
        );
    }
    
    renderAppointmentsTable(filtered);
});

// Modal functions
function showAddAppointmentModal() {
    document.getElementById('addAppointmentModal').classList.add('active');
}

function closeAddAppointmentModal() {
    document.getElementById('addAppointmentModal').classList.remove('active');
    document.getElementById('addAppointmentForm').reset();
}

// Add appointment form
document.getElementById('addAppointmentForm').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const newAppointment = {
        id: appointmentsData.length + 1,
        patientName: formData.get('patientName'),
        date: formData.get('appointmentDate'),
        time: formData.get('appointmentTime'),
        contact: formData.get('patientEmail'),
        phone: '+1 (555) 000-0000',
        reason: formData.get('appointmentReason'),
        status: formData.get('appointmentStatus')
    };
    
    appointmentsData.unshift(newAppointment);
    updateStats();
    renderTodaySchedule();
    renderAppointmentsTable();
    closeAddAppointmentModal();
    
    alert('Appointment added successfully!');
});

// View appointment
function viewAppointment(id) {
    const appointment = appointmentsData.find(apt => apt.id === id);
    if (!appointment) return;
    
    const modal = document.getElementById('appointmentDetailsModal');
    const content = document.getElementById('appointmentDetailsContent');
    
    content.innerHTML = `
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div>
                <strong>Patient Name:</strong> ${appointment.patientName}
            </div>
            <div>
                <strong>Date & Time:</strong> ${new Date(appointment.date).toLocaleDateString()} at ${appointment.time}
            </div>
            <div>
                <strong>Email:</strong> ${appointment.contact}
            </div>
            <div>
                <strong>Phone:</strong> ${appointment.phone}
            </div>
            <div>
                <strong>Reason:</strong> ${appointment.reason}
            </div>
            <div>
                <strong>Status:</strong> <span class="status-badge ${appointment.status}">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
}

function closeAppointmentDetailsModal() {
    document.getElementById('appointmentDetailsModal').classList.remove('active');
}

// Edit appointment
function editAppointment(id) {
    const appointment = appointmentsData.find(apt => apt.id === id);
    if (!appointment) return;
    
    // Populate form with appointment data
    document.getElementById('patientName').value = appointment.patientName;
    document.getElementById('patientEmail').value = appointment.contact;
    document.getElementById('appointmentDate').value = appointment.date;
    document.getElementById('appointmentTime').value = appointment.time;
    document.getElementById('appointmentReason').value = appointment.reason;
    document.getElementById('appointmentStatus').value = appointment.status;
    
    showAddAppointmentModal();
    
    // Update form submission to edit instead of add
    const form = document.getElementById('addAppointmentForm');
    const originalSubmit = form.onsubmit;
    
    form.onsubmit = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        Object.assign(appointment, {
            patientName: formData.get('patientName'),
            date: formData.get('appointmentDate'),
            time: formData.get('appointmentTime'),
            contact: formData.get('patientEmail'),
            reason: formData.get('appointmentReason'),
            status: formData.get('appointmentStatus')
        });
        
        updateStats();
        renderTodaySchedule();
        renderAppointmentsTable();
        closeAddAppointmentModal();
        form.onsubmit = originalSubmit;
        form.reset();
        
        alert('Appointment updated successfully!');
    };
}

// Delete appointment
function deleteAppointment(id) {
    if (confirm('Are you sure you want to delete this appointment?')) {
        const index = appointmentsData.findIndex(apt => apt.id === id);
        if (index > -1) {
            appointmentsData.splice(index, 1);
            updateStats();
            renderTodaySchedule();
            renderAppointmentsTable();
            alert('Appointment deleted successfully!');
        }
    }
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('isLoggedIn');
        window.location.href = 'index.html';
    }
}

// Close modals on outside click
window.addEventListener('click', (e) => {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});

