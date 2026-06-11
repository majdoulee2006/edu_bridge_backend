@extends('layouts.teacher')
@section('title', 'تسجيل الحضور والغياب')

@push('styles')
<style>
    .session-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; box-shadow: var(--shadow); margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .select-field { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; }
    .select-field:focus { outline: none; border-color: var(--accent-color); }
    
    .action-btn { padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; border: none; font-family: inherit; }
    .btn-qr { background: #eff6ff; color: #1d4ed8; }
    .btn-export { background: #f0fdf4; color: #166534; }
    .btn-absentees { background: #fef2f2; color: #b91c1c; }

    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); position: relative; max-height: 90vh; overflow-y: auto; }
    .close-btn { position: absolute; top: 1.5rem; left: 1.5rem; background: none; border: none; font-size: 1.2rem; color: var(--text-secondary); cursor: pointer; }
    .close-btn:hover { color: var(--text-primary); }

    .absentee-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-bottom: 1px solid var(--border-color); }
    .absentee-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">

        <!-- Start Session Panel -->
        <div>
            <div style="background: var(--bg-secondary); border-radius: 1.5rem; padding: 1.75rem; box-shadow: var(--shadow);">
                <h3 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.1rem;">
                    <i class="fa-solid fa-play-circle" style="color: var(--accent-color);"></i>
                    بدء جلسة حضور جديدة
                </h3>

                <form action="{{ route('teacher.attendance.store') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">المادة الدراسية</label>
                        <select name="course_id" id="course_select" class="select-field" required onchange="showCourseLevel()">
                            <option value="">← اختر المادة</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->course_id }}" data-level="{{ $c->level ?? 'عام' }}">{{ $c->title }}</option>
                            @endforeach
                        </select>
                        <div id="course_level_hint" style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.5rem; display: none;">
                            <i class="fa-solid fa-info-circle"></i> هذه المادة مخصصة لـ: <span id="level_text" style="font-weight: 700; color: var(--accent-color);"></span>
                        </div>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">القاعة / الصف</label>
                        <input type="text" name="room" class="select-field" placeholder="مثال: قاعة 302">
                    </div>
                    <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 800; cursor: pointer; font-family: inherit;">
                        <i class="fa-solid fa-qrcode"></i> توليد QR Code وبدء الجلسة
                    </button>
                    <p style="font-size: 0.75rem; color: var(--text-secondary); text-align: center; margin-top: 0.75rem;">
                        ستكون الجلسة صالحة لمدة 10 دقائق
                    </p>
                </form>
            </div>
        </div>

        <!-- Recent Sessions -->
        <div>
            <h3 style="font-weight: 800; margin-bottom: 1rem; font-size: 1.1rem;">
                <i class="fa-solid fa-history" style="color: var(--accent-color);"></i>
                إدارة الجلسات
            </h3>

            @forelse($recentSessions as $session)
                <div class="session-card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; border-radius: 1rem; background: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #1a1a1a; flex-shrink: 0;">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                {{ $session->course_title }}
                                @if($session->is_active)
                                    <span style="background: hsl(120,70%,90%); color: hsl(120,50%,30%); padding: 0.1rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 700;">مفتوحة</span>
                                @else
                                    <span style="background: var(--bg-primary); color: var(--text-secondary); padding: 0.1rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 700;">مغلقة</span>
                                @endif
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                <i class="fa-solid fa-calendar"></i> {{ \Carbon\Carbon::parse($session->created_at)->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        @if($session->is_active)
                            <button class="action-btn btn-qr" onclick="showQRModal('{{ $session->qr_token }}')">
                                <i class="fa-solid fa-qrcode"></i> عرض QR
                            </button>
                            
                            <form action="{{ route('teacher.attendance.end', $session->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="action-btn" style="background: #fef2f2; color: #b91c1c;" onclick="return confirm('هل أنت متأكد من إيقاف جلسة الحضور هذه؟ لن يتمكن الطلاب من تسجيل حضورهم بعدها.')">
                                    <i class="fa-solid fa-stop"></i> إيقاف الجلسة
                                </button>
                            </form>
                        @endif
                        
                        <button class="action-btn btn-absentees" onclick="showAbsenteesModal('{{ $session->id }}')">
                            <i class="fa-solid fa-users-slash"></i> الغائبين
                        </button>

                        <a href="{{ route('teacher.attendance.export', $session->id) }}" class="action-btn btn-export">
                            <i class="fa-solid fa-file-excel"></i> تصدير إكسيل
                        </a>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>
                    لا توجد جلسات حضور سابقة
                </div>
            @endforelse
        </div>
    </div>

    <!-- Advanced Filter & Export Section -->
    <div style="margin-top: 2rem; background: var(--bg-secondary); border-radius: 1.5rem; padding: 1.75rem; box-shadow: var(--shadow);">
        <h3 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--text-primary);">
            <i class="fa-solid fa-filter" style="color: var(--accent-color);"></i>
            فلترة متقدمة وتصدير شامل
        </h3>
        <form action="{{ route('teacher.attendance.filtered_export') }}" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
            
            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">نطاق التقرير</label>
                <select name="scope" class="select-field" id="report_scope" onchange="toggleCourseOptions()">
                    <option value="my_courses">المواد الخاصة بي</option>
                    @if($isAdvisor)
                        <option value="advisor_class">كافة مواد دورتي الإشرافية (مربي الدورة)</option>
                    @endif
                </select>
            </div>

            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">المادة (اختياري)</label>
                <select name="course_id" class="select-field" id="report_course">
                    <option value="">جميع المواد</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->course_id }}" class="my-course-option">{{ $c->title }}</option>
                    @endforeach
                </select>
                <small id="advisor_hint" style="display:none; color: var(--text-secondary); margin-top: 5px;">في حال بقاء (جميع المواد)، سيتم تصدير الحضور لكافة مواد الفرع والسنة الخاصة بك كمربي.</small>
            </div>

            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">الفترة الزمنية</label>
                <select name="period" class="select-field">
                    <option value="today">اليوم</option>
                    <option value="week">هذا الأسبوع</option>
                    <option value="semester">منذ بداية الفصل</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="export_type" value="excel" style="flex: 1; padding: 0.9rem; background: #166534; color: #fff; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 800; cursor: pointer; font-family: inherit;">
                    <i class="fa-solid fa-file-excel"></i> إكسيل
                </button>
                <button type="submit" name="export_type" value="pdf" style="flex: 1; padding: 0.9rem; background: #b91c1c; color: #fff; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 800; cursor: pointer; font-family: inherit;">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </button>
            </div>
        </form>
    </div>

    <!-- QR Code Modal -->
    <div id="qr-modal" class="modal-overlay">
        <div class="modal-card" style="text-align: center;">
            <button class="close-btn" onclick="closeModal('qr-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.2rem;">مسح رمز الحضور</h3>
            
            <div style="background: #fff; padding: 1rem; border-radius: 1rem; display: inline-block; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                <div id="qrcode"></div>
            </div>
            
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                اطلب من الطلاب مسح هذا الرمز باستخدام تطبيق الهاتف لتسجيل حضورهم.
                <br><strong>الرمز صالح لمدة 10 دقائق فقط.</strong>
            </p>
        </div>
    </div>

    <!-- Absentees Modal -->
    <div id="absentees-modal" class="modal-overlay">
        <div class="modal-card">
            <button class="close-btn" onclick="closeModal('absentees-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 style="font-weight: 800; margin-bottom: 1.5rem; font-size: 1.2rem; color: #b91c1c;">
                <i class="fa-solid fa-users-slash"></i> قائمة الغائبين
            </h3>
            
            <div id="absentees-loader" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p style="margin-top: 1rem;">جاري جلب القائمة...</p>
            </div>

            <div id="absentees-list" style="display: none;">
                <!-- List will be populated here -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let qrcodeInstance = null;

    function showCourseLevel() {
        const select = document.getElementById('course_select');
        const hint = document.getElementById('course_level_hint');
        const levelText = document.getElementById('level_text');
        
        if (select.selectedIndex > 0) {
            const level = select.options[select.selectedIndex].getAttribute('data-level');
            levelText.innerText = level;
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }

    function toggleCourseOptions() {
        const scope = document.getElementById('report_scope').value;
        const hint = document.getElementById('advisor_hint');
        const courseSelect = document.getElementById('report_course');
        
        if (scope === 'advisor_class') {
            hint.style.display = 'block';
            courseSelect.querySelectorAll('.my-course-option').forEach(el => el.style.display = 'none');
            courseSelect.value = "";
        } else {
            hint.style.display = 'none';
            courseSelect.querySelectorAll('.my-course-option').forEach(el => el.style.display = 'block');
        }
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    function showQRModal(token) {
        const qrContainer = document.getElementById('qrcode');
        qrContainer.innerHTML = ''; // Clear previous
        
        const data = 'edu-bridge://attendance?token=' + token;
        
        qrcodeInstance = new QRCode(qrContainer, {
            text: data,
            width: 250,
            height: 250,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        
        openModal('qr-modal');
    }

    function showAbsenteesModal(sessionId) {
        openModal('absentees-modal');
        const loader = document.getElementById('absentees-loader');
        const list = document.getElementById('absentees-list');
        
        loader.style.display = 'block';
        list.style.display = 'none';
        list.innerHTML = '';

        fetch('{{ url("teacher/attendance/absentees") }}/' + sessionId)
            .then(res => res.json())
            .then(data => {
                loader.style.display = 'none';
                list.style.display = 'block';
                
                if (data.length === 0) {
                    list.innerHTML = '<div style="text-align: center; color: var(--text-secondary); padding: 1rem;">لا يوجد غائبين (جميع الطلاب حاضرون)</div>';
                    return;
                }

                data.forEach(student => {
                    list.innerHTML += `
                        <div class="absentee-item">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">${student.full_name}</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">${student.level || 'غير محدد'}</div>
                            </div>
                            <span style="background: #fef2f2; color: #b91c1c; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700;">غائب</span>
                        </div>
                    `;
                });
            })
            .catch(err => {
                loader.style.display = 'none';
                list.style.display = 'block';
                list.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 1rem;">حدث خطأ أثناء جلب البيانات.</div>';
            });
    }
</script>
@endpush
