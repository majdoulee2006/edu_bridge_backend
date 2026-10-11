<?php

if (!function_exists('storageUrl')) {
    function storageUrl(?string $path): ?string {
        if (!$path) return null;
        $cleanPath = ltrim($path, '/');
        // إزالة بادئة storage/ إذا كانت موجودة لمنع التكرار
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }
        $encoded = implode('/', array_map('rawurlencode', explode('/', $cleanPath)));
        
        if (app()->bound('request') && request()->getHttpHost()) {
            return request()->getSchemeAndHttpHost() . '/api/file/' . $encoded;
        }
        return url('/api/file/' . $encoded);
    }
}

if (!function_exists('localizeNotificationCategory')) {
    function localizeNotificationCategory(?string $category): ?string {
        if (!$category) return null;
        $categoryMap = [
            'administrative' => __('messages.notif_cat_administrative'),
            'academic'       => __('messages.notif_cat_academic'),
            'general'        => __('messages.notif_cat_general'),
            'announcement'   => __('messages.notif_cat_announcement'),
            'attendance'     => __('messages.notif_cat_attendance'),
            'grade'          => __('messages.notif_cat_grade'),
            'assignment'     => __('messages.notif_cat_assignment'),
            'exam'           => __('messages.notif_cat_exam'),
            'leave'          => __('messages.notif_cat_leave'),
            'service'        => __('messages.notif_cat_service'),
            'message'        => __('messages.notif_cat_message'),
        ];
        $key = strtolower(trim($category));
        if (isset($categoryMap[$key])) {
            return $categoryMap[$key];
        }
        return app()->getLocale() === 'en' ? ucfirst($category) : $category;
    }
}

if (!function_exists('localizeNotificationTitle')) {
    function localizeNotificationTitle(?string $title): string {
        if (!$title) return '';
        if (app()->getLocale() !== 'en') return $title;

        $t = trim($title);

        // Strip/normalize emojis for pattern matching
        if (str_contains($t, 'تحديث الفصل الدراسي')) {
            return '🗓️ Semester Update';
        }
        if (str_contains($t, 'تهانينا! تم ترفيعك الأكاديمي') || str_contains($t, 'تم ترفيعك الأكاديمي')) {
            return '🎉 Congratulations! Academic Promotion';
        }
        if (str_contains($t, 'مبروك! تم الترفيع إلى الفصل الثاني') || str_contains($t, 'تم الترفيع إلى الفصل الثاني')) {
            return '📚 Congratulations! Promoted to Second Semester';
        }
        if (str_contains($t, 'تنبيه أكاديمي - إعادة السنة') || str_contains($t, 'إعادة السنة')) {
            return '⚠️ Academic Warning - Year Repetition';
        }
        if (str_contains($t, 'محاولة تسجيل دخول لحسابك') || str_contains($t, 'تسجيل دخول لحسابك')) {
            return '⚠️ Security Alert - New Login Attempt';
        }
        if (str_contains($t, 'تعميم رسمي من إدارة المعهد العليا') || str_contains($t, 'تعميم رسمي')) {
            return '📢 Official Circular from Higher Administration';
        }
        if (str_contains($t, 'إعلان جديد من الإدارة') || str_contains($t, 'اعلان جديد من الادارة')) {
            return 'New Announcement from Administration';
        }
        if (str_contains($t, 'تم تصحيح واجبك') || str_contains($t, 'تم تصحيح واجب') || str_contains($t, 'تصحيح واجب')) {
            return 'Assignment Graded';
        }
        if (str_contains($t, 'تسليم واجب جديد') || str_contains($t, 'تسليم واجب')) {
            return 'New Assignment Submission';
        }
        if (str_contains($t, 'واجب جديد')) {
            return 'New Assignment Posted';
        }
        if (str_contains($t, 'تحديث من الشؤون الطلابية على طلبك')) {
            return 'Update on Your Request from Student Affairs';
        }
        if (str_contains($t, 'طلب خدمة محول من الشؤون')) {
            return 'Service Request Transferred from Affairs';
        }
        if (str_contains($t, 'تحديث من رئيس القسم على طلبك')) {
            return 'Update on Your Request from Department Head';
        }
        if (str_contains($t, 'طلب خدمة محول للإدارة')) {
            return 'Service Request Transferred to Administration';
        }
        if (str_contains($t, 'طلب خدمة جديد (طلب امتحان إكمال)')) {
            return 'New Student Service (Makeup Exam Request)';
        }
        if (str_contains($t, 'طلب خدمة جديد')) {
            return 'New Student Service Request';
        }
        if (str_contains($t, 'طلب إجازة يحتاج موافقتك') || str_contains($t, 'طلب اجازة يحتاج موافقتك')) {
            return 'Leave Request Awaiting Your Approval';
        }
        if (str_contains($t, 'طلب إجازة بانتظار موافقتك') || str_contains($t, 'طلب اجازة بانتظار موافقتك')) {
            return 'Leave Request Pending Your Approval';
        }
        if (str_contains($t, 'تم رفض طلب الإجازة') || str_contains($t, 'رفض طلب الاجازة')) {
            return 'Leave Request Rejected';
        }
        if (str_contains($t, 'تمت الموافقة النهائية على طلب الإذن') || str_contains($t, 'الموافقة النهائية على طلب الإذن')) {
            return 'Leave Request Finally Approved ✓';
        }
        if (str_contains($t, 'استدعاء ولي أمر رسمي') || str_contains($t, 'استدعاء ولي امر رسمي')) {
            return 'Official Parent Summons';
        }
        if (str_contains($t, 'استدعاء ولي أمر') || str_contains($t, 'استدعاء ولي امر')) {
            return 'Parent Summons Notice';
        }
        if (str_contains($t, 'عذر غياب جديد بحاجة للمراجعة') || str_contains($t, 'عذر غياب جديد')) {
            return 'Absence Excuse Pending Review';
        }
        if (str_contains($t, 'طلب إجازة جديد من المعلم')) {
            return 'New Leave Request from Teacher';
        }
        if (str_contains($t, 'طلب تسجيل جديد')) {
            return 'New Registration Request (Pending Approval)';
        }
        if (str_contains($t, 'رسالة جديدة')) {
            return 'New Message';
        }
        if (str_contains($t, 'تقرير سلوكي جديد') || str_contains($t, 'تقرير سلوكي')) {
            return 'New Behavioral Report';
        }
        if (str_contains($t, 'تقرير أكاديمي جديد') || str_contains($t, 'تقرير أكاديمي')) {
            return 'New Academic Report';
        }
        if (str_contains($t, 'تقرير جاهز')) {
            return 'Report Ready';
        }
        if (str_contains($t, 'نتيجة') || str_contains($t, 'علامة')) {
            if (preg_match('/نتيجة\s*(.*)/u', $t, $m)) {
                $type = trim($m[1]);
                $typeMap = ['امتحان' => 'Exam', 'مذاكرة' => 'Quiz', 'شفهي' => 'Oral Test', 'تقييم' => 'Assessment'];
                return 'Assessment Result: ' . ($typeMap[$type] ?? $type);
            }
            return 'Grade & Assessment Result';
        }
        if (str_contains($t, 'محاضرة جديدة')) {
            return 'New Lecture Uploaded';
        }
        if (str_contains($t, 'اختبار جديد')) {
            return 'New Quiz / Test';
        }
        if (str_contains($t, 'طلب موعد')) {
            return 'Appointment Request';
        }

        return $title;
    }
}

if (!function_exists('localizeNotificationBody')) {
    function localizeNotificationBody(?string $body): string {
        if ($body === null || $body === '') return '';
        if (app()->getLocale() !== 'en') return $body;

        $b = trim($body);

        // Grade event patterns: "علامتك في امتحان «نصفي» - برمجة 1: 18 / 20"
        if (preg_match('/علامتك في\s*(.*?)\s*[«"״\'](.*?)[»"״\']\s*-\s*(.*?):\s*([0-9.]+)\s*\/\s*([0-9.]+)/u', $b, $matches)) {
            $type = trim($matches[1]);
            $typeMap = ['امتحان' => 'Exam', 'مذاكرة' => 'Quiz', 'شفهي' => 'Oral', 'تقييم' => 'Assessment'];
            $type = $typeMap[$type] ?? $type;
            return "Your score in $type \"{$matches[2]}\" - {$matches[3]}: {$matches[4]} / {$matches[5]}";
        }
        if (preg_match('/علامة\s*(.*?)\s*في\s*(.*?)\s*[«"״\'](.*?)[»"״\']\s*-\s*(.*?):\s*([0-9.]+)\s*\/\s*([0-9.]+)/u', $b, $matches)) {
            $type = trim($matches[2]);
            $typeMap = ['امتحان' => 'Exam', 'مذاكرة' => 'Quiz', 'شفهي' => 'Oral', 'تقييم' => 'Assessment'];
            $type = $typeMap[$type] ?? $type;
            return "{$matches[1]}'s score in $type \"{$matches[3]}\" - {$matches[4]}: {$matches[5]} / {$matches[6]}";
        }
        if (preg_match('/تم إضافة تقرير سلوكي جديد عن ابنك\/ابنتك\s*(.*?)\s*من قبل مربي الدورة/u', $b, $matches)) {
            return 'A new behavioral report was added for your child ' . trim($matches[1]) . ' by the class advisor.';
        }
        if (preg_match('/تم إرسال تقرير\s*(.*?)\s*عن ابنك\/ابنتك\s*(.*)/u', $b, $matches)) {
            $repType = trim($matches[1]) === 'سلوكي' ? 'behavioral' : 'academic';
            return 'A ' . $repType . ' report has been submitted for your child ' . trim($matches[2]);
        }
        if (preg_match('/تم رفع التقرير الخاص بالطالب\s*(.*?)\s*بواسطة\s*(.*)/u', $b, $matches)) {
            return 'The report for student ' . trim($matches[1]) . ' was submitted by ' . trim($matches[2]);
        }
        if (preg_match('/تم رفع تقرير عن الطالب\s*(.*?)\s*بواسطة\s*(.*)/u', $b, $matches)) {
            return 'A report for student ' . trim($matches[1]) . ' was submitted by ' . trim($matches[2]);
        }

        // Exact & regex translations
        if (preg_match('/تم تفعيل الفصل الدراسي الأول رسمياً بالمعهد/u', $b)) {
            return 'The First Semester has been officially activated at the institute. We wish you great success!';
        }
        if (preg_match('/تم تفعيل الفصل الدراسي الثاني رسمياً بالمعهد/u', $b)) {
            return 'The Second Semester has been officially activated at the institute. We wish you great success!';
        }
        if (preg_match('/تم ترفيعك بنجاح إلى السنة الثانية/u', $b)) {
            return 'You have been successfully promoted to the Second Year. We wish you continued excellence!';
        }
        if (preg_match('/تم ترفيعك بنجاح إلى السنة الثالثة/u', $b)) {
            return 'You have been successfully promoted to the Third Year. We wish you continued excellence!';
        }
        if (preg_match('/تم ترفيعك بنجاح إلى السنة الرابعة/u', $b)) {
            return 'You have been successfully promoted to the Fourth Year. We wish you continued excellence!';
        }
        if (preg_match('/تم ترفيعك بنجاح إلى السنة الخامسة/u', $b)) {
            return 'You have been successfully promoted to the Fifth Year. We wish you continued excellence!';
        }
        if (preg_match('/قررت شؤون الطلاب ترفيعك بنجاح إلى الفصل الدراسي الثاني/u', $b)) {
            return 'Student Affairs has approved your promotion to the Second Semester after completing First Semester requirements.';
        }
        if (preg_match('/تم تثبيت حالة إعادة السنة الدراسية/u', $b)) {
            return 'Year repetition status has been confirmed based on academic results and GPA.';
        }
        if (preg_match('/حاول حد يسجل دخول لحسابك من متصفح أو جهاز آخر/u', $b)) {
            return 'A login attempt was detected on your account from another device/browser while your session is active. If this wasn\'t you, please change your password immediately.';
        }
        if (preg_match('/حاول حد يسجل دخول لحسابك من جهاز موبايل آخر/u', $b)) {
            return 'A login attempt was detected on your account from another mobile device while your session is active. If this wasn\'t you, please change your password immediately.';
        }
        if (preg_match('/صح.*واجب/u', $b)) {
            $taskName = 'Assignment';
            if (preg_match('/واجب\s*["״\'“”]([^"״\'“”]+)["״\'“”]/u', $b, $tm)) {
                $taskName = trim($tm[1]);
            } elseif (preg_match('/واجب\s+([^\s—–-]+)/u', $b, $tm)) {
                $taskName = trim($tm[1]);
            }
            $taskMap = [
                'حل تمارين الفصل الاول' => 'Chapter 1 Exercises Solution',
                'حل تمارين الفصل الأول' => 'Chapter 1 Exercises Solution',
            ];
            $taskName = $taskMap[$taskName] ?? $taskName;

            $gradeVal = '';
            if (preg_match('/(?:علامة|علامتك)[:\s]+([^\n\r]+)/u', $b, $gm)) {
                $gradeVal = trim($gm[1]);
            }
            return 'Teacher graded assignment "' . $taskName . '"' . ($gradeVal ? ' — Your grade: ' . $gradeVal : '');
        }
        if (preg_match('/رفع المعلم (.*?) واجباً جديداً:\s*(.*)/u', $b, $matches)) {
            return 'Teacher ' . trim($matches[1]) . ' posted a new assignment: ' . trim($matches[2]);
        }
        if (preg_match('/(قام الطالب|سلّم الطالب)\s*(.*?)\s*(بتسليم واجب|الواجب):\s*(.*)/u', $b, $matches)) {
            return 'Student ' . trim($matches[2]) . ' submitted assignment: ' . trim($matches[4]);
        }
        if (preg_match('/لقد تلقيت رسالة جديدة من (.*)/u', $b, $matches)) {
            return 'You received a new message from ' . trim($matches[1]);
        }
        if (preg_match('/قامت الشؤون الطلابية بإبداء .*? على طلبك \(#(\d+)\)/u', $b, $matches)) {
            return 'Student Affairs added initial approval and notes on your request (#' . $matches[1] . '), forwarded to Department Head.';
        }
        if (preg_match('/تمت مراجعة طلب الطالب (.*?) من قبل الشؤون وهو بانتظار موافقتك/u', $b, $matches)) {
            return 'Student ' . trim($matches[1]) . '\'s request was reviewed by Affairs and is awaiting your review as Department Head.';
        }
        if (preg_match('/قام رئيس القسم بإبداء .*? على طلبك \(#(\d+)\)/u', $b, $matches)) {
            return 'Department Head approved your request (#' . $matches[1] . ') and forwarded it to Administration for final decision.';
        }
        if (preg_match('/تمت مراجعة طلب الطالب (.*?) من قبل رئيس القسم وهو بانتظار قرار الإدارة/u', $b, $matches)) {
            return 'Student ' . trim($matches[1]) . '\'s request was reviewed by Department Head and is awaiting final Administration decision.';
        }
        if (preg_match('/قام الطالب (.*?) بتقديم طلب امتحان إكمال جديد/u', $b, $matches)) {
            return 'Student ' . trim($matches[1]) . ' submitted a new makeup exam request awaiting your review.';
        }
        if (preg_match('/قدّم (.*?) طلب إجازة بتاريخ (.*?)،/u', $b, $matches)) {
            return trim($matches[1]) . ' submitted a leave request for date ' . trim($matches[2]) . ', please review and respond.';
        }
        if (preg_match('/وافق ولي أمر الطالب (.*?) على طلب إجازة بتاريخ (.*?)،/u', $b, $matches)) {
            return 'Parent of student ' . trim($matches[1]) . ' approved leave request for date ' . trim($matches[2]) . ', please review.';
        }
        if (preg_match('/تم رفض طلب إجازتك اليومية بتاريخ (.*?) من قِبل ولي الأمر/u', $b, $matches)) {
            return 'Your daily leave request for date ' . trim($matches[1]) . ' was rejected by parent.';
        }
        if (preg_match('/تمت موافقة رئيس القسم على طلب إجازة للطالب (.*?) بتاريخ (.*?)،/u', $b, $matches)) {
            return 'Department Head approved student ' . trim($matches[1]) . '\'s leave request for ' . trim($matches[2]) . ', please review.';
        }
        if (preg_match('/قام الأستاذ \((.*?)\) بتقديم طلب إجازة بتاريخ \((.*?)\) عبر التيليغرام/u', $b, $matches)) {
            return 'Teacher (' . trim($matches[1]) . ') submitted a leave request for (' . trim($matches[2]) . ') via Telegram - Reason: [Hourly leave: 10:00 - 12:00] - Emergency';
        }
        if (preg_match('/لديك استدعاء رسمي من إدارة المعهد بخصوص الطالب: \((.*?)\) - السبب: (.*)/u', $b, $matches)) {
            $reason = trim($matches[2]);
            $reasonMap = ['شغب' => 'Misconduct', 'الحضور' => 'Attendance', 'الغياب' => 'Absence'];
            $reason = $reasonMap[$reason] ?? $reason;
            return 'You have an official summons from Administration regarding student: (' . trim($matches[1]) . ') - Reason: ' . $reason;
        }
        if (preg_match('/تم إرسال طلب استدعاء رسمي لولي أمرك من قبل إدارة المعهد بخصوص: (.*)/u', $b, $matches)) {
            $reason = trim($matches[1]);
            $reasonMap = ['شغب' => 'Misconduct', 'الحضور' => 'Attendance', 'الغياب' => 'Absence'];
            $reason = $reasonMap[$reason] ?? $reason;
            return 'An official summons was sent to your parent by Administration regarding: ' . $reason;
        }
        if (preg_match('/تهانينا، تمت الموافقة على طلب إذنك بتاريخ (.*?) نهائياً/u', $b, $matches)) {
            return 'Congratulations! Your leave request for date ' . trim($matches[1]) . ' received final approval from Parent, Department Head, and Student Affairs!';
        }
        if (preg_match('/قام الطالب \((.*?)\) بتقديم عذر لغيابه في مادة \((.*?)\)/u', $b, $matches)) {
            $course = trim($matches[2]);
            $courseMap = ['خوارزميات' => 'Algorithms', 'برمجة 1' => 'Programming 1', 'قواعد بيانات' => 'Databases'];
            $course = $courseMap[$course] ?? $course;
            return 'Student (' . trim($matches[1]) . ') submitted an absence excuse for course (' . $course . '), please review.';
        }
        if (preg_match('/قدّم (.*?) طلب انضمام كـطالب/u', $b, $matches)) {
            return trim($matches[1]) . ' submitted a student registration request. Please review and activate.';
        }

        // Common simple announcement phrases
        $simpleDict = [
            'اعلان مهم'                    => 'Important Announcement',
            'إعلان مهم'                    => 'Important Announcement',
            'اعلان تجريبي اكتر من صورة'   => 'Demo Announcement with Multiple Images',
            'إعلان تجريبي أكثر من صورة'    => 'Demo Announcement with Multiple Images',
            'صفحات متراكمة'                => 'Accumulated Study Materials & Pages',
            '0'                            => '',
        ];

        if (isset($simpleDict[$b])) {
            return $simpleDict[$b];
        }

        return $body;
    }
}

