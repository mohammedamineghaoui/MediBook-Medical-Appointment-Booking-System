// Doctor Profile Page JavaScript

// Tab functionality
const tabButtons = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        const targetTab = button.getAttribute('data-tab');
        
        // Remove active class from all tabs and contents
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding content
        button.classList.add('active');
        document.getElementById(targetTab).classList.add('active');
    });
});

// Get doctor info from URL parameters (if coming from doctors list)
const urlParams = new URLSearchParams(window.location.search);
const doctorId = urlParams.get('id');

// Sample doctor data (in a real app, this would come from a database)
const doctors = {
    'sarah-johnson': {
        name: 'Dr. Sarah Johnson',
        specialty: 'Cardiology Specialist',
        experience: '15 Years Experience',
        rating: '4.9'
    },
    'michael-chen': {
        name: 'Dr. Michael Chen',
        specialty: 'Pediatrics Specialist',
        experience: '12 Years Experience',
        rating: '4.8'
    },
    'emily-rodriguez': {
        name: 'Dr. Emily Rodriguez',
        specialty: 'Gynecology Specialist',
        experience: '10 Years Experience',
        rating: '4.9'
    },
    'james-wilson': {
        name: 'Dr. James Wilson',
        specialty: 'Dentistry Specialist',
        experience: '18 Years Experience',
        rating: '4.7'
    }
};

// Update doctor info if ID is provided
if (doctorId && doctors[doctorId]) {
    const doctor = doctors[doctorId];
    document.getElementById('doctorName').textContent = doctor.name;
    document.getElementById('doctorSpecialty').textContent = doctor.specialty;
}

