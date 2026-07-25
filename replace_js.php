<?php
$files = [
    'd:/Graduation project/edu_bridge_backend/resources/views/admin/student-services.blade.php',
    'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php'
];

$jsOld = '    function switchTab(btnElement, tabName) {
        document.querySelectorAll(\'.tab-btn\').forEach(btn => btn.classList.remove(\'active\'));
        document.querySelectorAll(\'.tab-content\').forEach(content => content.classList.remove(\'active\'));
        
        btnElement.classList.add(\'active\');
        const targetTab = document.getElementById(\'tab-\' + tabName);
        if (targetTab) {
            targetTab.classList.add(\'active\');
        }
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

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Find switchTab function manually using regex to be safe
    $pattern = '/\s*function switchTab\(.*?\}\s*\}/s';
    $content = preg_replace($pattern, "\n" . $jsNew, $content);
    file_put_contents($file, $content);
}
echo "JS replaced successfully.\n";
?>
