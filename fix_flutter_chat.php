<?php
// Fix chat_input_widget.dart
$chatInputFile = 'd:/Graduation project/Edu_Pridge_flutter/lib/widgets/chat/chat_input_widget.dart';
$chatInputContent = file_get_contents($chatInputFile);
$chatInputContent = str_replace("hintText: '???? ??????...',", "hintText: 'اكتب رسالة...',", $chatInputContent);
file_put_contents($chatInputFile, $chatInputContent);

// Fix chat_bubble_widget.dart
$chatBubbleFile = 'd:/Graduation project/Edu_Pridge_flutter/lib/widgets/chat/chat_bubble_widget.dart';
$chatBubbleContent = file_get_contents($chatBubbleFile);
$chatBubbleContent = str_replace(" // ????? ????????", "", $chatBubbleContent);
$chatBubbleContent = str_replace(" // ???? ?????? ???????? ?????? ????", "", $chatBubbleContent);
file_put_contents($chatBubbleFile, $chatBubbleContent);

// Fix boss_massega.dart
$bossMessageFile = 'd:/Graduation project/Edu_Pridge_flutter/lib/screens/Head of department/nav_bar/boss_massega.dart';
$bossMessageContent = file_get_contents($bossMessageFile);

$oldLogic = '      final bool isOnline = contact[\'is_online\'] ?? false;
      final bool isRead = contact[\'is_read\'] ?? true;';
$newLogic = '      final bool isOnline = contact[\'is_online\'] ?? false;
      final bool isRead = contact[\'is_read\'] == true;
      final bool isMyMessage = contact[\'is_my_message\'] == true;';

$bossMessageContent = str_replace($oldLogic, $newLogic, $bossMessageContent);

$oldUI = '              if (isRead && !hasUnread) 
                const Padding(
                  padding: EdgeInsets.only(left: 4), 
                  child: Icon(Icons.done_all, color: Colors.blue, size: 16)
                ),';
$newUI = '              if (isMyMessage) 
                Padding(
                  padding: const EdgeInsets.only(left: 4), 
                  child: Icon(Icons.done_all, color: isRead ? Colors.blue : Colors.grey, size: 16)
                ),';

$bossMessageContent = str_replace($oldUI, $newUI, $bossMessageContent);
file_put_contents($bossMessageFile, $bossMessageContent);

echo "Fixed encoding issues and read receipts.\n";
?>
