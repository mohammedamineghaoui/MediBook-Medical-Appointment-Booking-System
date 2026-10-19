<?php
$doctor_id = $doctor['doctor_id'];

$rating_sql = "
    SELECT 
        AVG(r.rating) AS avg_rating,
        COUNT(r.rating_id) AS total_votes
    FROM ratings r
    INNER JOIN appointments a ON r.appointment_id = a.appointment_id
    WHERE a.doctor_id = ?
";

$rating_stmt = $conn->prepare($rating_sql);
$rating_stmt->bind_param("i", $doctor_id);
$rating_stmt->execute();

$rating_result = $rating_stmt->get_result();
$rating_row = $rating_result->fetch_assoc();

$avg_rating = $rating_row['avg_rating'] ?? 0;
$total_votes = $rating_row['total_votes'] ?? 0;

$rating_stmt->close();
?>