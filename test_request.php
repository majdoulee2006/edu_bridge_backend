<?php
$request = \App\Models\StudentRequest::create([
    'student_id' => 7, // assuming 7 is a valid student
    'type' => 'mercy',
    'details' => 'Test pending_affairs',
    'status' => 'pending_affairs'
]);
echo "Created request id: " . $request->id . "\n";
