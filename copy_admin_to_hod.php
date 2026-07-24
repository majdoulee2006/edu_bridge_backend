<?php
$adminFile = 'd:/Graduation project/edu_bridge_backend/resources/views/admin/student-services.blade.php';
$hodFile = 'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php';

$content = file_get_contents($adminFile);

// 1. Layout Extension
$content = str_replace("@extends('layouts.admin')", "@extends('layouts.hod')", $content);

// 2. Status Filtering
$content = str_replace("pending_admin", "pending_hod", $content);

// 3. Status Badges Logic
// Admin has:
// @if($req->status == 'pending_hod')
//     <span class="badge badge-pending">بانتظار قرارك</span>
// @else
// Wait, admin actually had:
// @if($req->status == 'pending_hod') <span class="badge badge-pending">بانتظار قرارك</span>
// No, the original admin had `pending_admin`. Let's just fix the badges manually for HOD.

$badgeOld = '                            @if($req->status == \'pending_hod\')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif';
$badgeNew = '                            @if($req->status == \'pending_hod\')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == \'pending_admin\')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif';
$content = str_replace($badgeOld, $badgeNew, $content);

// 4. Modal Action URL
$content = str_replace("/admin/student-services/", "/hod/student-services/", $content);

// 5. Modal Notes Field IDs and Names
// For HOD, they should edit `hod_notes` instead of `admin_notes`
$content = str_replace('id="modal-admin-notes"', 'id="modal-hod-notes"', $content);
$content = str_replace('name="admin_notes"', 'name="notes"', $content); // In controller it expects 'notes'
$content = str_replace('id="modal-hod-notes-readonly"', 'id="modal-affairs-notes-readonly-hod-not-used"', $content); // The readonly one

// Update the modal JS to target the right element
$content = str_replace("const notesElement = document.getElementById('modal-admin-notes');", "const notesElement = document.getElementById('modal-hod-notes');", $content);

file_put_contents($hodFile, $content);
echo "HOD copied from Admin successfully.\n";

?>
