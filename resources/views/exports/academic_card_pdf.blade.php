<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحميل كشف العلامات - {{ $student['full_name'] ?? 'الطالب' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- html2pdf.js for Direct Client-side PDF Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background-color: #0f172a;
            color: #f8fafc;
            direction: rtl;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        /* Download Status Screen */
        .loading-screen {
            text-align: center;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .spinner-icon {
            font-size: 3rem;
            color: #facc15;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }

        @keyframes spin {
            100% { transform: rotate(-360deg); }
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: #ffffff;
        }

        .status-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .btn-manual-download {
            background: #facc15;
            color: #000;
            border: none;
            padding: 0.75rem 1.8rem;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-manual-download:hover {
            background: #eab308;
            transform: translateY(-2px);
        }

        /* Document Container (Rendered off-screen for PDF capture) */
        .pdf-render-wrapper {
            position: absolute;
            left: -9999px;
            top: -9999px;
            width: 800px;
        }

        .document-card {
            background: #ffffff;
            color: #0f172a;
            width: 800px;
            padding: 2.5rem;
            border-radius: 0;
            position: relative;
        }

        .doc-header {
            text-align: center;
            border-bottom: 3px double #facc15;
            padding-bottom: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .brand-title {
            font-size: 1.8rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 0.2rem;
        }

        .brand-title span {
            color: #ca8a04;
        }

        .doc-subtitle {
            font-size: 1.15rem;
            font-weight: 800;
            color: #475569;
            margin-top: 0.2rem;
        }

        .doc-date {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 0.3rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.2rem;
        }

        .info-item {
            font-size: 0.9rem;
        }

        .info-item strong {
            color: #334155;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.6rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .stat-box {
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 8px;
            padding: 0.6rem;
        }

        .stat-val {
            font-size: 1.3rem;
            font-weight: 900;
            color: #ca8a04;
        }

        .stat-lbl {
            font-size: 0.75rem;
            color: #71717a;
            font-weight: 700;
            margin-top: 0.1rem;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .grades-table th, .grades-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: center;
        }

        .grades-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .grades-table td {
            font-size: 0.85rem;
        }

        .course-title-td {
            text-align: right !important;
            font-weight: 700;
        }

        .status-pass { color: #16a34a; font-weight: 800; }
        .status-fail { color: #dc2626; font-weight: 800; }
        .status-pending { color: #ca8a04; font-weight: 700; }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.2rem;
            border-top: 1px solid #e2e8f0;
        }

        .sig-box {
            font-size: 0.8rem;
            color: #475569;
        }

        .sig-box strong {
            display: block;
            margin-bottom: 2rem;
            color: #0f172a;
        }

        .stamp-circle {
            width: 65px;
            height: 65px;
            border: 2px dashed #94a3b8;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 0.7rem;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <!-- On-screen Status View for User -->
    <div class="loading-screen">
        <i class="fa-solid fa-circle-notch spinner-icon" id="status-icon"></i>
        <div class="status-title" id="status-title">جاري تحميل ملف PDF...</div>
        <div class="status-desc" id="status-desc">سيتم تنزيل كشف العلامات تلقائياً وإغلاق هذه النافذة.</div>
        
        <button class="btn-manual-download" id="btn-download" onclick="generateAndSavePDF()">
            <i class="fa-solid fa-download"></i> اضغط هنا إذا لم يبدأ التحميل تلقائياً
        </button>
    </div>

    <!-- Hidden HTML Canvas Target for PDF Generation -->
    <div class="pdf-render-wrapper">
        <div class="document-card" id="pdf-target">
            <div class="doc-header">
                <h1 class="brand-title">معهد <span>Edu Bridge</span> التعليمي</h1>
                <div class="doc-subtitle">بطاقة الطالب الأكاديمية - كشف العلامات</div>
                <div class="doc-date">تاريخ الإصدار: {{ date('Y-m-d H:i') }}</div>
            </div>

            <!-- Student Info -->
            <div class="info-grid">
                <div class="info-item"><strong>اسم الطالب:</strong> {{ $student['full_name'] ?? '—' }}</div>
                <div class="info-item"><strong>الرقم الجامعي:</strong> {{ $student['university_id'] ?? '—' }}</div>
                <div class="info-item"><strong>التخصص / القسم:</strong> {{ $student['department'] ?? '—' }}</div>
                <div class="info-item"><strong>السنة الدراسية:</strong> {{ $student['level'] ?? '—' }}</div>
            </div>

            <!-- Summary Statistics -->
            <div class="summary-grid">
                <div class="stat-box">
                    <div class="stat-val">{{ $summary['average'] ?? 0 }}%</div>
                    <div class="stat-lbl">المعدل التراكمي</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val" style="color: #16a34a;">{{ $summary['passed_courses'] ?? 0 }}</div>
                    <div class="stat-lbl">المواد المجتازة</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val" style="color: #dc2626;">{{ $summary['failed_courses'] ?? 0 }}</div>
                    <div class="stat-lbl">المواد الراسبة</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val" style="color: #64748b;">{{ $summary['not_attended'] ?? 0 }}</div>
                    <div class="stat-lbl">لم يتم التقدم لها</div>
                </div>
            </div>

            <!-- Grades Table -->
            <table class="grades-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">اسم المادة الدراسية</th>
                        <th style="width: 15%;">السنة / الفصل</th>
                        <th style="width: 10%;">المذاكرة (25)</th>
                        <th style="width: 10%;">الشفهي (25)</th>
                        <th style="width: 10%;">النهائي (50)</th>
                        <th style="width: 10%;">المجموع (100)</th>
                        <th style="width: 10%;">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicCard as $idx => $c)
                        @php
                            $statusClass = 'status-pending';
                            if (($c['status'] ?? '') === 'ناجح') $statusClass = 'status-pass';
                            elseif (($c['status'] ?? '') === 'راسب') $statusClass = 'status-fail';
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="course-title-td">{{ $c['title'] ?? '—' }}</td>
                            <td>سنة {{ $c['year'] ?? 1 }} - فصل {{ $c['semester'] ?? 1 }}</td>
                            <td>{{ $c['quiz_score'] !== null ? $c['quiz_score'] : '-' }}</td>
                            <td>{{ $c['oral_score'] !== null ? $c['oral_score'] : '-' }}</td>
                            <td>{{ $c['final_score'] !== null ? $c['final_score'] : '-' }}</td>
                            <td><strong>{{ $c['total_score'] !== null ? $c['total_score'] : '-' }}</strong></td>
                            <td class="{{ $statusClass }}">{{ $c['status'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 2rem; color: #94a3b8;">لا توجد سجلات علامات مدمجة حتى الآن</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Signatures and Stamps -->
            <div class="signatures-grid">
                <div class="sig-box">
                    <strong>أخصائي الشؤون الطلابية</strong>
                    ...................................
                </div>
                <div class="sig-box">
                    <strong>الختم الرسمي للمعهد</strong>
                    <div class="stamp-circle">الختم</div>
                </div>
                <div class="sig-box">
                    <strong>إدارة معهد Edu Bridge</strong>
                    ...................................
                </div>
            </div>

        </div>
    </div>

    <!-- Direct PDF Generation Script -->
    <script>
        function generateAndSavePDF() {
            const element = document.getElementById('pdf-target');
            const studentId = "{{ $student['university_id'] ?? $student['student_id'] ?? 'transcript' }}";
            const filename = `academic_card_${studentId}.pdf`;

            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                document.getElementById('status-icon').className = 'fa-solid fa-circle-check';
                document.getElementById('status-icon').style.color = '#16a34a';
                document.getElementById('status-title').innerText = 'تم التنزيل بنجاح!';
                document.getElementById('status-desc').innerText = 'تم حفظ كشف العلامات في مجلد التنزيلات لديك.';
                
                setTimeout(() => {
                    window.close();
                }, 1500);
            }).catch(err => {
                console.error('PDF Generation error:', err);
                document.getElementById('status-icon').className = 'fa-solid fa-circle-xmark';
                document.getElementById('status-icon').style.color = '#dc2626';
                document.getElementById('status-title').innerText = 'حدث خطأ أثناء التنزيل';
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
            // Wait for Google Fonts to be ready for 100% crisp text
            if (document.fonts) {
                document.fonts.ready.then(() => {
                    setTimeout(generateAndSavePDF, 400);
                });
            } else {
                setTimeout(generateAndSavePDF, 800);
            }
        });
    </script>
</body>
</html>
