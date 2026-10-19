<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des médecins - Cabinet Médical</title>
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
                <h1 class="page-title">Liste des médecins disponibles</h1>
                <p class="page-subtitle">Trouvez un médecin qualifié et prenez rendez-vous</p>

                <div class="filter-section">
                    <form class="filter-section" method="get">
                        <label class="filter-label" for="specialityFilter">Filtrer par spécialité :</label>
                        <select id="specialityFilter" name="specialty" onchange="this.form.submit()">
                            <option value="">Tous les médecins</option>
                            <?php foreach ($specialties as $spec): ?>
                            <option value="<?= $spec['specialty_id']; ?>"
                                <?= isset($selectedSpecialty) && $selectedSpecialty === (int)$spec['specialty_id'] ? 'selected' : ''; ?>>
                                <?= ucfirst($spec['specialty_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript>
                            <button type="submit" class="btn-book" style="max-width:200px;">Appliquer</button>
                        </noscript>
                    </form>
                </div>

                <div class="doctors-grid" id="doctorsContainer">
                    <?php if ($result && $result->num_rows > 0): ?>
                    <?php include 'table-doctor.php'; ?>
                    <?php else: ?>
                    <p class="no-doctors">Aucun médecin disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
