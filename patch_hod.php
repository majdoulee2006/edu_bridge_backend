<?php
$hodFile = 'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php';
$content = file_get_contents($hodFile);

// Replace the single Tabs Navigation with Main Tabs and wrapped sub-tabs
$oldTabs = '    <!-- Tabs Navigation -->
    <div class="custom-tabs">
        <button class="tab-btn active" onclick="switchTab(\'mercy\')">
            <i class="fa-solid fa-gavel"></i> طلبات الاسترحام
        </button>
        <button class="tab-btn" onclick="switchTab(\'documents\')">
            <i class="fa-solid fa-file-invoice"></i> طلبات الوثائق
        </button>
        <button class="tab-btn" onclick="switchTab(\'makeup\')">
            <i class="fa-solid fa-pen-to-square"></i> امتحانات الإكمال
        </button>
    </div>';

$newTabs = '    <!-- Main Tabs Navigation -->
    <div class="custom-tabs" style="border-bottom: 3px solid var(--accent-color); margin-bottom: 2rem;">
        <button class="tab-btn active" onclick="switchMainTab(this, \'pending\')" style="font-size: 1.2rem; border-radius: 12px 12px 0 0;">
            <i class="fa-regular fa-clock"></i> طلبات معلقة
        </button>
        <button class="tab-btn" onclick="switchMainTab(this, \'completed\')" style="font-size: 1.2rem; border-radius: 12px 12px 0 0;">
            <i class="fa-solid fa-check-double"></i> طلبات منتهية
        </button>
    </div>

    @foreach([\'pending\', \'completed\'] as $statusGrp)
    <div id="main-tab-{{ $statusGrp }}" class="main-tab-content" style="display: {{ $loop->first ? \'block\' : \'none\' }}; animation: fadeIn 0.3s ease;">
        
        <!-- Sub Tabs Navigation -->
        <div class="custom-tabs">
            <button class="tab-btn active" onclick="switchSubTab(this, \'{{ $statusGrp }}-mercy\', \'{{ $statusGrp }}\')">
                <i class="fa-solid fa-gavel"></i> طلبات الاسترحام
            </button>
            <button class="tab-btn" onclick="switchSubTab(this, \'{{ $statusGrp }}-documents\', \'{{ $statusGrp }}\')">
                <i class="fa-solid fa-file-invoice"></i> طلبات الوثائق
            </button>
            <button class="tab-btn" onclick="switchSubTab(this, \'{{ $statusGrp }}-makeup\', \'{{ $statusGrp }}\')">
                <i class="fa-solid fa-pen-to-square"></i> امتحانات الإكمال
            </button>
        </div>';

$content = str_replace($oldTabs, $newTabs, $content);

// Wrap tab contents
$content = str_replace('<div id="tab-mercy" class="tab-content active">', '<div id="tab-{{ $statusGrp }}-mercy" class="sub-tab-content-{{ $statusGrp }}" style="display: {{ $loop->first ? \'block\' : \'none\' }}; animation: fadeIn 0.3s ease;">', $content);
$content = str_replace('<div id="tab-documents" class="tab-content">', '<div id="tab-{{ $statusGrp }}-documents" class="sub-tab-content-{{ $statusGrp }}" style="display: none; animation: fadeIn 0.3s ease;">', $content);
$content = str_replace('<div id="tab-makeup" class="tab-content">', '<div id="tab-{{ $statusGrp }}-makeup" class="sub-tab-content-{{ $statusGrp }}" style="display: none; animation: fadeIn 0.3s ease;">', $content);

// Now we need to update the queries in foreach to use filtering based on statusGrp
$content = str_replace('@foreach($requests->where(\'type\', \'mercy\') as $req)', '@php $filtered = $requests->where(\'type\', \'mercy\')->filter(function($r) use ($statusGrp) { return $statusGrp == \'pending\' ? ($r->status == \'pending_hod\') : ($r->status != \'pending_hod\'); }); @endphp
                    @foreach($filtered as $req)', $content);
$content = str_replace('@foreach($requests->where(\'type\', \'document\') as $req)', '@php $filtered = $requests->where(\'type\', \'document\')->filter(function($r) use ($statusGrp) { return $statusGrp == \'pending\' ? ($r->status == \'pending_hod\') : ($r->status != \'pending_hod\'); }); @endphp
                    @foreach($filtered as $req)', $content);
$content = str_replace('@foreach($requests->where(\'type\', \'makeup\') as $req)', '@php $filtered = $requests->where(\'type\', \'makeup\')->filter(function($r) use ($statusGrp) { return $statusGrp == \'pending\' ? ($r->status == \'pending_hod\') : ($r->status != \'pending_hod\'); }); @endphp
                    @foreach($filtered as $req)', $content);

// Update empty checks
$content = str_replace('@if($requests->where(\'type\', \'mercy\')->isEmpty())', '@if($filtered->isEmpty())', $content);
$content = str_replace('@if($requests->where(\'type\', \'document\')->isEmpty())', '@if($filtered->isEmpty())', $content);
$content = str_replace('@if($requests->where(\'type\', \'makeup\')->isEmpty())', '@if($filtered->isEmpty())', $content);

// Update Status Badge logic for HOD
$statusLogicOld = '<td><span class="badge badge-admin-review">بانتظار الإدارة</span></td>';
$statusLogicNew = '<td>
                            @if($req->status == \'pending_hod\')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == \'pending_admin\')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif
                        </td>';
$content = str_replace($statusLogicOld, $statusLogicNew, $content);

// Close the foreach at the end of makeup tab content div
$content = str_replace('<!-- Request Details Modal -->', '    </div>
    @endforeach
<!-- Request Details Modal -->', $content);

// Update button calls
$btnOld = '<button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal(';
$btnNew = '@php $canRespond = ($req->status == \'pending_hod\'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal(';
$content = str_replace($btnOld, $btnNew, $content);

$callOld = '`{{ addslashes($req->details) }}`, `لا توجد ملاحظات من الشؤون`)';
$callNew = '`{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? "لا توجد ملاحظات") }}`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})';
$content = str_replace($callOld, $callNew, $content);

// Update Modal Form action
$modalFormOld = '<form action="{{ route(\'hod.student_services.process\', 1) }}" method="POST">';
$modalFormNew = '<form id="decisionForm" method="POST" action="">';
$content = str_replace($modalFormOld, $modalFormNew, $content);

// Add the JS functions
$jsOld = '    function switchTab(tabName) {
        document.querySelectorAll(\'.tab-btn\').forEach(btn => btn.classList.remove(\'active\'));
        document.querySelectorAll(\'.tab-content\').forEach(content => content.classList.remove(\'active\'));
        
        event.currentTarget.classList.add(\'active\');
        document.getElementById(\'tab-\' + tabName).classList.add(\'active\');
    }';

$jsNew = '    function switchMainTab(btnElement, statusGrp) {
        btnElement.parentElement.querySelectorAll(\'.tab-btn\').forEach(btn => btn.classList.remove(\'active\'));
        btnElement.classList.add(\'active\');
        
        document.querySelectorAll(\'.main-tab-content\').forEach(content => {
            content.style.display = \'none\';
        });
        const targetMainTab = document.getElementById(\'main-tab-\' + statusGrp);
        if (targetMainTab) {
            targetMainTab.style.display = \'block\';
        }
    }

    function switchSubTab(btnElement, tabId, statusGrp) {
        btnElement.parentElement.querySelectorAll(\'.tab-btn\').forEach(btn => btn.classList.remove(\'active\'));
        btnElement.classList.add(\'active\');
        
        document.querySelectorAll(\'.sub-tab-content-\' + statusGrp).forEach(content => {
            content.style.display = \'none\';
        });
        const targetTab = document.getElementById(\'tab-\' + tabId);
        if (targetTab) {
            targetTab.style.display = \'block\';
        }
    }';

$content = str_replace($jsOld, $jsNew, $content);

$jsModalOld = 'function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes) {';
$jsModalNew = 'function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond) {';
$content = str_replace($jsModalOld, $jsModalNew, $content);

$jsModalLogicOld = 'const modal = document.getElementById(\'requestModal\');
        modal.style.display = \'flex\';';
$jsModalLogicNew = 'document.getElementById(\'decisionForm\').action = \'/hod/student-services/\' + reqId + \'/process\';
        
        const notesElement = document.getElementById(\'modal-hod-notes\');
        notesElement.style.borderColor = \'\';
        
        const footer = document.querySelector(\'.modal-footer\');
        if (canRespond) {
            notesElement.value = \'\';
            notesElement.readOnly = false;
            footer.style.display = \'flex\';
        } else {
            notesElement.value = \'تم إبداء رأيك مسبقاً، لا يمكن التعديل.\';
            notesElement.readOnly = true;
            footer.style.display = \'none\';
        }
        
        const modal = document.getElementById(\'requestModal\');
        modal.style.display = \'flex\';';
$content = str_replace($jsModalLogicOld, $jsModalLogicNew, $content);

file_put_contents($hodFile, $content);
echo "HOD blade perfectly restored and patched.\n";

?>
