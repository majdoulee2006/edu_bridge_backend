<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>جدول الامتحانات</title>
    <style>
        body { font-family: 'sans-serif'; text-align: right; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background-color: #EAFA00; }
    </style>
</head>
<body>
    <h2 style="text-align: center;">جدول الامتحانات النهائية</h2>
    <table>
        <thead>
            <tr>
                <th>المادة</th>
                <th>التاريخ</th>
                <th>الوقت</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exams as $exam)
            <tr>
                <td>{{ $exam->exam_name ?? ($exam->course->title ?? 'مادة') }}</td>
                <td>{{ \Carbon\Carbon::parse($exam->exam_date)->format('Y-m-d') }}</td>
                <td>{{ \Carbon\Carbon::parse($exam->exam_date)->format('h:i A') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>