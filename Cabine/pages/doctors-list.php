<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors List - Medical Cabinet</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/doctorlistpage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php
    session_start();
            include 'header.php'; 
            include '../php/conn.php'; 
            include '../php/get-doctors.php';
    ?>

    <main>
        <section class="page-section">
            <div class="container">
                <h1 class="page-title">Available Doctors List</h1>
                <p class="page-subtitle">Find and book an appointment with our qualified doctors</p>

                <div class="filter-section">
                    <form class="filter-section" method="get">
                        <label class="filter-label" for="specialityFilter">Filter by specialty:</label>
                        <select id="specialityFilter" name="specialty" onchange="this.form.submit()">
                            <option value="">All Doctors</option>
                            <?php foreach ($specialties as $spec): ?>
                            <option value="<?= $spec['specialty_id']; ?>"
                                <?= isset($selectedSpecialty) && $selectedSpecialty === (int)$spec['specialty_id'] ? 'selected' : ''; ?>>
                                <?= ucfirst($spec['specialty_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript>
                            <button type="submit" class="btn-book" style="max-width:200px;">Apply</button>
                        </noscript>
                    </form>
                </div>

                <div class="doctors-grid" id="doctorsContainer">
                    <?php if ($result && $result->num_rows > 0): ?>
                    <?php include 'table-doctor.php'; ?>
                    <?php else: ?>
                    <p class="no-doctors">No doctors available</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>

</html>