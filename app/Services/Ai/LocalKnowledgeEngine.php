<?php

namespace App\Services\Ai;

/**
 * محرك احتياطي بلا نموذج لغوي: مطابقة كلمات مفتاحية + بيانات المستخدم الحية.
 * يراعي الدور في كل رد ويحجب ما ليس من صلاحيات الدور.
 */
class LocalKnowledgeEngine
{
    public const DENIED = 'عذراً، هذه البيانات والإجراءات ليست من صلاحياتك للاطلاع عليها أو إدارتها، وليس من صلاحياتي إخبارك بها أو بتفاصيلها. يرجى مراجعة إدارة المعهد أو المعنيين بذلك.';

    /** كلمات تدل على إجراءات ليست من صلاحيات الدور */
    private const RESTRICTED = [
        'student' => ['رصد حضور', 'رصد درجات', 'رصد علامات', 'تعديل علامات', 'تعديل درجات', 'ترفيع', 'ترقية الطلاب', 'كادر', 'لوحة المعلم', 'لوحة الشؤون', 'لوحة المدير', 'بيانات الطلاب', 'حسابات الطلاب', 'حسابات المعلمين', 'حذف حساب', 'تفعيل الفصل'],
        'parent'  => ['رصد حضور', 'رصد درجات', 'رصد علامات', 'تعديل علامات', 'تعديل درجات', 'ترفيع', 'ترقية الطلاب', 'كادر', 'لوحة المعلم', 'لوحة الشؤون', 'لوحة المدير', 'حسابات الطلاب', 'حسابات المعلمين', 'حذف حساب', 'تفعيل الفصل', 'إعادة تعيين الجهاز مباشرة'],
        'teacher' => ['ترفيع', 'ترقية الطلاب', 'لوحة الشؤون', 'لوحة المدير', 'حذف حساب', 'تفعيل الفصل', 'تعديل علامات بعد الاعتماد', 'إعادة تعيين الجهاز مباشرة'],
    ];

    /**
     * @param  array  $data  ناتج AiContextBuilder::build
     */
    public function respond(string $message, string $role, array $data, string $baseHttp, array $history = []): string
    {
        $q    = mb_strtolower(trim($message), 'UTF-8');
        $role = AiRole::normalize($role);

        foreach (self::RESTRICTED[$role] ?? [] as $kw) {
            if (str_contains($q, $kw)) {
                return self::DENIED;
            }
        }

        if ($this->isGreeting($q)) {
            return "يا أهلاً وسهلاً بك! ❤️ أنا EduBridge AI، جاهز أساعدك بأي استفسار عن المنظومة" . $this->hintFor($role) . ' 😊';
        }

        if ($this->has($q, ['شو بتعرف', 'شو بتقدر', 'شو فيني', 'شو اسأل', 'شو أسأل', 'شو بسألك', 'ساعدني', 'مساعدة', 'اسئلة', 'أسئلة', 'شو بتساعد', 'شو بتعمل', 'help'])) {
            return $this->helpAnswer($role);
        }

        if ($this->has($q, ['مين انا', 'من انا', 'مين أنا', 'من أنا', 'شو اسمي', 'معلوماتي', 'بياناتي', 'حسابي'])) {
            return $this->whoAmIAnswer($role, $data);
        }

        if ($role === 'teacher' && $this->has($q, ['طلابي', 'مين الطلاب', 'من الطلاب', 'كم طالب', 'عدد الطلاب', 'اسماء الطلاب', 'أسماء الطلاب', 'بعطيهم', 'بدرسهم', 'بدرّسهم', 'الطلاب الي', 'الطلاب اللي', 'الطلاب الذين', 'قائمة الطلاب'])) {
            if ($r = $this->teacherStudentsAnswer($data)) {
                return $r;
            }
        }

        if ($role === 'hod' && !empty($data['department'])) {
            if ($r = $this->hodAnswer($q, $data)) {
                return $r;
            }
        }

        if ($role === 'teacher' && $this->has($q, ['مرشد', 'مربي', 'مربّي'])) {
            return empty($data['advisor'])
                ? '🧭 لست معيَّناً كمرشد (مربي) لدورة حالياً. التعيين من رئيس القسم.'
                : "🧭 أنت مرشد (مربي) دورة: **{$data['advisor']}**.";
        }

        if ($role === 'teacher' && !empty($data['teaching'])
            && $this->has($q, ['دورة', 'دورات', 'الدورات', 'سنة', 'سنين', 'سنوات', 'السنة', 'مواد', 'مقررات', 'مقرر', 'مادة', 'بعطي', 'بدرس', 'بدرّس'])
            && !$this->has($q, ['جدول', 'غياب', 'حضور', 'امتحان', 'علام', 'واجب', 'مين بيعطي', 'مين بيدرس'])) {
            return $this->teachingAnswer($q, $data['teaching']);
        }

        if ($this->has($q, ['مين بيعطي', 'مين بيدرس', 'مين يدرس', 'مين المدرس', 'مين الاستاذ', 'مين الأستاذ', 'مين المعلم', 'مدرسين', 'مدرسي', 'اساتذ', 'أساتذ', 'معلمين', 'معلمي', 'بيعطيه', 'بيدرسه', 'بيدرسني', 'بيعطيني', 'مين مدرس'])) {
            if ($r = $this->teachersAnswer($role, $data)) {
                return $r;
            }
        }

        if ($this->has($q, ['تسجيل دخول', 'تسجيل الدخول', 'رابط الدخول', 'رابط الويب', 'رابط تسجيل', 'بوابة الويب', 'بوابة الدخول', 'فوت عالويب', 'ادخل عالويب', 'موقع المعهد', 'رابط المنصة'])) {
            return $this->loginAnswer($role, $baseHttp);
        }

        // أسئلة تعتمد على بيانات المستخدم الحية أولاً
        if ($this->has($q, ['امتحان', 'اختبار', 'مذاكرة'])) {
            return $this->examsAnswer($role, $data);
        }
        if ($this->has($q, ['جدول', 'محاضراتي', 'محاضرات اليوم', 'كم محاضرة', 'عندي محاضر', 'شو عندي', 'ايمت عندي', 'إيمت عندي', 'جدولي'])) {
            if ($r = $this->scheduleAnswer($data)) {
                return $r;
            }
        }
        if ($this->has($q, ['غياب', 'حضور', 'انذار', 'إنذار', 'حرمان'])) {
            return $this->attendanceAnswer($role, $data);
        }
        if ($this->has($q, ['علام', 'درج', 'معدل', 'كشف', 'مسار'])) {
            return $this->gradesAnswer($role, $data);
        }
        if ($this->has($q, ['واجب', 'تكليف', 'تسليم'])) {
            return $this->assignmentsAnswer($role, $data);
        }
        if ($this->has($q, ['جهاز', 'عذر', 'شهادة', 'مصدقة', 'خدمات', 'طلب', 'إجازة', 'اجازة', 'إذن', 'اذن'])) {
            return $this->servicesAnswer($role, $data);
        }
        if ($this->has($q, ['استدعاء', 'موعد', 'مقابلة', 'لقاء'])) {
            return $this->appointmentsAnswer($role);
        }
        if ($this->has($q, ['محاضرة', 'ملخص', 'مقرر', 'مادة', 'مواد'])) {
            return $this->coursesAnswer($role, $data);
        }
        if ($this->has($q, ['حساب', 'كلمة سر', 'كلمة المرور', 'مستخدم'])) {
            return $this->accountsAnswer($role);
        }
        if ($role === 'parent' && $this->has($q, ['ابني', 'ابنتي', 'بنتي', 'ابنائي', 'أبنائي', 'اولادي', 'أولادي', 'مين ابن', 'ولدي', 'اولادك'])) {
            return $this->childrenAnswer($data);
        }
        if ($this->has($q, ['نصيح', 'ادرس', 'مذاكر', 'تنظيم'])) {
            return "💡 **نصائح للتفوق:**\n\n1. قسّم الدراسة لفترات 45 دقيقة تتبعها 10 دقائق راحة.\n2. أنجز الواجبات فور صدورها.\n3. حافظ على الحضور لتجنب الإنذارات والحرمان.\n4. راسل مدرّس المقرر عند أي استفسار.";
        }

        // متابعة قصيرة ("اي شو هنن"، "وبعدين"...) → نعيد معالجة آخر سؤال للمستخدم
        if (!empty($history) && $this->wordCount($q) <= 5) {
            foreach (array_reverse($history) as $h) {
                $prev = trim((string) ($h['text'] ?? ''));
                if (($h['role'] ?? '') === 'user' && $prev !== '' && mb_strtolower($prev, 'UTF-8') !== $q) {
                    return $this->respond($prev, $role, $data, $baseHttp, []);
                }
            }
        }

        return "شكراً لتواصلك مع **EduBridge AI**! 🌟\n\nأستطيع إرشادك في شاشات التطبيق وصفحات الويب الخاصة بدورك" . $this->hintFor($role) . ".\nاسألني مثلاً عن الجدول أو الغياب أو العلامات أو الخدمات.";
    }

    // ───────────────────────── الردود ─────────────────────────

    protected function scheduleAnswer(array $data): ?string
    {
        if (empty($data['schedule'])) {
            return null;
        }
        $n   = count($data['schedule']);
        $out = "📅 **جدولك الأسبوعي ({$n} محاضرة):**\n\n";
        foreach ($data['schedule'] as $i => $s) {
            $out .= ($i + 1) . ". **{$s['day']}**: {$s['course']} من {$s['start']} إلى {$s['end']}"
                . (!empty($s['room']) ? " ({$s['room']})" : '')
                . (!empty($s['teacher']) ? " - {$s['teacher']}" : '') . "\n";
        }
        $out .= "\n📱 يمكنك حفظ الجدول كصورة من الزر المركزي ← **'الجدول'** (للطالب).";

        return $out;
    }

    protected function attendanceAnswer(string $role, array $data): string
    {
        $policy = "\n\n📌 **نظام الإنذارات:** إنذار أول عند 7 أيام غياب غير معذور، وثانٍ مع استدعاء ولي الأمر عند 10، ونهائي وإحالة للإدارة عند 15. الغياب المعذور (عذر معتمد) لا يُحتسب.";

        if ($role === 'student' && isset($data['attendance']['absence_days']) && !empty($data['attendance']['courses'])) {
            $days = $data['attendance']['absence_days'];
            $out  = "📊 **غيابك:** **{$days}** يوم غياب غير معذور — " . $this->levelText($data['attendance']['level'], $days) . "\n\nالتفصيل حسب المقرر:\n";
            foreach ($data['attendance']['courses'] as $c) {
                $out .= "• **{$c['title']}**: {$c['absent']} غياب من {$c['total']} جلسة" . ($c['excused'] ? " ({$c['excused']} معذور)" : '') . "\n";
            }

            return $out . $policy . "\n\n📱 من الزر المركزي ← **'الحضور والغياب'** لتفاصيل الجلسات وتقديم عذر خلال 48 ساعة.";
        }

        if ($role === 'student') {
            return "لا توجد جلسات حضور مسجلة لك حتى الآن." . $policy;
        }

        if ($role === 'parent' && !empty($data['children'])) {
            $out = "📊 **غياب أبنائك (أيام غير معذورة):**\n\n";
            foreach ($data['children'] as $c) {
                $days = $c['attendance']['absence_days'] ?? 0;
                $out .= "• **{$c['name']}**: {$days} يوم — " . $this->levelText($c['attendance']['level'] ?? null, $days) . "\n";
            }

            return $out . $policy;
        }

        $where = match ($role) {
            'teacher' => "\n\n📱 الحضور: من الزر المركزي ← **'الحضور والغياب'**، وعلى الويب `/teacher/attendance` (تقارير PDF/Excel).",
            'hod'     => "\n\n💻 تابع الإنذارات والتقارير من لوحة `/hod/dashboard` و`/hod/reports`.",
            'affairs' => "\n\n💻 تتابع الشؤون الحالات والأعذار من لوحة `/affairs`.",
            'parent'  => "\n\n📱 من الزر المركزي ← **'الأذونات والإجازات'** لتقديم إذن غياب.",
            default   => '',
        };

        return '📌 **نظام الحضور والغياب:**' . $policy . $where;
    }

    protected function examsAnswer(string $role, array $data): string
    {
        if ($role === 'student' && !empty($data['exams'])) {
            $out = "📅 **امتحاناتك القادمة:**\n\n";
            foreach ($data['exams'] as $e) {
                $out .= "• **{$e['course']}** ({$e['name']}) — {$e['date']}" . (!empty($e['room']) ? " في {$e['room']}" : '') . "\n";
            }

            return $out;
        }

        return match ($role) {
            'hod'     => "📅 جداول الامتحانات وتوزيع القاعات والمراقبين من **'التنظيم الأكاديمي'** (الزر المركزي) أو `/hod/organization`.",
            'teacher' => "📅 لإنشاء امتحان/مذاكرة ورصد درجاتها: **'التقييم والعلامات'** من الزر المركزي أو `/teacher/grade-events`.",
            default   => "📅 جدول الامتحانات الرسمي يظهر ضمن شاشة **'الجدول'** (الزر المركزي) وعلى الويب في صفحة الجدول.",
        };
    }

    protected function gradesAnswer(string $role, array $data): string
    {
        if ($role === 'student' && !empty($data['grades'])) {
            $g   = $data['grades'];
            $out = "📊 **علاماتك:** المعدل الموزون التراكمي **{$g['average']}** (ناجح {$g['passed']} / راسب {$g['failed']})\n\n";
            foreach ($g['courses'] as $c) {
                $out .= "• {$c['title']}: " . ($c['total'] !== null ? $c['total'] . '/100' : 'لم تُرصد بعد') . " — {$c['status']}\n";
            }

            return $out . "\nالحد الأدنى للنجاح 50/100. للتصدير PDF/Excel: الزر المركزي ← **'العلامات'**.";
        }

        if ($role === 'parent' && !empty($data['children'])) {
            $out = "📊 **معدلات أبنائك:**\n\n";
            foreach ($data['children'] as $c) {
                $out .= "• **{$c['name']}**: " . ($c['average'] ?? 'غير متوفر') . "\n";
            }

            return $out;
        }

        return match ($role) {
            'teacher' => "📊 ترصد العلامات من **'التقييم والعلامات'** (الزر المركزي) أو `/teacher/grade-events`.",
            'affairs' => "📊 اعتماد الكشوف وتصدير النتائج من `/affairs/course-weights` و`/affairs/academic-card`.",
            default   => "📊 الحد الأدنى للنجاح 50/100. تجد العلامات في الزر المركزي ← **'العلامات'**.",
        };
    }

    protected function assignmentsAnswer(string $role, array $data): string
    {
        if ($role === 'student' && !empty($data['assignments'])) {
            $out = "📝 **واجبات لم تسلّمها بعد:**\n\n";
            foreach ($data['assignments'] as $a) {
                $out .= "• **{$a['title']}** ({$a['course']}) — آخر موعد {$a['due']}\n";
            }

            return $out . "\nالزر المركزي ← **'الواجبات والتكاليف'** لرفع الحل.";
        }

        return match ($role) {
            'student' => "📝 لا توجد واجبات معلّقة عليك حالياً. تجد الواجبات في الزر المركزي ← **'الواجبات والتكاليف'**.",
            'teacher' => "📝 لنشر واجب وتصحيح الحلول: الزر المركزي ← **'الواجبات'** أو `/teacher/assignments`." . (isset($data['pending_grading']) ? "\nلديك **{$data['pending_grading']}** تسليماً بانتظار التصحيح." : ''),
            'parent'  => "📝 تابع واجبات ابنك من الزر المركزي ← **'الواجبات'**.",
            default   => "📝 الواجبات يديرها الأساتذة ويتابعها الطلاب وأولياء الأمور؛ ليست ضمن مهام دورك المباشرة.",
        };
    }

    protected function servicesAnswer(string $role, array $data): string
    {
        return match ($role) {
            'student' => "📑 **الخدمات الطلابية:**\n\n• أيقونة القائمة في الهيدر ← **'الخدمات الطلابية'** لطلب إعادة تعيين الجهاز أو مصدقة أو اعتراض.\n• **'طلبات الإذن'** لإذن المغادرة/الإجازة.\n• الأعذار الطبية خلال 48 ساعة مع التقرير.\n\n💻 الويب: `/student/student-services` و`/student/leave-requests`.",
            'parent'  => "📑 لتقديم إذن غياب لابنك: الزر المركزي ← **'الأذونات والإجازات'**، وعلى الويب `/parent/permissions`.",
            'affairs' => "📑 الطلبات المعلقة حالياً: **" . ($data['pending_requests'] ?? 0) . "**.\nمن بوابة الخدمات الطلابية في الهيدر أو `/affairs/student-services` (يوجد زر **إعادة تعيين الجهاز المباشر**).",
            'hod'     => "📑 دراسة واعتماد الطلبات والإجازات: الزر المركزي ← **'طلبات الإجازات'** أو `/hod/student-services`.",
            'teacher' => "📑 الطلبات الإدارية تتم عبر الشؤون ورئيس القسم؛ يمكنك استدعاء ولي أمر من الزر المركزي ← **'استدعاء ولي الأمر'**.",
            default   => "📑 الخدمات والطلبات تُدار من لوحة الإدارة على الويب.",
        };
    }

    protected function appointmentsAnswer(string $role): string
    {
        return match ($role) {
            'parent'  => "🤝 الزر المركزي ← **'المواعيد والاستدعاءات'** للرد على استدعاء أو طلب لقاء، وعلى الويب `/parent/appointments`.",
            'teacher' => "🤝 لاستدعاء ولي أمر: الزر المركزي ← **'استدعاء ولي الأمر'**.",
            'hod'     => "🤝 إدارة المقابلات والاستدعاءات: `/hod/appointments` و`/hod/summons` أو الزر المركزي.",
            'affairs' => "🤝 جدولة مراجعات الطلاب وأولياء الأمور: الزر المركزي ← **'المواعيد والاستدعاءات'** أو `/affairs/appointments`.",
            'admin'   => "🤝 `/admin/appointments`.",
            default   => "🤝 إذا استُدعي ولي أمرك ستصلك إشعارات؛ تواصل مع الشؤون لأي موعد.",
        };
    }

    protected function coursesAnswer(string $role, array $data): string
    {
        if ($role === 'parent' && !empty($data['children'])) {
            $out = "📚 **مقررات أبنائك:**\n";
            foreach ($data['children'] as $c) {
                $out .= "\n**{$c['name']}:**\n";
                $titles = array_keys($c['course_teachers'] ?? []);
                $out .= $titles ? '• ' . implode("\n• ", $titles) . "\n" : "• لا توجد مقررات مسجلة حتى الآن.\n";
            }

            return $out . "\nاسألني \"مين بيدرّسه؟\" لمعرفة أستاذ كل مقرر.";
        }

        if (!empty($data['courses'])) {
            $list = implode('، ', $data['courses']);
            $what = $role === 'teacher' ? 'المقررات التي تدرّسها' : 'مقرراتك';

            return "📚 **{$what}:** {$list}\n\n" . match ($role) {
                'teacher' => 'لرفع ملفات المحاضرات: الزر المركزي ← **\'المحاضرات\'** أو `/teacher/lectures`.',
                default   => 'لتحميل الملفات والملخصات: الزر المركزي ← **\'المحاضرات\'** أو `/student/courses`.',
            };
        }

        return match ($role) {
            'teacher' => "📚 لرفع الملفات: الزر المركزي ← **'المحاضرات'** أو `/teacher/lectures`.",
            'student' => "📚 تجد المقررات والملخصات في الزر المركزي ← **'المحاضرات'** أو `/student/courses`.",
            default   => "📚 المقررات والمحاضرات يديرها الأساتذة ويتابعها الطلاب.",
        };
    }

    protected function accountsAnswer(string $role): string
    {
        return match ($role) {
            'affairs' => "👤 إدارة بيانات الطلاب والأرقام الجامعية: `/affairs/accounts` و`/affairs/university-ids`.",
            'hod'     => "👤 إدارة كادر القسم وتعيين مرشدي الدورات: `/hod/accounts`.",
            'admin'   => "👤 إنشاء/تعديل/تجميد الحسابات: `/admin/accounts`.",
            default   => "👤 تعدّل ملفك الشخصي وكلمة السر من **'الملف الشخصي'** في الشريط السفلي. لأي تعديل آخر على حسابك راجع شؤون الطلاب.",
        };
    }

    /**
     * مقررات المعلم حسب الدورة والسنة، مع فلترة إذا ذكر في سؤاله دورة أو سنة معينة.
     *
     * @param array<int, array{title:string, year:?int, programs:string[]}> $teaching
     */
    protected function teachingAnswer(string $q, array $teaching): string
    {
        // فلترة بالسنة
        $year = null;
        if ($this->has($q, ['اولى', 'أولى', 'الاولى', 'الأولى', 'سنة اولى', 'سنه اولى'])) {
            $year = 1;
        } elseif ($this->has($q, ['تانية', 'ثانية', 'التانية', 'الثانية', 'تاني سنة', 'ثاني'])) {
            $year = 2;
        } elseif ($this->has($q, ['تالتة', 'ثالثة', 'الثالثة'])) {
            $year = 3;
        }

        // فلترة بالدورة: اسم دورة (أو جزء منه) مذكور في السؤال
        $allPrograms = array_values(array_unique(array_merge(...array_map(fn ($c) => $c['programs'], $teaching) ?: [[]])));
        $program = null;
        $qWords = $this->stemWords($q);
        foreach ($allPrograms as $name) {
            $need = $this->stemWords($name);
            // كل كلمات اسم الدورة (بدون «ال» التعريف) موجودة في السؤال (مع السماح بسوابق مثل ب/ل)
            $found = $need && count(array_filter($need, function ($w) use ($qWords) {
                foreach ($qWords as $qw) {
                    if (mb_strlen($w) >= 3 && str_contains($qw, $w)) {
                        return true;
                    }
                }

                return false;
            })) === count($need);
            if ($found) {
                $program = $name;
                break;
            }
        }

        $rows = array_values(array_filter($teaching, fn ($c) =>
            ($year === null || $c['year'] === $year) && ($program === null || in_array($program, $c['programs'], true))));

        if (empty($rows)) {
            return '📚 لا توجد مقررات تدرّسها' . ($year ? ' في ' . AiContextBuilder::YEAR_AR[$year] : '') . ($program ? " ضمن دورة {$program}" : '') . ' حالياً.';
        }

        $yearName = fn ($y) => AiContextBuilder::YEAR_AR[$y] ?? 'سنة غير محددة';
        $titles   = fn (array $list) => implode('، ', array_unique(array_column($list, 'title')));

        // سؤال عن السنوات صراحةً (بدون ذكر الدورات) → نجمّع بالسنة أولاً
        $byYearFirst = $this->has($q, ['سنة', 'سنين', 'سنوات', 'السنة']) && !$this->has($q, ['دورة', 'دورات']);

        $out = '🎓 **' . ($program ? "مقرراتك في دورة {$program}" : ($year ? 'مقرراتك في ' . $yearName($year) : 'ما تدرّسه')) . ":**\n";

        if ($byYearFirst) {
            $groups = [];
            foreach ($rows as $c) {
                $groups[$yearName($c['year'])][] = $c;
            }
            ksort($groups);
            foreach ($groups as $label => $list) {
                $progs = array_unique(array_merge(...array_map(fn ($c) => $c['programs'], $list)));
                $out .= "\n**{$label}**" . ($progs ? ' — دورات: ' . implode('، ', $progs) : '') . ":\n• " . implode("\n• ", array_unique(array_column($list, 'title'))) . "\n";
            }
        } else {
            $groups = [];
            foreach ($rows as $c) {
                foreach ($c['programs'] ?: ['دورة غير محددة'] as $pname) {
                    $groups[$pname][$yearName($c['year'])][] = $c;
                }
            }
            ksort($groups);
            foreach ($groups as $pname => $years) {
                $out .= "\n**دورة {$pname}:**\n";
                ksort($years);
                foreach ($years as $label => $list) {
                    $out .= "• {$label}: {$titles($list)}\n";
                }
            }
        }

        $countCourses  = count(array_unique(array_column($rows, 'title')));
        $countPrograms = count(array_unique(array_merge(...array_map(fn ($c) => $c['programs'], $rows) ?: [[]])));
        $countYears    = count(array_unique(array_map(fn ($c) => $c['year'], $rows)));

        return $out . "\n📊 الإجمالي: {$countCourses} مقرراً في " . max($countPrograms, 1) . " دورة و{$countYears} سنة دراسية.";
    }

    /**
     * أسئلة رئيس القسم عن قسمه: الأساتذة، المرشدون، الطلاب، الدورات.
     */
    protected function hodAnswer(string $q, array $data): ?string
    {
        $dept = $data['department'];

        // المرشدون (مربو الدورات)
        if ($this->has($q, ['مرشد', 'مربي', 'مربّي', 'مرشدين', 'مربين', 'مشرف', 'المشرف', 'مشرفين', 'مشرفة', 'مسؤول الدورة', 'مسوول الدورة'])) {
            return $this->advisorsByProgramAnswer($data);
        }

        // الأساتذة
        if ($this->has($q, ['اساتذ', 'أساتذ', 'مدرسين', 'معلمين', 'كادر', 'دكاترة', 'مدربين', 'اساتذة', 'استاذ', 'أستاذ', 'كم مدرس', 'كم معلم'])) {
            $teachers = $data['dept_teachers'] ?? [];
            if (empty($teachers)) {
                return "👨‍🏫 لا يوجد أساتذة مسجلون في قسم {$dept} حالياً.";
            }
            $out = "👨‍🏫 **أساتذة قسم {$dept} ({$data['teachers_count']}):**\n";
            foreach ($teachers as $t) {
                $shown = array_slice($t['courses'], 0, 6);
                $more  = count($t['courses']) - count($shown);
                $out  .= "\n• **{$t['name']}**"
                    . (!empty($t['advisor']) ? " — مرشد دورة {$t['advisor']}" : '')
                    . ($shown ? "\n   المقررات: " . implode('، ', $shown) . ($more > 0 ? " (+{$more} أخرى)" : '') : "\n   بلا مقررات مسندة") . "\n";
            }

            return $out;
        }

        // الطلاب
        if ($this->has($q, ['طلاب', 'طالب', 'الطلبة', 'عدد'])) {
            $groups = $data['dept_students'] ?? [];
            if (empty($groups)) {
                return "👥 لا يوجد طلاب مسجلون في قسم {$dept} حالياً.";
            }
            $withNames = $this->has($q, ['مين', 'من هم', 'اسماء', 'أسماء', 'قائمة', 'شو عندي', 'اعرض']);
            $out = "👥 **طلاب قسم {$dept}: {$data['students_count']} طالباً**\n";
            foreach ($groups as $label => $info) {
                $out .= "\n🎓 **دورة {$label}** ({$info['count']})" . ($withNames ? ":\n• " . implode("\n• ", $info['names']) : '') . "\n";
            }

            return $out . ($withNames ? '' : "\nاسألني \"مين طلاب القسم\" لعرض الأسماء.");
        }

        // الدورات (البرامج)
        if ($this->has($q, ['دورات', 'دورة', 'برامج', 'تخصصات', 'اختصاصات'])) {
            if (empty($data['programs'])) {
                return "🎓 لا توجد دورات مسجلة في قسم {$dept}.";
            }
            $out = "🎓 **دورات قسم {$dept}:**\n\n";
            foreach ($data['programs'] as $p) {
                $count = 0;
                foreach ($data['dept_students'] ?? [] as $label => $info) {
                    if (str_starts_with($label, $p)) {
                        $count += $info['count'];
                    }
                }
                $sup = [];
                foreach ($data['dept_teachers'] ?? [] as $t) {
                    if (!empty($t['advisor']) && str_starts_with($t['advisor'], $p)) {
                        $sup[] = $t['name'] . ' (' . trim(substr($t['advisor'], strlen($p)), ' -') . ')';
                    }
                }
                $out .= "• **{$p}** — {$count} طالباً" . ($sup ? "\n   المشرفون: " . implode('، ', $sup) : '') . "\n";
            }

            return $out . "\nاسألني \"مين المشرف لكل دورة\" لعرض الدورات التي بلا مشرف.";
        }

        return null;
    }

    /**
     * مشرف (مرشد/مربي) كل دورة وسنة في قسم رئيس القسم، مع الإشارة للدورات التي بلا مشرف.
     */
    protected function advisorsByProgramAnswer(array $data): string
    {
        $dept = $data['department'];

        // [دورة => [سنة => [أسماء المشرفين]]] انطلاقاً من مجموعات الطلاب ومن تعيينات المرشدين
        $map = [];
        $add = function (string $label) use (&$map) {
            [$prog, $year] = array_pad(array_map('trim', explode(' - ', $label, 2)), 2, 'غير محددة');
            $map[$prog][$year] ??= [];

            return [$prog, $year];
        };
        foreach (array_keys($data['dept_students'] ?? []) as $label) {
            $add($label);
        }
        foreach ($data['dept_teachers'] ?? [] as $t) {
            if (!empty($t['advisor'])) {
                [$prog, $year] = $add($t['advisor']);
                $map[$prog][$year][] = $t['name'];
            }
        }

        if (empty($map)) {
            return "🧭 لا توجد دورات أو مشرفون مسجلون في قسم {$dept} حالياً.";
        }

        ksort($map);
        $out = "🧭 **مشرفو الدورات في قسم {$dept}:**\n";
        foreach ($map as $prog => $years) {
            $out .= "\n🎓 **دورة {$prog}:**\n";
            ksort($years);
            foreach ($years as $year => $names) {
                $out .= "• {$year}: " . ($names ? '**' . implode('، ', $names) . '**' : '⚠️ بلا مشرف') . "\n";
            }
        }

        return $out . "\nلتعيين أو تغيير مشرف: **إدارة الحسابات** (`/hod/accounts`).";
    }

    protected function helpAnswer(string $role): string
    {
        $lists = [
            'hod' => ['شو عندي أساتذة بالقسم؟', 'كم طالب عندي؟', 'مين طلاب القسم؟', 'شو دورات القسم؟', 'مين المشرف لكل دورة؟', 'كيف أعدّل جدول الامتحانات؟', 'كيف أبت بطلبات الإجازات؟', 'شو نظام الإنذارات؟', 'كيف أنشر إعلان؟', 'مين انا؟'],
            'teacher' => ['شو جدولي؟', 'شو الدورات اللي بعطيها؟', 'اي سنين بعطي؟', 'شو المواد اللي بعطيها بالسنة الأولى؟', 'مين الطلاب اللي بعطيهم؟', 'هل أنا مشرف دورة؟', 'كم تسليم بانتظار التصحيح؟', 'كيف أبدأ جلسة حضور؟', 'شو نظام الإنذارات؟'],
            'parent' => ['مين ابني؟', 'شو المواد اللي عندو؟', 'مين بيعطيه؟', 'كم غياب ابني؟', 'شو علامات ابني؟', 'كيف أقدّم إذن غياب؟', 'كيف أحجز موعد مع الإدارة؟'],
            'student' => ['شو جدولي؟', 'كم غيابي؟', 'شو علاماتي؟', 'شو واجباتي؟', 'متى امتحاناتي؟', 'مين بيعطيني؟', 'كيف أطلب إعادة تعيين الجهاز؟', 'كيف أقدّم عذر طبي؟'],
            'affairs' => ['كم طلب معلق عندنا؟', 'كيف أعيد تعيين جهاز طالب؟', 'كيف أصدّر كشف علامات؟', 'كيف أرقّي الطلاب؟', 'كيف أفعّل فصل دراسي؟'],
            'admin' => ['كيف أنشئ حساب؟', 'وين سجلات النشاط؟', 'كيف أعيّن رئيس قسم؟'],
        ];

        return "💡 **أسئلة تقدر تسألني إياها (" . AiRole::title($role) . "):**\n\n• " . implode("\n• ", $lists[$role] ?? $lists['student'])
            . "\n\nاكتبها بصيغتك، وأنا أجيبك من بياناتك الحقيقية.";
    }

    protected function teacherStudentsAnswer(array $data): ?string
    {
        if (empty($data['program_students'])) {
            return null;
        }

        $out = "👥 **طلابك: {$data['students_total']} طالباً**، حسب الدورة:\n";
        foreach ($data['program_students'] as $group => $info) {
            $out .= "\n🎓 **دورة {$group}** ({$info['count']}):\n• " . implode("\n• ", $info['names']) . "\n";
            if ($info['count'] > count($info['names'])) {
                $out .= '• ... و' . ($info['count'] - count($info['names'])) . " آخرين\n";
            }
        }

        return $out;
    }

    protected function teachersAnswer(string $role, array $data): ?string
    {
        $block = function (array $map): string {
            $out = '';
            foreach ($map as $title => $teachers) {
                $out .= "• **{$title}**: " . ($teachers ? implode('، ', $teachers) : 'غير محدد') . "\n";
            }

            return $out;
        };

        if ($role === 'student' && !empty($data['course_teachers'])) {
            return "👨‍🏫 **أساتذة مقرراتك:**\n\n" . $block($data['course_teachers']);
        }

        if ($role === 'parent' && !empty($data['children'])) {
            $out = "👨‍🏫 **أساتذة أبنائك:**\n";
            foreach ($data['children'] as $c) {
                $out .= "\n**{$c['name']}:**\n" . ($block($c['course_teachers'] ?? []) ?: "• لا توجد مقررات مسجلة.\n");
            }

            return $out;
        }

        return null;
    }

    protected function childrenAnswer(array $data): string
    {
        if (empty($data['children'])) {
            return "👨‍👩‍👧 لا يوجد أبناء مرتبطون بحسابك حتى الآن.

لربط ابن بحسابك: **الإضافة (+)** في الصفحة الرئيسية أو `/parent/children` على الويب، أو راجع شؤون الطلاب.";
        }

        $out = "👨‍👩‍👧 **أبناؤك المرتبطون بحسابك (" . count($data['children']) . "):**

";
        foreach ($data['children'] as $c) {
            $days = $c['attendance']['absence_days'] ?? 0;
            $out .= "• **{$c['name']}**" . (!empty($c['code']) ? " — الرقم الجامعي {$c['code']}" : '')
                . (!empty($c['level']) ? " — {$c['level']}" : '')
                . (!empty($c['branch']) ? " — {$c['branch']}" : '')
                . "
   أيام الغياب غير المعذورة: {$days}، المعدل: " . ($c['average'] ?? 'غير متوفر') . "
";
        }

        return $out . "
اسألني عن غياب أبنائك أو علاماتهم لمزيد من التفاصيل.";
    }

    protected function whoAmIAnswer(string $role, array $data): string
    {
        $out = "👤 **" . ($data['name'] ?? 'مستخدم') . "** — " . AiRole::title($role) . "
";
        foreach (['code' => 'الرقم الجامعي', 'level' => 'السنة/المستوى', 'branch' => 'التخصص', 'semester' => 'الفصل النشط', 'department' => 'القسم'] as $k => $label) {
            if (!empty($data[$k])) {
                $out .= "• {$label}: {$data[$k]}
";
            }
        }
        if ($role === 'parent' && !empty($data['children'])) {
            $out .= '• الأبناء: ' . implode('، ', array_column($data['children'], 'name')) . "
";
        }

        return rtrim($out);
    }

    protected function loginAnswer(string $role, string $baseHttp): string
    {
        $url = rtrim($baseHttp, '/') . LoginLinkSanitizer::LOGIN_PATH;

        return "🌐 **بوابة تسجيل الدخول على الويب (موحدة لكل الأدوار):**\n\n"
            . "🔗 **`{$url}`**\n\n"
            . "انسخ الرابط والصقه في شريط عنوان المتصفح، وبعد الدخول يوجّهك النظام تلقائياً إلى لوحة " . AiRole::title($role) . "."; 
    }

    // ───────────────────────── مساعدات ─────────────────────────

    /** كلمات النص بحروف صغيرة وبدون «ال» التعريف في أولها. */
    private function stemWords(string $text): array
    {
        $words = preg_split('/[\s\-_,،.؟?]+/u', mb_strtolower(trim($text), 'UTF-8'));

        return array_values(array_filter(array_map(fn ($w) => preg_replace('/^ال/u', '', $w), $words)));
    }

    private function wordCount(string $q): int
    {
        return count(array_filter(preg_split('/\s+/u', trim($q))));
    }

    private function levelText(?string $level, int $days): string
    {
        $first = \App\Services\AbsenceWarningService::FIRST_WARNING_DAYS;

        return match ($level) {
            'final'  => '⛔ إنذار نهائي (إحالة للإدارة)',
            'second' => '🚨 إنذار ثانٍ (استدعاء ولي الأمر)',
            'first'  => '⚠️ إنذار أول',
            default  => $days > 0 ? "✅ وضع سليم (الإنذار الأول عند {$first} أيام)" : '✅ لا غياب',
        };
    }

    private function hintFor(string $role): string
    {
        return match ($role) {
            'student' => ' عن جدولك وغيابك وعلاماتك وواجباتك',
            'teacher' => ' عن جدول تدريسك والحضور والواجبات والعلامات',
            'parent'  => ' عن أبنائك وغيابهم ومعدلاتهم',
            'hod'     => ' عن التنظيم الأكاديمي والتقارير والطلبات',
            'affairs' => ' عن الطلبات الطلابية والكشوف والحسابات',
            default   => '',
        };
    }

    private function isGreeting(string $q): bool
    {
        if (in_array($q, ['كيفك', 'كيفك اليوم', 'مرحبا', 'أهلا', 'اهلا', 'اهلين', 'السلام عليكم', 'صباح الخير', 'مساء الخير', 'hi', 'hello'], true)) {
            return true;
        }

        return $this->has($q, ['شو أخبارك', 'شو اخبارك', 'كيف حالك', 'عساك بخير']);
    }

    /** @param string[] $needles */
    private function has(string $q, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($q, $n)) {
                return true;
            }
        }

        return false;
    }
}
