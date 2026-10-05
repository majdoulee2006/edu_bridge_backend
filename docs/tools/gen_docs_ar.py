import json, os, re, collections
T = os.environ['TEMP']
s = json.load(open(T + '/schema.json', encoding='utf-8'))
cols = collections.defaultdict(list)
for c in s['cols']:
    cols[c['t']].append(c)
fks = collections.defaultdict(list)
for f in s['fks']:
    fks[f['t']].append(f)
kind = {t['t']: t['tt'] for t in s['tables']}

D = {
'users': 'الحسابات الأساسية لكل الأدوار (الدخول، الدور، الحالة، الجلسة، قفل الحساب، توكن FCM، معرّف تيليغرام)',
'roles': 'تعريف الأدوار الستة',
'students': 'ملف الطالب: الكود، المستوى، البرنامج، ربط الجهاز، بصمة الوجه المرجعية',
'teachers': 'ملف المعلّم، وحقول المربّي (advisor_branch / advisor_year)',
'parents': 'ملف ولي الأمر',
'heads': 'رؤساء الأقسام وربطهم بالقسم',
'admins': 'ملف الإدارة',
'parent_students': 'ربط أولياء الأمور بالطلاب (علاقة كثير لكثير)',
'departments': 'الأقسام الأكاديمية، وسياسة مزامنة الحضور بدون إنترنت (offline_sync_policy)',
'programs': 'البرامج/الاختصاصات التابعة للأقسام',
'courses': 'المقررات (الاسم، الكود، الساعات، الوزن، السنة، الفصل)',
'course_program': 'ربط المقررات بالبرامج',
'course_departments': 'ربط المقررات بالأقسام',
'course_teachers': 'تدريس المعلّمين للمقررات، مع الدور (مثل advisor)',
'semesters': 'الفصول الدراسية وتفعيل الفصل الحالي',
'enrollments': 'تسجيل الطلاب في المقررات حسب الفصل',
'subjects': 'المواد (بنية قديمة/مساعدة)',
'schedules': 'الجدول الدراسي الأسبوعي (اليوم، الوقت، القاعة، الشعبة)',
'head_schedule_entries': 'مدخلات الجدول التي ينشئها رئيس القسم',
'exams': 'جدول الامتحانات',
'lessons': 'المحاضرات/الجلسات (ملف، فيديو، معلّم، مقرر)، وتُنشأ جلسة حضور لكل محاضرة',
'resources': 'ملفات ومراجع المقرر',
'attendance_sessions': 'جلسة حضور: رمز QR المتجدد، الموقع ونصف القطر، الصلاحية',
'attendance': 'سجل الحضور: الحالة، الجهاز، الموقع، درجة مطابقة الوجه، سبب الرفض، العذر',
'student_warnings': 'إنذارات الغياب التلقائية (الأول/الثاني/النهائي)',
'assignments': 'الواجبات',
'assignment_submissions': 'تسليمات الطلاب والتصحيح',
'quizzes': 'المذاكرات الإلكترونية', 'quiz_questions': 'أسئلة المذاكرات', 'quiz_options': 'خيارات أسئلة المذاكرات',
'grades': 'علامات الامتحانات (النظام القديم)',
'grade_events': 'أحداث التقييم (امتحان/مذاكرة/شفهي) بتاريخها ووقتها وعلامتها العظمى',
'grade_entries': 'علامات الطلاب في كل حدث تقييم',
'grade_report_requests': 'طلبات رئيس القسم لتقارير العلامات من المعلّمين',
'performance_reports': 'تقارير الأداء',
'report_requests': 'طلبات تقارير سلوكية (من رئيس القسم أو ولي الأمر) وملاحظات الرئيس',
'admin_generated_reports': 'سجل التقارير التي أنشأتها الإدارة',
'leave_requests': 'طلبات الإجازة (الطالب ← ولي الأمر ← رئيس القسم ← الشؤون)',
'absence_requests': 'طلبات إذن الغياب',
'student_requests': 'طلبات الخدمات الطلابية (استرحام، وثائق، مذاكرة تعويضية، فك قفل جهاز)',
'photo_change_requests': 'طلبات تغيير صورة الطالب المرجعية',
'parent_meeting_requests': 'طلبات مواعيد من أولياء الأمور',
'parent_summons': 'استدعاءات أولياء الأمور (يدوية أو تلقائية بعد 10 أيام غياب)',
'messages': 'رسائل الدردشة (مرفقات، رد، تحويل، حذف، رسائل مؤقتة، تسليم/قراءة)',
'groups': 'مجموعات الدردشة', 'group_user': 'أعضاء المجموعات', 'chats': 'جدول دردشة قديم',
'notifications': 'الإشعارات داخل النظام (النوع، التصنيف، المعرّف المرتبط)',
'announcements': 'الإعلانات والأنشطة (جمهور مستهدف، صور، رابط، تفاصيل فعالية)',
'calendar_events': 'أحداث التقويم الأكاديمي',
'university_ids': 'الأرقام الجامعية المسبقة التي تُنشئها الشؤون قبل تسجيل الطالب',
'otp_codes': 'رموز التحقق عبر البريد (الحالية)', 'otps': 'رموز تحقق (بنية قديمة)',
'user_activities': 'سجل نشاط المستخدمين (تدقيق)', 'user_activity': 'سجل نشاط قديم',
'system_settings': 'إعدادات النظام (الثيم)',
'personal_access_tokens': 'توكنات Sanctum', 'sessions': 'جلسات الويب', 'session': 'جدول جلسات قديم',
'cache': 'كاش Laravel', 'cache_locks': 'أقفال الكاش', 'jobs': 'طابور المهام', 'job_batches': 'دفعات المهام', 'failed_jobs': 'المهام الفاشلة',
'admin_profile_stats_view': 'View: إحصاءات ملف الأدمن',
'affairs_dashboard_stats_view': 'View: إحصاءات لوحة الشؤون',
'teacher_dashboard_stats_view': 'View: إحصاءات لوحة المعلّم',
}


def esc(x):
    return '' if x is None else str(x).replace('|', '\\|').replace('\n', ' ')


nbase = len([t for t in kind.values() if t == 'BASE TABLE'])
nview = len([t for t in kind.values() if t != 'BASE TABLE'])
out = ['# قاموس قاعدة البيانات\n',
       f'> مُولَّد آلياً من الـ migrations الفعلية (تشغيل `php artisan migrate` على قاعدة فارغة ثم قراءة `information_schema`). **{nbase} جدولاً و{nview} Views، {len(s["cols"])} عموداً، {len(s["fks"])} مفتاحاً أجنبياً.**\n',
       '## ملاحظات مهمة قبل القراءة\n',
       '- **المفاتيح الأساسية مخصصة وليست `id`** في أغلب الجداول: `user_id` و`student_id` و`teacher_id` و`course_id` و`lesson_id`... (استثناءات: `programs.id` و`grade_events.id` و`attendance_sessions.id`).',
       '- **العلاقات المنطقية أكثر من المفاتيح الأجنبية المعلنة** (72 مفتاحاً فقط). كثير من الربط يتم في الكود دون قيد في القاعدة، مثل `parent_students` و`leave_requests.student_id`.',
       '- **تنبيه تناسق:** `parent_students.parent_id/student_id` و`leave_requests.student_id` قد تحمل `users.user_id` أو المعرّف الداخلي (`parents.parent_id` / `students.student_id`). الكود يتعامل مع الاثنين (انظر `08-project-status.md`، البند B-04).',
       '- جداول بنية قديمة ما زالت موجودة: `chats`, `user_activity`, `session`, `otps`, `subjects`، ولا يبدو أنها مستخدمة (يلزم تحقق قبل حذفها).\n',
       '## المخطط العلاقاتي للجداول الأساسية\n',
       'يعرض أهم الجداول والعلاقات (مفاتيح أجنبية معلنة + علاقات منطقية موثَّقة من الـ models). المخطط بصيغة Mermaid ويُرسم تلقائياً في GitHub وموقع التوثيق.\n',
       '```mermaid', 'erDiagram',
       '    users ||--o| students : "user_id"', '    users ||--o| teachers : "user_id"', '    users ||--o| parents : "user_id"',
       '    users ||--o| heads : "user_id"', '    roles ||--o{ users : "role_id"',
       '    departments ||--o{ programs : "department_id"', '    departments ||--o| heads : "department_id"', '    programs ||--o{ students : "program_id"',
       '    courses }o--o{ programs : "course_program"', '    courses }o--o{ departments : "course_departments"', '    courses }o--o{ teachers : "course_teachers"',
       '    students }o--o{ courses : "enrollments"', '    students }o--o{ parents : "parent_students"',
       '    courses ||--o{ lessons : "course_id"', '    lessons ||--o{ attendance_sessions : "lesson_id"', '    lessons ||--o{ attendance : "lesson_id"',
       '    students ||--o{ attendance : "student_id"', '    students ||--o{ student_warnings : "student_id"',
       '    courses ||--o{ assignments : "course_id"', '    assignments ||--o{ assignment_submissions : "assignment_id"', '    students ||--o{ assignment_submissions : "student_id"',
       '    courses ||--o{ grade_events : "course_id"', '    grade_events ||--o{ grade_entries : "grade_event_id"', '    students ||--o{ grade_entries : "student_id"',
       '    users ||--o{ messages : "sender / receiver"', '    users ||--o{ notifications : "user_id"', '    students ||--o{ parent_summons : "student_id"',
       '    semesters ||--o{ enrollments : "semester_id"',
       '```\n',
       '## فهرس الجداول\n', '| الجدول | النوع | الوصف |', '|---|---|---|']
for t in sorted(kind):
    out.append(f'| [`{t}`](#{t}) | {"View" if kind[t] != "BASE TABLE" else "جدول"} | {D.get(t, "—")} |')
out.append('\n---\n')
for t in sorted(kind):
    out.append(f'## {t}\n')
    if t in D:
        out.append(f'{D[t]}\n')
    out.append('| العمود | النوع | Null | افتراضي | مفتاح | ملاحظات |\n|---|---|---|---|---|---|')
    for c in cols[t]:
        k = {'PRI': 'PK', 'UNI': 'UNIQUE', 'MUL': 'INDEX'}.get(c['k'], '')
        note = c['cm'] or ''
        if 'auto_increment' in (c['e'] or ''):
            note = (note + ' auto-increment').strip()
        out.append(f"| `{c['c']}` | `{esc(c['ty'])}` | {'نعم' if c['n'] == 'YES' else 'لا'} | {esc(c['d'])} | {k} | {esc(note)} |")
    if fks[t]:
        out.append('\n**مفاتيح أجنبية:** ' + '، '.join(f"`{f['c']}` ← `{f['rt']}.{f['rc']}`" for f in fks[t]))
    out.append('')
open('docs/ar/03-database.md', 'w', encoding='utf-8', newline='\n').write('\n'.join(out))

# ---------------- API ----------------
r = json.load(open(T + '/routes.json', encoding='utf-8'))
api = [x for x in r if x['uri'].startswith('api/')]


def roles(m):
    for x in m:
        mm = re.search(r'RoleMiddleware:(.+)', x)
        if mm:
            return mm.group(1)
    return None


def auth(x):
    m = x['middleware']
    if any('Authenticate:sanctum' in y for y in m):
        rr = roles(m)
        return ('مصادقة + دور: ' + rr) if rr else 'مصادقة (أي دور)'
    return 'عام'


def thr(x):
    for y in x['middleware']:
        mm = re.search(r'ThrottleRequests:(.+)', y)
        if mm and mm.group(1) != 'api':
            return ' ⏱' + mm.group(1)
    return ''


def short(a):
    a = a.replace('App\\Http\\Controllers\\', '')
    return '`' + a + '`' if '@' in a else '`Closure`'


groups = collections.OrderedDict()
groups['عام (بدون مصادقة)'] = lambda x: auth(x) == 'عام'
groups['مشترك بين الأدوار (مصادقة فقط)'] = lambda x: auth(x) == 'مصادقة (أي دور)'


def pref(p):
    return lambda x: x['uri'].startswith('api/' + p + '/') and auth(x) != 'عام'


for lbl, p in [('الطالب', 'student'), ('المعلّم', 'teacher'), ('رئيس القسم', 'department-head'), ('الإدارة', 'admin'), ('ولي الأمر', 'parent'), ('الشؤون', 'affairs')]:
    groups[lbl + f' (`/api/{p}/*`)'] = pref(p)
used = set()
sections = []
for lbl, f in groups.items():
    rows = [x for x in api if id(x) not in used and f(x)]
    for x in rows:
        used.add(id(x))
    sections.append((lbl, rows))
rest = [x for x in api if id(x) not in used]
if rest:
    sections.append(('أخرى', rest))
tot = len(api)
o = ['# مرجع الـ API\n',
     f'> مُولَّد آلياً من `php artisan route:list` ({tot} نقطة نهاية تحت `/api`). **الأساس:** `<SERVER>/api` (مثال محلي: `http://127.0.0.1:8000/api`).\n',
     '## الاستخدام العام\n',
     '- **المصادقة:** Laravel Sanctum. بعد `POST /api/login` يرجع `token`، ويُرسَل في كل طلب: `Authorization: Bearer <token>` مع `Accept: application/json`.',
     '- **جلسة واحدة:** تسجيل الدخول من جهاز جديد لا يُسمح به أثناء وجود جلسة نشطة (يرجع `423`). التوكن الذي أُبطل يتلقى `401` مع `error_code = LOGGED_IN_ELSEWHERE`.',
     '- **الأدوار:** `student`, `teacher`, `head` (رئيس القسم), `admin`, `parent`, `affairs`. الرد على دور غير مسموح: `403`.',
     '- **حدود المعدّل:** `throttle:api` عام (240 طلب/دقيقة لكل مستخدم). مسارات الدخول والـ OTP لها حدود أشد (⏱ في الجدول). التجاوز يرجع `429`.',
     '- **صيغة الرد:** JSON، غالباً `{ "success": true|false, "message": "...", "data": ... }`. الأخطاء: `422` للتحقق، `403` للصلاحية، `404` غير موجود، `423` حساب مقفول أو جلسة مشغولة.',
     '- **ترميز:** UTF-8، والنصوص العربية تُرجَع بدون escape.',
     '- **ملاحظة:** أسماء الدوال (`Controller@method`) وصفية، وهي نقطة البداية لقراءة منطق كل نقطة في الكود.\n',
     '## فهرس\n']
for lbl, rows in sections:
    o.append(f'- {lbl}: **{len(rows)}**')
o.append('')
for lbl, rows in sections:
    o.append(f'\n## {lbl}\n\n| الطريقة | المسار | المعالج | الصلاحية |\n|---|---|---|---|')
    for x in sorted(rows, key=lambda z: (z['uri'], z['method'])):
        m = x['method'].replace('GET|HEAD', 'GET')
        o.append(f"| {m} | `/{x['uri']}` | {short(x['action'])} | {auth(x)}{thr(x)} |")
open('docs/ar/04-api-reference.md', 'w', encoding='utf-8', newline='\n').write('\n'.join(o))
print({l: len(rw) for l, rw in sections}, tot)
