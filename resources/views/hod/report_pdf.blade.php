<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTypeLabel }} - {{ $report->student_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            color: #0f172a;
            background: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            line-height: 1.5;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #ca8a04;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .inst-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #475569;
            line-height: 1.3;
        }
        .inst-main {
            font-size: 11pt;
            font-weight: bold;
            color: #ca8a04;
        }
        .report-title-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            margin-bottom: 14px;
            text-align: center;
        }
        .report-title-box h2 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }
        .report-title-box p {
            margin: 3px 0 0 0;
            font-size: 8pt;
            color: #64748b;
        }
        .info-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            margin-bottom: 14px;
        }
        .info-table td {
            padding: 6px 10px;
            font-size: 9pt;
            border: 1px solid #e2e8f0;
        }
        .info-table td.lbl {
            width: 20%;
            font-weight: bold;
            color: #475569;
            background-color: #f1f5f9;
        }
        .info-table td.val {
            width: 30%;
            color: #0f172a;
            font-weight: bold;
        }
        .section-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            margin-bottom: 14px;
        }
        .section-header-teacher {
            background-color: #eff6ff;
            color: #1e40af;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 9.5pt;
            border-bottom: 1px solid #bfdbfe;
        }
        .section-header-hod {
            background-color: #fefce8;
            color: #854d0e;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 9.5pt;
            border-bottom: 1px solid #fef08a;
        }
        .section-body {
            padding: 10px;
            font-size: 9.5pt;
            line-height: 1.6;
            color: #1e293b;
            background-color: #ffffff;
        }
        .footer-table {
            width: 100%;
            margin-top: 18px;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            font-size: 8pt;
            color: #64748b;
        }
        .footer-table td {
            vertical-align: middle;
            border: none;
        }
    </style>
</head>
<body>

    <!-- الترويسة الرسمية -->
    <table class="header-table">
        <tr>
            <td style="width: 70%; text-align: right;">
                <div class="inst-title">الجمهورية العربية السورية</div>
                <div class="inst-title">وكالة هيئة الأمم المتحدة لإغاثة وتشغيل اللاجئين (UNRWA)</div>
                <div class="inst-main">معهد دمشق المتوسط (DTC)</div>
                <div class="inst-title">دائرة شؤون الطلاب والتعليم الفني والمهني</div>
            </td>
            <td style="width: 30%; text-align: left; vertical-align: top;">
                @if(!empty($dtcLogoBase64))
                    <img src="{{ $dtcLogoBase64 }}" style="width:48px; height:48px;" alt="DTC" />
                @endif
                <div style="font-size: 7.5pt; color: #64748b; margin-top: 3px;">
                    رقم الوثيقة: #REP-{{ $report->report_id ?? ($report->id ?? '1') }}<br>
                    التاريخ: {{ \Carbon\Carbon::parse($report->generated_at ?? ($report->created_at ?? now()))->format('Y/m/d H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- عنوان التقرير -->
    <div class="report-title-box">
        <h2>{{ $reportTypeLabel }}</h2>
        <p>وثيقة تقييم ومتابعة أداء معتمدة صادرة عن رئاسة القسم الأكاديمي</p>
    </div>

    <!-- بطاقة الطالب -->
    <table class="info-table">
        <tr>
            <td class="lbl">اسم الطالب:</td>
            <td class="val">{{ $report->student_name }}</td>
            <td class="lbl">الرقم الأكاديمي:</td>
            <td class="val">{{ $report->student_code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">القسم الأكاديمي:</td>
            <td class="val">{{ $report->student_department ?? 'معهد دمشق المتوسط' }}</td>
            <td class="lbl">المدرب المشرف:</td>
            <td class="val">{{ $report->teacher_name ?? '—' }}</td>
        </tr>
        @if($isAcademic)
        <tr>
            <td class="lbl">نسبة الحضور:</td>
            <td class="val">{{ number_format($report->attendance_rate ?? 0, 0) }}%</td>
            <td class="lbl">المعدل الدراسي:</td>
            <td class="val">{{ number_format($report->average_grade ?? 0, 1) }}</td>
        </tr>
        @endif
    </table>

    <!-- تقييم وملاحظات المدرب -->
    <table class="section-table">
        <tr>
            <td class="section-header-teacher">
                ملاحظات وتقييم المدرب: {{ $report->teacher_name ?? 'المدرب المشرف' }}
            </td>
        </tr>
        <tr>
            <td class="section-body">
                {!! nl2br(e($report->recommendations ?: ($report->notes ?: 'لا توجد ملاحظات مسجلة من قبل المدرب.'))) !!}
            </td>
        </tr>
    </table>

    <!-- رأي وتوجيهات رئيس القسم -->
    @if(!empty($report->hod_notes))
    <table class="section-table">
        <tr>
            <td class="section-header-hod">
                رأي وتوجيهات رئيس القسم الأكاديمي
            </td>
        </tr>
        <tr>
            <td class="section-body">
                {!! nl2br(e($report->hod_notes)) !!}
            </td>
        </tr>
    </table>
    @endif

    <!-- الفوتر الرسمي -->
    <table class="footer-table">
        <tr>
            <td style="text-align: right;">
                نظام إدارة المعاهد المهنية والتقنية - EduBridge
            </td>
            <td style="text-align: left;">
                تم استخراج الوثيقة إلكترونياً من قبل رئيس القسم بتاريخ {{ now()->format('Y/m/d') }}
            </td>
        </tr>
    </table>

</body>
</html>
