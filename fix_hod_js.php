<?php
$hodFile = 'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php';
$content = file_get_contents($hodFile);

$content = str_replace("document.getElementById('modal-hod-notes-readonly').innerText = hodNotes;", "", $content);
$content = str_replace("document.getElementById('modal-affairs-notes-readonly-hod-not-used').innerText = hodNotes;", "", $content);

file_put_contents($hodFile, $content);
echo "Fixed JS error in HOD.\n";
?>
