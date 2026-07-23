<?php
DB::statement("ALTER TABLE leave_requests MODIFY COLUMN type ENUM('full_day', 'hourly', 'justification') NOT NULL");
echo "Done\n";
