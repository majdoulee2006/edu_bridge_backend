<?php
$files = [
    'd:/Graduation project/edu_bridge_backend/resources/views/admin/student-services.blade.php',
    'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Fix the display of the mercy sub-tab so it is always block inside its container
    $oldStyle = 'class="sub-tab-content-{{ $statusGrp }}" style="display: {{ $loop->first ? \'block\' : \'none\' }};';
    $newStyle = 'class="sub-tab-content-{{ $statusGrp }}" style="display: block;';
    
    $content = str_replace($oldStyle, $newStyle, $content);
    
    file_put_contents($file, $content);
}
echo "Fixed initial display of sub-tabs.\n";
?>
