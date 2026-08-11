<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>كشف العلامات الأكاديمي</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #FFCC00;
            padding-bottom: 12px;
        }
        .header h2 {
            margin: 0;
            color: #1a1a1a;
            font-size: 22px;
        }
        .header h4 {
            margin: 4px 0 0 0;
            color: #FFCC00;
            font-size: 15px;
        }
        .header p {
            margin: 4px 0 0 0;
            color: #666;
            font-size: 11px;
        }
        .student-info {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .student-info table {
            width: 100%;
            border: none;
            margin: 0;
        }
        .student-info td {
            border: none;
            padding: 5px 0;
            text-align: right;
            font-size: 12px;
        }
        .summary-box {
            margin-bottom: 20px;
            background-color: #fffde7;
            border: 1px solid #fff59d;
            padding: 10px 16px;
            border-radius: 8px;
        }
        .summary-box table {
            width: 100%;
            border: none;
        }
        .summary-box td {
            border: none;
            text-align: center;
            font-size: 12px;
        }
        table.grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.grades-table th, table.grades-table td {
            border: 1px solid #ddd;
            padding: 9px 8px;
            text-align: center;
        }
        table.grades-table th {
            background-color: #1a1a1a;
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
        }
        table.grades-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .badge-pass {
            color: #2e7d32;
            font-weight: bold;
        }
        .badge-fail {
            color: #c62828;
            font-weight: bold;
        }
        .badge-none {
            color: #616161;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>معهد الجسر التعليمي (Edu Bridge)</h2>
        <h4>بطاقة الطالب الأكاديمية - كشف العلامات</h4>
        <p>تاريخ الإصدار: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td style="width: 50%;"><strong>اسم الطالب:</strong> {{ $student['full_name'] }}</td>
                <td style="width: 50%;"><strong>الرقم الجامعي:</strong> {{ $student['university_id'] }}</td>
            </tr>
            <tr>
                <td><strong>التخصص / القسم:</strong> {{ $student['department'] }}</td>
                <td><strong>السنة الدراسية:</strong> {{ $student['level'] }}</td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table>
            <tr>
                <td><strong>المعدل العام:</strong> <span style="font-size: 14px; color: #d84315;">{{ $summary['average'] }}%</span></td>
                <td><strong>إجمالي المواد:</strong> {{ $summary['total_courses'] }}</td>
                <td><strong>المواد الناجحة:</strong> {{ $summary['passed_courses'] }}</td>
                <td><strong>لم يتم التقدم:</strong> {{ $summary['not_attended'] }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 32%;">اسم المادة</th>
                <th style="width: 10%;">السنة</th>
                <th style="width: 12%;">المذاكرة</th>
                <th style="width: 14%;">عملي / شفهي</th>
                <th style="width: 14%;">امتحان نهائي</th>
                <th style="width: 10%;">المجموع</th>
                <th style="width: 8%;">الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($academicCard as $course)
                <tr>
                    <td style="text-align: right;"><strong>{{ $course['title'] }}</strong></td>
                    <td>{{ $course['year'] ?? '-' }}</td>
                    <td>{{ $course['quiz_score'] !== null ? $course['quiz_score'] : '-' }}</td>
                    <td>{{ $course['oral_score'] !== null ? $course['oral_score'] : '-' }}</td>
                    <td>{{ $course['final_score'] !== null ? $course['final_score'] : '-' }}</td>
                    <td>
                        @if($course['total_score'] !== null)
                            <strong>{{ $course['total_score'] }}</strong>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($course['status'] === 'ناجح')
                            <span class="badge-pass">ناجح</span>
                        @elseif($course['status'] === 'راسب')
                            <span class="badge-fail">راسب</span>
                        @else
                            <span class="badge-none">لم يتم التقدم</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>هذا المستند وثيقة أكاديمية رسمية صادرة الكترونياً عن إدارة شؤون الطلاب بمعهد Edu Bridge.</p>
    </div>

</body>
</html>
