<?php
$hodFile = 'd:/Graduation project/edu_bridge_backend/resources/views/hod/student-services.blade.php';
$content = file_get_contents($hodFile);

// Remove the readonly HOD notes block since the HOD IS the one writing notes here
$hodReadonlyBlock = '<!-- ملاحظات رئيس القسم (Read-only) -->
                <div class="detail-row" style="background: #e0e7ff; padding: 1rem; border-radius: 8px; border-right: 4px solid #4f46e5;">
                    <label style="color: #3730a3; font-size: 1rem;"><i class="fa-solid fa-user-tie"></i> رأي رئيس القسم:</label>
                    <div class="detail-value" id="modal-affairs-notes-readonly-hod-not-used" style="color: #1e1b4b; font-size: 0.95rem; margin-top: 0.5rem; line-height: 1.6;"></div>
                </div>';
// It might be encoded differently or have spacing differences, let's use regex
$pattern = '/<!-- Ù…Ù„Ø§Ø­Ø¸Ø§Øª Ø±Ø¦ÙŠØ³ Ø§Ù„Ù‚Ø³Ù….*?<\/div>\s*<\/div>/s';
$content = preg_replace($pattern, '', $content);

// Change "قرار وملاحظات الإدارة" to "رأي وملاحظات رئيس القسم"
$content = str_replace('Ù‚Ø±Ø§Ø± ÙˆÙ…Ù„Ø§Ø­Ø¸Ø§Øª Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©', 'رأي وملاحظات رئيس القسم', $content);
$content = str_replace('Ø§ÙƒØªØ¨ Ù‚Ø±Ø§Ø± Ø§Ù„Ø¥Ø¯Ø§Ø±Ø© Ø§Ù„Ù†Ù‡Ø§Ø¦ÙŠ Ø£Ùˆ Ø£Ø³Ø¨Ø§Ø¨ Ø§Ù„Ø±Ù Ø¶/Ø§Ù„Ù‚Ø¨ÙˆÙ„ Ù„ÙŠØªÙ… Ø§Ø¹ØªÙ…Ø§Ø¯Ù‡ Ø±Ø³Ù…ÙŠØ§Ù‹ ÙˆØ¥Ø´Ø¹Ø§Ø± Ø§Ù„Ø·Ø§Ù„Ø¨ Ø¨Ù‡...', 'اكتب رأيك الأكاديمي وتوصيتك ليتم رفعها إلى الإدارة لاتخاذ القرار النهائي...', $content);

// Change button text
$content = str_replace('Ø§Ø¹ØªÙ…Ø§Ø¯ Ù†Ù‡Ø§Ø¦ÙŠ (Ù…ÙˆØ§Ù Ù‚Ø©)', 'رفع للإدارة (موافقة مبدئية)', $content);
$content = str_replace('Ø±Ù Ø¶ Ø§Ù„Ø·Ù„Ø¨', 'رفع للإدارة (رفض مبدئي)', $content);

// Fix the openRequestModal Javascript function name arguments:
$oldJSParams = "function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, hodNotes, reqId, canRespond)";
$newJSParams = "function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond)";
$content = str_replace($oldJSParams, $newJSParams, $content);

// Fix the HTML onclick arguments where it passes 10 arguments instead of 9 for HOD.
// In the PHP copy script, I already did this correctly?
// Wait, in my copy script I didn't change the HTML button string perfectly because I copied from Admin!
// Let's use a regex to fix the onclick arguments for HOD.
// Admin: openRequestModal('...', '...', '...', '...', '...', '...', `...`, `...`, `...`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})
// We want to remove the 9th argument (hodNotes) from the call in HOD.
$patternBtn = '/(openRequestModal\([^,]+,[^,]+,[^,]+,[^,]+,[^,]+,[^,]+,.*?,.*?), `\{\{\s*addslashes\(\$req->hod_notes[^}]+\}\}\`, (.*?\))/s';
$content = preg_replace($patternBtn, '$1, $2', $content);

file_put_contents($hodFile, $content);
echo "Cleaned up HOD labels.\n";

?>
