<?php
$dir = new RecursiveDirectoryIterator('d:/Graduation project/Edu_Pridge_flutter/lib/screens');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.dart')) {
        $content = file_get_contents($file->getPathname());
        
        // Add mainAxisSize: MainAxisSize.min, to trailing: Column(
        $search = "trailing: Column(\n          mainAxisAlignment: MainAxisAlignment.center,\n          crossAxisAlignment: CrossAxisAlignment.end,";
        $replace = "trailing: Column(\n          mainAxisSize: MainAxisSize.min,\n          mainAxisAlignment: MainAxisAlignment.center,\n          crossAxisAlignment: CrossAxisAlignment.end,";
        
        if (strpos($content, $search) !== false) {
            $content = str_replace($search, $replace, $content);
            file_put_contents($file->getPathname(), $content);
            echo "Patched: " . $file->getFilename() . "\n";
        }
    }
}
?>
