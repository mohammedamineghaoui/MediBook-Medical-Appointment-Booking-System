 <link rel="stylesheet" href="../css/doctorlistpage.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

 <?php
 $experienceYears = null;
 if (!empty($doctor['experience'])) {
     if (is_numeric($doctor['experience'])) {
         $experienceYears = (int) $doctor['experience'];
     } else {
         try {
             $experienceDate = new DateTime($doctor['experience']);
             $now = new DateTime();
             $yearsDiff = $experienceDate->diff($now)->y;
             $experienceYears = max(0, $yearsDiff);
         } catch (Exception $e) {
             $experienceYears = null;
         }
     }
 }
 ?>

 <div class="doctor-card ">
     <div class="doctor-avatar">
         <img src="<?=$doctor['avatar'] ?>" alt="<?= $doctor['full_name']; ?>">
     </div>
     <div class="doctor-name">Dr.<?= $doctor['full_name']; ?>
     </div>
     <div class="doctor-speciality">
         <?= isset($doctor['specialty_name']) ? ucfirst($doctor['specialty_name']) : 'No Specialty'; ?>
     </div>
     <div class="doctor-rating">
         <?php
                               include '../php/get-rating.php';
            ?>

     </div>
     <div class="doctor-info">
         <?php
                               include '../php/get-doctor-schedule.php';
                                ?>

         <p class="experience-item">
             <?php if ($experienceYears !== null): ?>
             <?= $experienceYears; ?> years experience
             <?php else: ?>
             Experience info coming soon
             <?php endif; ?>
         </p>
         <p class="location-item"><?= $doctor['location']; ?></p>
         <?php if (!empty($rating_row['total_votes'])): ?>
         <p class="rating-score">
             <span class="rating-value"><?= round($rating_row['avg_rating'], 1); ?></span>
             <span class="rating-max">/ 5</span>
             <span class="rating-votes">(<?= $rating_row['total_votes']; ?> rating)</span>
         </p>
         <?php else: ?>
         <p class="rating-score no-rating">No ratings yet</p>
         <?php endif; ?>
     </div>
     <div class="doctor-schedule">
         <h4>🕒 Working Days</h4>

         <?php if ($schedule_result->num_rows > 0): ?>

         <?php while($sch = $schedule_result->fetch_assoc()): ?>
         <div class="schedule-item <?= $sch['is_working_day'] == 1 ? 'is-working' : 'is-off'; ?>">
             <span class="schedule-day"><?= htmlspecialchars($sch['day_of_week']); ?></span>
             <?php if ($sch['is_working_day'] == 1): ?>
             <span class="schedule-time">
                 <span><?= substr($sch['work_start_time'], 0, 5); ?></span>
                 <span class="schedule-separator">to</span>
                 <span><?= substr($sch['break_start_time'], 0, 5); ?></span>
                 <span class="schedule-break">Break</span>
                 <span><?= substr($sch['break_end_time'], 0, 5); ?></span>
                 <span class="schedule-separator">to</span>
                 <span><?= substr($sch['work_end_time'], 0, 5); ?></span>
             </span>
             <?php else: ?>
             <span class="schedule-closed">Not working</span>
             <?php endif; ?>
         </div>
         <?php endwhile; ?>

         <?php else: ?>
         <p class="schedule-empty">No schedule found</p>
         <?php endif; ?>
     </div>

     <a onclick="checkLogin()" href=" appointment-slots.php?doctor_id=<?php echo $doctor['doctor_id'];?>"
         class="btn-book">
         📅 Book Appointment
     </a>
 </div>