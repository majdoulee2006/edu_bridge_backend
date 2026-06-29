<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير الحضور والغياب</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            color: #1F3A93;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #1F3A93;
            color: #fff;
            font-weight: bold;
        }
        .present {
            color: #1B5E20;
            font-weight: bold;
        }
        .absent {
            color: #B71C1C;
            font-weight: bold;
        }
        .pending {
            color: #d97706;
            font-weight: bold;
        }
        .dash {
            color: #999;
        }
        .student-name {
            text-align: right;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>تقرير الحضور والغياب المجمع</h2>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d') }}</p>
    </div>

    <!-- Daily Summary Table -->
    <h3 style="color: #333;">الملخص اليومي</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">اسم الطالب</th>
                <th style="width: 15%;">الفرع/السنة</th>
                @foreach($daysMap as $date => $dayName)
                    <th>{{ $dayName }}<br>{{ $date }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allStudents as $studentId => $info)
            <tr>
                <td class="student-name">{{ $info['name'] }}</td>
                <td>{{ $info['branch'] }} - {{ $info['year'] }}</td>
                @foreach($daysMap as $date => $dayName)
                    @php
                        $status = '-';
                        if (isset($dailyStatus[$studentId][$date])) {
                            $status = $dailyStatus[$studentId][$date];
                        }
                    @endphp
                    @if($status === 'present')
                        <td class="present">حاضر</td>
                    @elseif($status === 'absent')
                        <td class="absent">غائب</td>
                    @elseif($status === 'pending')
                        <td class="pending">قيد الانتظار</td>
                    @else
                        <td class="dash">-</td>
                    @endif
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Course Sheets -->
    @foreach($courseSessions as $courseId => $sessionsList)
        @php
            $courseTitle = $sessionsList[0]->course_title;
        @endphp
        <h3 style="color: #333; border-bottom: 2px solid #1F3A93; padding-bottom: 5px;">مادة: {{ $courseTitle }}</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">اسم الطالب</th>
                    <th style="width: 15%;">الفرع/السنة</th>
                    <th style="width: 10%;">الحضور</th>
                    <th style="width: 10%;">الغياب</th>
                    @foreach($sessionsList as $session)
                        @php
                            $dateObj = \Carbon\Carbon::parse($session->created_at)->locale('ar');
                        @endphp
                        <th>{{ $dateObj->translatedFormat('l') }}<br>{{ $dateObj->format('Y-m-d') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($allStudents as $studentId => $info)
                    @php
                        $isEnrolled = false;
                        foreach($sessionsList as $session) {
                            if(isset($matrix[$studentId][$session->lesson_id])) {
                                $isEnrolled = true; break;
                            }
                        }
                    @endphp
                    
                    @if($isEnrolled)
                        @php
                            $presentCount = 0;
                            $absentCount = 0;
                            foreach($sessionsList as $session) {
                                $st = $matrix[$studentId][$session->lesson_id] ?? null;
                                if($st === 'present') $presentCount++;
                                elseif($st === 'absent') $absentCount++;
                            }
                        @endphp
                        <tr>
                            <td class="student-name">{{ $info['name'] }}</td>
                            <td>{{ $info['branch'] }} - {{ $info['year'] }}</td>
                            <td style="background-color: #E8F5E9; color: #1B5E20; font-weight:bold;">{{ $presentCount }}</td>
                            <td style="background-color: #FFEBEE; color: #B71C1C; font-weight:bold;">{{ $absentCount }}</td>
                            @foreach($sessionsList as $session)
                                @php
                                    $st = $matrix[$studentId][$session->lesson_id] ?? '-';
                                @endphp
                                @if($st === 'present')
                                    <td class="present">حاضر</td>
                                @elseif($st === 'absent')
                                    <td class="absent">غائب</td>
                                @elseif($st === 'pending')
                                    <td class="pending">قيد الانتظار</td>
                                @else
                                    <td class="dash">-</td>
                                @endif
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
