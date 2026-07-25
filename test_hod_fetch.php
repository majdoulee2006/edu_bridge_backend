<?php
$hodDepartment = "?????? ???????"; // Just an example
$query = \App\Models\StudentRequest::with('student.user')
    ->whereIn('status', ['pending_hod', 'pending_admin', 'completed'])
    ->whereHas('student.user', function($q) use ($hodDepartment) {
        $q->where('department', $hodDepartment);
    });
echo "HOD API fetch count: " . $query->count() . "\n";
