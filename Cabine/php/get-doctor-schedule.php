 <?php
                                $schedule_sql = "
                                    SELECT * FROM doctor_schedule
                                    WHERE doctor_id = $doctor_id
                                    ORDER BY FIELD(day_of_week,'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday')
                                ";

                                $schedule_result = $conn->query($schedule_sql);
                                ?>