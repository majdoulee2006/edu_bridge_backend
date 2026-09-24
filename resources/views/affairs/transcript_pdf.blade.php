<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف درجات وسجل أكاديمي موحد معتمد - {{ $student->full_name }}</title>
    <style>
        @page {
            margin: 6mm 7mm 6mm 7mm;
        }
        body {
            font-family: 'XB Riyaz', 'Cairo', sans-serif;
            direction: rtl;
            text-align: right;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        table {
            border-collapse: collapse;
        }
        /* Institutional Header */
        .header-table {
            width: 100%;
            margin-bottom: 5px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .inst-title {
            font-size: 11.5px;
            font-weight: bold;
            color: #b45309;
        }
        .doc-title {
            font-size: 13.5px;
            font-weight: 900;
            color: #0f172a;
            text-align: center;
            letter-spacing: -0.2px;
        }
        .doc-subtitle {
            font-size: 9px;
            font-weight: bold;
            color: #92400e;
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            padding: 1px 8px;
            border-radius: 10px;
            text-align: center;
            display: inline-block;
            margin-top: 2px;
        }
        .meta-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 4px 6px;
            font-size: 9px;
            border-radius: 4px;
            line-height: 1.35;
        }
        .barcode-box {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 1px 4px;
            margin-top: 3px;
            text-align: center;
            border-radius: 3px;
        }

        /* Student Identity Card */
        .student-card {
            width: 100%;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 6px;
            margin-bottom: 5px;
            padding: 4px 6px;
        }
        .student-header-table {
            width: 100%;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 3px;
        }
        .student-metrics-table {
            width: 100%;
        }
        .metric-cell {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 3px 5px;
            width: 25%;
        }
        .metric-label {
            font-size: 8px;
            color: #64748b;
            font-weight: bold;
            display: block;
        }
        .metric-val {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            display: block;
        }

        /* Section Title */
        .section-bar {
            width: 100%;
            margin-bottom: 3px;
        }
        .section-title {
            font-size: 10px;
            font-weight: 900;
            color: #1e293b;
        }
        .section-scale {
            font-size: 8.5px;
            color: #64748b;
            font-weight: 600;
            text-align: left;
        }

        /* Grades Table */
        .grades-table {
            width: 100%;
            margin-bottom: 5px;
            font-size: 9px;
        }
        .grades-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #334155;
            font-size: 8.5px;
        }
        .grades-table td {
            padding: 3px 2px;
            text-align: center;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
        }
        .grades-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .grades-table .course-name {
            text-align: right;
            padding-right: 5px;
            font-weight: bold;
            color: #0f172a;
        }
        .grades-table .col-total {
            font-weight: 900;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .grades-table .col-points {
            font-weight: 900;
            background-color: #fef3c7;
            color: #92400e;
        }

        /* Status Badges */
        .badge-pass {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            border-radius: 3px;
            padding: 1px 3px;
            font-weight: bold;
            font-size: 8px;
        }
        .badge-fail {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 3px;
            padding: 1px 3px;
            font-weight: bold;
            font-size: 8px;
        }
        .badge-closed {
            background-color: #fef3c7;
            color: #854d0e;
            border: 1px solid #fde68a;
            border-radius: 3px;
            padding: 1px 3px;
            font-weight: bold;
            font-size: 8px;
        }
        .badge-past {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
            border-radius: 3px;
            padding: 1px 3px;
            font-weight: bold;
            font-size: 8px;
        }

        /* Progression & GPA Decision Box */
        .decision-card {
            width: 100%;
            border: 1.5px solid #f59e0b;
            background-color: #fffbeb;
            border-radius: 6px;
            padding: 5px 6px;
            margin-bottom: 5px;
        }
        .gpa-pill {
            background-color: #fef08a;
            border: 1px solid #eab308;
            padding: 1px 5px;
            font-weight: 900;
            font-size: 11px;
            color: #0f172a;
            border-radius: 4px;
        }
        .decision-badge-box {
            background-color: #ffffff;
            border: 1px solid #86efac;
            border-radius: 5px;
            padding: 4px 6px;
            margin-bottom: 3px;
        }

        /* Signatures Table */
        .signatures-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-bottom: 4px;
        }
        .signatures-table td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            padding: 2px 4px;
        }
        .sig-pillar {
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 3px 2px;
            height: 52px;
        }
        .sig-pillar-stamp {
            border: 1px solid #cbd5e1;
            background-color: #fffdf5;
            border-radius: 4px;
            padding: 3px 2px;
            height: 52px;
        }
        .seal-circle {
            display: inline-block;
            border: 1.5px double #0284c7;
            border-radius: 50%;
            width: 36px;
            height: 24px;
            color: #0369a1;
            font-size: 6px;
            font-weight: 900;
            line-height: 11px;
            background-color: #f0f9ff;
            margin: 1px auto;
        }

        /* Archival Legal Footer */
        .legal-box {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 8px;
            color: #475569;
            text-align: center;
            margin-bottom: 3px;
            line-height: 1.25;
        }
        .system-meta-table {
            width: 100%;
            font-size: 7.5px;
            color: #64748b;
            font-family: monospace;
        }
    </style>
</head>
<body>

    <!-- Official Institutional Header (نفس ترويسة الشؤون) -->
    <table class="header-table">
        <tr>
            <!-- Right: Hierarchy -->
            <td style="width: 33%; text-align: right; line-height: 1.35;">
                <div style="font-weight: 800; font-size: 10px; color: #0f172a;">الجمهورية العربية السورية</div>
                <div style="font-size: 8.5px; color: #475569;">وكالة الأمم المتحدة لإغاثة وتشغيل اللاجئين (UNRWA)</div>
                <div style="font-size: 8.5px; color: #475569;">دائرة التربية والتعليم المهني والتقني - دمشق</div>
                <div class="inst-title">معهد دمشق المتوسط (DTC)</div>
                <div style="font-size: 8px; color: #64748b;">شؤون الطلاب والامتحانات والكنترول الأكاديمي</div>
            </td>

            <!-- Center: Logo & Main Title -->
            <td style="width: 37%; text-align: center;">
                @if(!empty($dtcLogoBase64))
                    <div style="text-align: center; margin-bottom: 2px;">
                        <img src="{{ $dtcLogoBase64 }}" style="width: 42px; height: 42px; border-radius: 50%;" alt="DTC Logo" />
                    </div>
                @endif
                <div class="doc-title">كشف درجات وسجل أكاديمي موحد معتمد</div>
                <div class="doc-subtitle">العام الدراسي {{ $academicYearLabel ?? '2024 - 2025' }} | الدورة الرئيسية</div>
            </td>

            <!-- Left: Verification Meta & Barcode -->
            <td style="width: 30%; text-align: left;">
                <div class="meta-card" style="display: inline-block; text-align: right; width: 165px;">
                    <div><span style="color: #64748b;">رقم السجل:</span> <strong style="font-family: monospace; color: #0f172a;">DTC-{{ $student->student_code ?? '2026' }}-REG</strong></div>
                    <div><span style="color: #64748b;">تاريخ التوليد:</span> <span style="color: #0f172a; font-weight: bold;">{{ $issueDateFormatted ?? now()->format('d-m-Y') }}</span></div>
                    <div><span style="color: #64748b;">حالة الوثيقة:</span> <span style="background-color: #dcfce7; color: #166534; font-weight: bold; padding: 0.5px 3px; border-radius: 2px; font-size: 8px;">معتمدة رسمياً ✓</span></div>
                    <div class="barcode-box">
                        <div style="font-family: monospace; font-size: 7.5px; letter-spacing: 1px; color: #1e293b; font-weight: bold;">*DTC-VERIFIED-{{ $student->student_code ?? '2026' }}*</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Student Academic Identity Card (بطاقة الهوية الأكاديمية للطالب) -->
    <div class="student-card">
        <table class="student-header-table">
            <tr>
                <td style="text-align: right; vertical-align: middle;">
                    <div style="display: inline-block; vertical-align: middle; background-color: #eab308; color: #0f172a; font-weight: 900; font-size: 10px; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 50%; border: 1px solid #ca8a04; margin-left: 5px;">
                        {{ $studentInitials ?? 'ط' }}
                    </div>
                    <div style="display: inline-block; vertical-align: middle;">
                        <span style="font-size: 11.5px; font-weight: 900; color: #0f172a;">{{ $student->full_name }}</span>
                        <span style="font-size: 9px; color: #64748b; margin-right: 6px;">
                            الرقم الجامعي الأكاديمي: <strong style="font-family: monospace; color: #0f172a;">{{ $student->student_code ?? '-' }}</strong>
                            | تاريخ الالتحاق: <span style="font-family: monospace;">{{ $joinedAtFormatted ?? '10-09-2024' }}</span>
                        </span>
                    </div>
                </td>
                <td style="text-align: left; vertical-align: middle;">
                    <span style="font-size: 8.5px; font-weight: bold; color: #475569; background-color: #ffffff; border: 1px solid #cbd5e1; padding: 2px 6px; border-radius: 4px;">
                        نطاق التقرير: كافة الفصول ({{ $student->level }})
                    </span>
                </td>
            </tr>
        </table>

        <!-- 4 Structured Metric Boxes -->
        <table class="student-metrics-table">
            <tr>
                <td class="metric-cell" style="padding-right: 0;">
                    <span class="metric-label">القسم الأكاديمي العام</span>
                    <span class="metric-val">{{ $student->department_name ?? 'عام' }}</span>
                </td>
                <td class="metric-cell">
                    <span class="metric-label">الفرع / التخصص الدقيق</span>
                    <span class="metric-val" style="color: #b45309;">{{ $student->program_name ?? 'عام' }}</span>
                </td>
                <td class="metric-cell">
                    <span class="metric-label">السنة الدراسية / المستوى</span>
                    <span class="metric-val">{{ $student->level }}</span>
                </td>
                <td class="metric-cell" style="padding-left: 0;">
                    <span class="metric-label">الخطة الدراسية المعتمدة</span>
                    <span class="metric-val" style="font-family: monospace;">{{ $planCode }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Section Bar -->
    <table class="section-bar">
        <tr>
            <td style="text-align: right;">
                <span class="section-title">■ جدول المقررات الدراسية وتثقيل الساعات وعلامات التقييم المعتمدة</span>
            </td>
            <td style="text-align: left;">
                <span class="section-scale">نظام القياس المعتمد: المئوي والنقاط الموزونة (Weighted Scale)</span>
            </td>
        </tr>
    </table>

    <!-- Grades Table (جدول الدرجات التفصيلي الموزون) -->
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 32%; text-align: right; padding-right: 5px;">اسم المقرر الدراسي</th>
                <th style="width: 9%;">الفصل</th>
                <th style="width: 6%;">الساعات</th>
                <th style="width: 7%;">المذاكرة (25)</th>
                <th style="width: 7%;">الامتحان (50)</th>
                <th style="width: 7%;">الشفهي (25)</th>
                <th style="width: 8%; background-color: #020617;">المجموع</th>
                <th style="width: 8%; background-color: #451a03; color: #fde047;">النقاط</th>
                <th style="width: 12%;">الحالة والتقدير</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coursesList as $idx => $c)
            <tr>
                <td style="font-family: monospace; color: #64748b;">{{ $idx + 1 }}</td>
                <td class="course-name">{{ $c['title'] }}</td>
                <td>{{ $c['sem_label'] }}</td>
                <td style="font-weight: bold;">{{ $c['weight'] }}</td>
                <td style="font-family: monospace;">{{ $c['quiz_score'] }}</td>
                <td style="font-family: monospace;">{{ $c['exam_score'] }}</td>
                <td style="font-family: monospace;">{{ $c['oral_score'] }}</td>
                <td class="col-total" style="font-family: monospace;">{{ $c['score'] }}</td>
                <td class="col-points" style="font-family: monospace;">{{ $c['points'] }}</td>
                <td>
                    @if($c['is_closed'])
                        <span class="badge-closed">مغلق</span>
                    @elseif($c['is_past'])
                        <span class="badge-past">مجتاز سابقاً</span>
                    @elseif($c['is_pass'])
                        <span class="badge-pass">{{ $c['status_label'] }}</span>
                    @else
                        <span class="badge-fail">راسب</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 1.5px solid #64748b;">
                <td colspan="3" style="text-align: right; padding-right: 6px; font-weight: 800; color: #0f172a;">
                    إجمالي الساعات المعتمدة والنقاط التراكمية:
                </td>
                <td style="font-weight: 900; font-family: monospace; color: #0f172a;">{{ $totalHours }}</td>
                <td colspan="4" style="color: #64748b; font-size: 8px; font-weight: normal;">مجموع الأوزان المعتمدة طبقاً للائحة الامتحانية</td>
                <td style="font-weight: 900; font-family: monospace; color: #92400e; background-color: #fef3c7;">{{ number_format($totalPoints, 1) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Progression Decision & GPA Summary Box (بطاقة القرار والمعدلات) -->
    <div class="decision-card">
        <table style="width: 100%;">
            <tr>
                <!-- Left: GPA Metrics -->
                <td style="width: 42%; vertical-align: top; border-left: 1px solid #fde68a; padding-left: 6px; line-height: 1.45;">
                    @if($isYear1)
                        <div><span style="color: #475569;">معدل الفصل الأول:</span> <strong style="font-family: monospace; color: #0f172a;">{{ number_format($currentSemGpa, 2) }}%</strong></div>
                        <div><span style="color: #475569;">معدل الفصل الثاني:</span> <span style="color: #b45309; font-size: 8px;">مغلق لحين انتهاء الفصل الأول</span></div>
                    @else
                        <div><span style="color: #475569;">الفصل الأول والثاني:</span> <strong style="color: #1d4ed8; font-size: 8px;">مجتاز بنجاح من العام الماضي</strong></div>
                        <div><span style="color: #475569;">معدل الفصل الثالث:</span> <strong style="font-family: monospace; color: #0f172a;">{{ number_format($currentSemGpa, 2) }}%</strong></div>
                    @endif
                    <div style="margin-top: 2px; padding-top: 2px; border-top: 1px dashed #fde68a;">
                        <span style="font-weight: 900; color: #451a03;">المعدل التراكمي العام (GPA):</span>
                        <span class="gpa-pill">{{ $cumulativeStats['cum_gpa'] }}%</span>
                    </div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 1px;">
                        المعادلة: الفصول المجتازة ({{ $passedSemCount }}) | التقدير: <strong style="color: #15803d; background-color: #dcfce7; padding: 0.5px 3px; border-radius: 2px;">{{ $cumulativeStats['appreciation'] }}</strong>
                    </div>
                </td>

                <!-- Right: Decision & Directives -->
                <td style="width: 58%; vertical-align: top; padding-right: 6px;">
                    <div class="decision-badge-box">
                        <div style="font-size: 8px; font-weight: bold; color: #15803d; text-transform: uppercase;">
                            ✓ القرار الأكاديمي والإداري الرسمي النهائي:
                        </div>
                        <div style="font-size: 11px; font-weight: 900; color: #0f172a; margin-top: 1px;">
                            {{ $decisionTitle }}
                        </div>
                    </div>
                    <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 3px 5px; font-size: 8px; color: #475569; line-height: 1.3;">
                        <strong style="color: #0f172a;">توجيهات لجنة الامتحانات والكنترول:</strong>
                        {{ $directivesText }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Official Approvals, Signatures & Stamp (أعمدة الاعتماد الثلاثة والختم الحي) -->
    <table class="signatures-table">
        <tr>
            <!-- Pillar 1: Student Affairs Auditor -->
            <td style="padding-right: 0;">
                <div class="sig-pillar">
                    <div style="font-weight: 800; font-size: 9px; color: #0f172a;">تدقيق ومطابقة شؤون الطلاب</div>
                    <div style="margin: 8px auto 4px auto; width: 65%; border-bottom: 1px dotted #94a3b8;"></div>
                    <div style="font-size: 7.5px; color: #64748b;">التوقيع والتاريخ: ........................</div>
                </div>
            </td>

            <!-- Pillar 2: Head of Department -->
            <td>
                <div class="sig-pillar">
                    <div style="font-weight: 800; font-size: 9px; color: #0f172a;">رئيس قسم {{ $student->department_name ?? 'القسم الأكاديمي' }}</div>
                    <div style="margin: 8px auto 4px auto; width: 65%; border-bottom: 1px dotted #94a3b8;"></div>
                    <div style="font-size: 7.5px; color: #64748b;">الاعتماد الأكاديمي والتوقيع: ............</div>
                </div>
            </td>

            <!-- Pillar 3: Institute Director & Stamp -->
            <td style="padding-left: 0;">
                <div class="sig-pillar-stamp">
                    <div style="font-weight: 900; font-size: 9px; color: #0f172a;">مدير معهد دمشق المتوسط (DTC)</div>
                    <div class="seal-circle">
                        خاتم رسمي<br>
                        DTC-UNRWA
                    </div>
                    <div style="font-size: 7px; color: #475569; font-weight: bold;">التوقيع والخاتم الرسمي المعتمد</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Legal Warning & Archival Footer (التنبيه القانوني الرسمي وهوية المنظومة) -->
    <div class="legal-box">
        <strong style="color: #be123c;">تنبيه قانوني رسمي:</strong>
        تعتبر هذه الوثيقة لاغية وفاقدة لأي أثر أو حجية نظامية في حال وجود أي كشط أو شطب أو تعديل، أو خلوها من التوقيع الحي والخاتم الحي المعتمد لإدارة معهد دمشق المتوسط (DTC).
    </div>

    <table class="system-meta-table">
        <tr>
            <td style="text-align: right;">المنظومة الإلكترونية: EduBridge Academic Management System (v4.8)</td>
            <td style="text-align: center;">رمز التحقق المشفر: {{ $verificationHash ?? 'DTC-SEC-VERIFIED' }}</td>
            <td style="text-align: left; font-weight: bold; color: #0f172a;">صفحة 1 من 1</td>
        </tr>
    </table>

</body>
</html>
