<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>الجدول الدراسي الأسبوعي</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #CCAA00;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            color: #CCAA00;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 11px;
        }
        .student-info {
            margin-bottom: 15px;
            background-color: #fcf8e3;
            border: 1px solid #fbeed5;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .student-info table {
            width: 100%;
            border: none;
            margin: 0;
        }
        .student-info td {
            border: none;
            padding: 2px 0;
            text-align: right;
            font-size: 11px;
        }
        table.schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.schedule-table th, table.schedule-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        table.schedule-table th {
            background-color: #1a2633;
            color: #fff;
            font-weight: bold;
            font-size: 11px;
        }
        table.schedule-table th.time-header {
            background-color: #111b26;
            width: 100px;
        }
        .course-box {
            font-weight: bold;
            font-size: 10px;
        }
        .course-title {
            color: #CCAA00;
            display: block;
            margin-bottom: 2px;
        }
        .course-teacher {
            color: #666;
            font-size: 8px;
            display: block;
        }
        .course-room {
            color: #888;
            font-size: 8px;
            display: block;
        }
        .empty-cell {
            color: #ccc;
        }
        .footer {
            margin-top: 25px;
            font-size: 10px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>الجدول الدراسي الأسبوعي</h2>
        <p>معهد بريدج التعليمي (Edu Bridge)</p>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td><strong>اسم الطالب:</strong> {{ $student->user->full_name }}</td>
                <td><strong>الرقم الجامعي:</strong> {{ $student->student_code ?? $student->user->university_id }}</td>
            </tr>
            <tr>
                <td><strong>البرنامج الدراسي:</strong> {{ $student->program->name ?? 'غير محدد' }}</td>
                <td><strong>السنة الدراسية/المستوى:</strong> {{ $student->level }}</td>
            </tr>
            <tr>
                <td><strong>تاريخ التصدير:</strong> {{ now()->format('Y-m-d H:i') }}</td>
                <td></td>
            </tr>
        </table>
    </div>

    @php
        $days = [
            'Sunday'    => 'الأحد',
            'Monday'    => 'الاثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
        ];

        // Group schedules by day (keys normalized to Arabic)
        $dayMap = [
            'Sunday'    => 'الأحد',
            'Monday'    => 'الاثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
            'Saturday'  => 'السبت',
        ];

        $grouped = $schedules->groupBy(function($item) use ($dayMap) {
            return $dayMap[$item->day] ?? $item->day;
        });

        // Time slots
        $allTimes = $schedules->map(function($s) {
            return date('H:i', strtotime($s->start_time)) . ' - ' . date('H:i', strtotime($s->end_time));
        })->unique()->sort()->values();
    @endphp

    @if($schedules->isNotEmpty())
    <table class="schedule-table">
        <thead>
            <tr>
                <th class="time-header">الوقت</th>
                @foreach($days as $dayEn => $dayAr)
                    <th>{{ $dayAr }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allTimes as $slot)
                @php
                    list($slotStart, $slotEnd) = array_map('trim', explode('-', $slot));
                @endphp
                <tr>
                    <td style="font-weight: bold; background-color: #f9f9f9;">
                        {{ date('h:i A', strtotime($slotStart)) }}<br>
                        <span style="font-size: 8px; font-weight: normal; color: #666;">{{ date('h:i A', strtotime($slotEnd)) }}</span>
                    </td>
                    @foreach($days as $dayEn => $dayAr)
                        <td>
                            @php
                                $daySchedules = $grouped->get($dayAr) ?? collect();
                                $match = $daySchedules->first(function($s) use ($slotStart) {
                                    return date('H:i', strtotime($s->start_time)) === $slotStart;
                                });
                            @endphp
                            @if($match)
                                <div class="course-box">
                                    <span class="course-title">{{ $match->course_title }}</span>
                                    @if($match->teacher_name)
                                        <span class="course-teacher">{{ $match->teacher_name }}</span>
                                    @endif
                                    @if($match->room)
                                        <span class="course-room">[{{ $match->room }}]</span>
                                    @endif
                                </div>
                            @else
                                <span class="empty-cell">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 30px; background-color: #f9f9f9; border: 1px dashed #ccc; border-radius: 6px;">
        لا توجد حصص مجدولة حالياً
    </div>
    @endif

    <div class="footer">
        تم التصدير تلقائياً من تطبيق Edu-Bridge التعليمي
    </div>

</body>
</html>
