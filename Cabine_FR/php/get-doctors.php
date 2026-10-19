<?php
// specialties dropdown
$specialties = [];
$spec_sql = "SELECT specialty_id, specialty_name FROM specialties ORDER BY specialty_name";
$spec_res = $conn->query($spec_sql);
while ($r = $spec_res->fetch_assoc()) $specialties[] = $r;

// selected specialty
$selectedSpecialty = (isset($_GET['specialty']) && $_GET['specialty'] !== '') ? (int)$_GET['specialty'] : null;

// base query
$sql = "SELECT doctors.*, specialties.specialty_name AS specialty_name
        FROM doctors
        LEFT JOIN specialties ON doctors.specialty_id = specialties.specialty_id";

if ($selectedSpecialty) {
    $sql .= " WHERE doctors.specialty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selectedSpecialty);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

?>