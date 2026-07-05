<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>الجدول الامتحاني</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 13px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #CCAA00;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            color: #CCAA00;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        .student-info {
            margin-bottom: 20px;
            background-color: #fcf8e3;
            border: 1px solid #fbeed5;
            padding: 10px 15px;
            border-radius: 6px;
        }
        .student-info table {
            width: 100%;
            border: none;
            margin: 0;
        }
        .student-info td {
            border: none;
            padding: 4px 0;
            text-align: right;
            font-size: 12px;
        }
        table.exams-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.exams-table th, table.exams-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        table.exams-table th {
            background-color: #1a1a1a;
            color: #fff;
            font-weight: bold;
            font-size: 12px;
        }
        table.exams-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>الجدول الامتحاني للطالب</h2>
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

    <table class="exams-table">
        <thead>
            <tr>
                <th style="width: 35%;">المادة / التقييم</th>
                <th style="width: 20%;">التاريخ</th>
                <th style="width: 15%;">اليوم</th>
                <th style="width: 15%;">الوقت</th>
                <th style="width: 15%;">نوع التقييم</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exams as $exam)
                @php
                    $date = \Carbon\Carbon::parse($exam->date);
                @endphp
                <tr>
                    <td>
                        <strong>{{ $exam->course_title }}</strong>
                        @if($exam->title)
                            <br><small style="color: #666;">({{ $exam->title }})</small>
                        @endif
                    </td>
                    <td>{{ $date->format('Y-m-d') }}</td>
                    <td>{{ $date->translatedFormat('l') }}</td>
                    <td>
                        {{ $exam->time ?? $date->format('h:i A') }}
                        @if(isset($exam->duration) && $exam->duration)
                            <br><small style="color: #666;">({{ $exam->duration }})</small>
                        @else
                            <br><small style="color: #666;">({{ $exam->event_type === 'exam' ? 'ساعتان' : 'ساعة' }})</small>
                        @endif
                    </td>
                    <td>{{ $exam->event_type === 'exam' ? 'امتحان' : 'مذاكرة' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>يرجى الحضور قبل موعد الامتحان بـ 15 دقيقة على الأقل وإحضار البطاقة الجامعية.</p>
        <p>تم توليد هذا التقرير تلقائياً من نظام Edu Bridge.</p>
    </div>

</body>
</html>
