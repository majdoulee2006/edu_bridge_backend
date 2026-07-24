<?php
$dir = new RecursiveDirectoryIterator('d:/Graduation project/Edu_Pridge_flutter/lib/screens');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.dart')) {
        $content = file_get_contents($file->getPathname());
        
        // Fix 1: change margin: const EdgeInsets.only(bottom: 4), to zero
        $content = str_replace(
            "margin: const EdgeInsets.only(bottom: 4),", 
            "margin: EdgeInsets.zero,", 
            $content
        );
        
        // Fix 2: change const SizedBox(height: 5) to const SizedBox(height: 1)
        $content = str_replace(
            "const SizedBox(height: 5),", 
            "const SizedBox(height: 1),", 
            $content
        );
        
        // Fix 3: change padding: const EdgeInsets.all(6) to padding: const EdgeInsets.all(4) for the unread circle to reduce height
        $content = preg_replace(
            "/\bdecoration:\s*const\s*BoxDecoration\(color:\s*Color\(0xFFFFCC00\),\s*shape:\s*BoxShape\.circle\)/",
            "decoration: const BoxDecoration(color: Color(0xFFFFCC00), shape: BoxShape.circle)",
            $content
        );
        
        $content = str_replace(
            "padding: const EdgeInsets.all(6),\n                decoration: const BoxDecoration(color: Color(0xFFFFCC00), shape: BoxShape.circle)", 
            "padding: const EdgeInsets.all(4),\n                decoration: const BoxDecoration(color: Color(0xFFFFCC00), shape: BoxShape.circle)", 
            $content
        );

        file_put_contents($file->getPathname(), $content);
        echo "Patched spacing in: " . $file->getFilename() . "\n";
    }
}
?>
