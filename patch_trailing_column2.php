<?php
$dir = new RecursiveDirectoryIterator('d:/Graduation project/Edu_Pridge_flutter/lib/screens');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.dart')) {
        $content = file_get_contents($file->getPathname());
        
        $pattern = '/trailing:\s*Column\(\s*mainAxisAlignment:\s*MainAxisAlignment\.center,\s*crossAxisAlignment:\s*CrossAxisAlignment\.end,/m';
        $replacement = "trailing: Column(\n          mainAxisSize: MainAxisSize.min,\n          mainAxisAlignment: MainAxisAlignment.center,\n          crossAxisAlignment: CrossAxisAlignment.end,";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            file_put_contents($file->getPathname(), $content);
            echo "Patched: " . $file->getFilename() . "\n";
        }
    }
}
?>
