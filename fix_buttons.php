<?php
$adminFile = 'd:/Graduation project/edu_bridge_backend/resources/views/admin/student-services.blade.php';
$content = file_get_contents($adminFile);

$oldCall = '`{{ addslashes($req->details) }}`, {{ $req->id }})';
$newCall = '`{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? "لا توجد ملاحظات") }}`, `{{ addslashes($req->hod_notes ?? "لا توجد ملاحظات") }}`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})';

$content = str_replace($oldCall, $newCall, $content);

// Also we need to inject $canRespond before the button for admin
$btnDef = '<button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal(';
$btnNewDef = '@php $canRespond = ($req->status == \'pending_admin\'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal(';
$content = str_replace($btnDef, $btnNewDef, $content);

// And update the JS of openRequestModal to accept canRespond
$jsModalOld = 'function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, hodNotes, reqId) {';
$jsModalNew = 'function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, hodNotes, reqId, canRespond) {';
$content = str_replace($jsModalOld, $jsModalNew, $content);

$jsModalLogicOld = 'document.getElementById(\'decisionForm\').action = \'/admin/student-services/\' + reqId + \'/process\';';
$jsModalLogicNew = 'document.getElementById(\'decisionForm\').action = \'/admin/student-services/\' + reqId + \'/process\';
        
        const notesElement = document.getElementById(\'modal-admin-notes\');
        notesElement.style.borderColor = \'\';
        
        const footer = document.querySelector(\'.modal-footer\');
        if (canRespond) {
            notesElement.value = \'\';
            notesElement.readOnly = false;
            footer.style.display = \'flex\';
        } else {
            notesElement.value = \'تم إغلاق الطلب مسبقاً ولا يمكن تعديله.\';
            notesElement.readOnly = true;
            footer.style.display = \'none\';
        }';
$content = str_replace($jsModalLogicOld, $jsModalLogicNew, $content);

file_put_contents($adminFile, $content);

// Now for HOD
$hodFile = 'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php';
$contentH = file_get_contents($hodFile);

$oldCallHOD = '`{{ addslashes($req->details) }}`, {{ $req->id }})';
$newCallHOD = '`{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? "لا توجد ملاحظات") }}`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})';

$contentH = str_replace($oldCallHOD, $newCallHOD, $contentH);

$btnDefHOD = '<button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal(';
$btnNewDefHOD = '@php $canRespond = ($req->status == \'pending_hod\'); @endphp
                                            <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal(';
$contentH = str_replace($btnDefHOD, $btnNewDefHOD, $contentH);

$jsModalOldHOD = 'function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId) {';
$jsModalNewHOD = 'function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond) {';
$contentH = str_replace($jsModalOldHOD, $jsModalNewHOD, $contentH);

$jsModalLogicOldHOD = 'document.getElementById(\'decisionForm\').action = \'/hod/student-services/\' + reqId + \'/forward\';';
$jsModalLogicNewHOD = 'document.getElementById(\'decisionForm\').action = \'/hod/student-services/\' + reqId + \'/forward\';
        
        const notesElement = document.getElementById(\'modal-hod-notes\');
        notesElement.style.borderColor = \'\';
        
        const footer = document.querySelector(\'.modal-footer\');
        if (canRespond) {
            notesElement.value = \'\';
            notesElement.readOnly = false;
            footer.style.display = \'flex\';
        } else {
            notesElement.value = \'تم رفع الطلب للإدارة مسبقاً.\';
            notesElement.readOnly = true;
            footer.style.display = \'none\';
        }';
$contentH = str_replace($jsModalLogicOldHOD, $jsModalLogicNewHOD, $contentH);

file_put_contents($hodFile, $contentH);
echo "Buttons fixed.\n";
?>
