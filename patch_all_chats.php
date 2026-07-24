<?php
$dir = new RecursiveDirectoryIterator('d:/Graduation project/Edu_Pridge_flutter/lib/screens');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'messages_screen.dart') || str_ends_with($file->getFilename(), 'boss_massega.dart') || str_ends_with($file->getFilename(), 'parents_messages_screen.dart')) {
        $content = file_get_contents($file->getPathname());
        
        $oldLogic1 = 'final bool isRead = contact[\'is_read\'] ?? true;';
        $newLogic1 = 'final bool isRead = contact[\'is_read\'] == true;' . "\n" . '      final bool isMyMessage = contact[\'is_my_message\'] == true;';
        if (strpos($content, $oldLogic1) !== false) {
            $content = str_replace($oldLogic1, $newLogic1, $content);
        }

        $oldUI1 = 'if (isRead && !hasUnread) 
                const Padding(
                  padding: EdgeInsets.only(left: 4), 
                  child: Icon(Icons.done_all, color: Colors.blue, size: 16)
                ),';
        $newUI1 = 'if (isMyMessage) 
                Padding(
                  padding: const EdgeInsets.only(left: 4), 
                  child: Icon(Icons.done_all, color: isRead ? Colors.blue : Colors.grey, size: 16)
                ),';
        if (strpos($content, $oldUI1) !== false) {
            $content = str_replace($oldUI1, $newUI1, $content);
        }
        
        // Sometimes it is written without !hasUnread or differently in other screens
        $oldUI2 = 'if (chat.isRead) const Padding(padding: EdgeInsets.only(left: 4), child: Icon(Icons.done_all, color: Colors.blue, size: 16)),';
        $newUI2 = 'if (chat.isMyMessage) Padding(padding: const EdgeInsets.only(left: 4), child: Icon(Icons.done_all, color: chat.isRead ? Colors.blue : Colors.grey, size: 16)),';
        
        // Wait, if it uses chat.isRead, it means it's using an object. But getContacts returns a Map. 
        // We'll just leave other files for now if they don't match EXACTLY, since the user only complained about the Head of Department screen. But let's apply where it matches.
        
        file_put_contents($file->getPathname(), $content);
    }
}
echo "Patched other screens if they matched the same logic.\n";
?>
