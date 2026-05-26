@extends('layouts.affairs')
@section('title', 'التقويم')

@push('styles')
<style>
    /* Container */
    .calendar-container {
        max-width: 960px;
        margin: 2rem auto;
        background: var(--bg-secondary);
        padding: 2rem;
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
    }
    /* Header */
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
        flex-wrap: wrap;
        gap: 1rem;
    }
    .calendar-header h2 {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0;
    }
    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    .month-nav {
        display: flex;
        gap: 0.5rem;
    }
    .month-nav button, .add-event-btn {
        background: var(--accent-color);
        border: none;
        color: var(--primary-dark);
        padding: 0.6rem 1rem;
        border-radius: 0.8rem;
        cursor: pointer;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, opacity 0.2s;
    }
    .month-nav button {
        width: 40px;
        height: 40px;
        padding: 0;
    }
    .month-nav button:hover, .add-event-btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    .add-event-btn i {
        margin-left: 0.5rem;
    }
    /* Weekdays */
    .weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-weight: 800;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }
    .weekdays div { font-size: 0.9rem; }
    /* Days grid */
    .days-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.4rem;
        text-align: center;
    }
    .day {
        position: relative;
        padding: 1rem 0.5rem;
        background: var(--bg-primary);
        border-radius: 0.8rem;
        font-weight: 700;
        color: var(--text-primary);
        cursor: pointer;
        transition: background 0.2s, color 0.2s, transform 0.2s;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .day.empty {
        background: transparent;
        cursor: default;
    }
    .day:not(.empty):hover {
        background: var(--accent-color);
        color: var(--primary-dark);
        transform: translateY(-2px);
    }
    .day.today {
        background: var(--text-primary);
        color: var(--bg-primary);
    }
    .day.today:hover {
        background: var(--accent-color);
        color: var(--primary-dark);
    }
    .event-dot {
        width: 6px;
        height: 6px;
        background: #ef4444; /* Red dot for events */
        border-radius: 50%;
        margin-top: 0.3rem;
    }
    .day:hover .event-dot {
        background: var(--primary-dark);
    }
    
    /* Events list */
    .events-section { margin-top: 2rem; }
    .events-section h3 { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1rem; }
    .event-cards-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .event-card {
        background: var(--bg-primary);
        padding: 1.25rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s;
    }
    .event-card:hover { transform: translateY(-2px); }
    .event-date-box {
        background: var(--accent-color);
        color: var(--primary-dark);
        border-radius: 0.8rem;
        padding: 0.5rem 1rem;
        text-align: center;
        font-weight: 800;
        min-width: 75px;
    }
    .event-date-box .day-num { font-size: 1.4rem; line-height: 1.2; }
    .event-date-box .month-name { font-size: 0.8rem; opacity: 0.8; }
    .event-details { flex: 1; }
    .event-details h4 { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.3rem; }
    .event-details p { font-size: 0.9rem; color: var(--text-secondary); margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .event-details p i { color: var(--accent-color); }
    .event-icon {
        width: 40px;
        height: 40px;
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    /* Modal */
    .modal-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.6);
        display: none; align-items: center; justify-content: center;
        z-index: 1000; backdrop-filter: blur(4px);
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: var(--bg-secondary); padding: 2rem;
        border-radius: 1.5rem; width: 90%; max-width: 450px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .modal-header h3 { font-size: 1.4rem; font-weight: 800; color: var(--text-primary); margin: 0; }
    .close-modal { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-secondary); transition: color 0.2s; }
    .close-modal:hover { color: #ef4444; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--text-primary); font-size: 0.9rem; }
    .form-group input { width: 100%; padding: 0.8rem 1rem; border-radius: 0.8rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; }
    .form-group input:focus { outline: none; border-color: var(--accent-color); }
    .form-group input[readonly] { background: var(--bg-secondary); cursor: not-allowed; opacity: 0.7; }
    .submit-btn { width: 100%; padding: 1rem; background: var(--accent-color); color: var(--primary-dark); font-size: 1.1rem; font-weight: 800; border: none; border-radius: 0.8rem; cursor: pointer; margin-top: 1rem; transition: opacity 0.2s; }
    .submit-btn:hover { opacity: 0.9; }

    @keyframes tpSlide {
        from { transform: scale(0.92) translateY(15px); opacity: 0; }
        to   { transform: scale(1) translateY(0);       opacity: 1; }
    }
</style>
@endpush

@section('content')

{{-- Flash Messages --}}
@if(session('success'))
<div id="flashMsg" style="position:fixed; top:1.5rem; left:50%; transform:translateX(-50%); z-index:9999;
    background:#10b981; color:white; padding:0.9rem 2rem; border-radius:1rem;
    font-weight:700; font-size:0.95rem; box-shadow:0 8px 24px rgba(0,0,0,0.15);
    display:flex; align-items:center; gap:0.75rem; animation: slideDown 0.4s ease;">
    <i class="fa-solid fa-circle-check"></i>
    {{ session('success') }}
</div>
<style>
    @keyframes slideDown { from { opacity:0; transform:translateX(-50%) translateY(-20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
</style>
<script>setTimeout(() => { const m = document.getElementById('flashMsg'); if(m) m.style.display='none'; }, 3000);</script>
@endif

@if(session('error') || $errors->any())
<div id="flashErr" style="position:fixed; top:1.5rem; left:50%; transform:translateX(-50%); z-index:9999;
    background:#ef4444; color:white; padding:0.9rem 2rem; border-radius:1rem;
    font-weight:700; font-size:0.95rem; box-shadow:0 8px 24px rgba(0,0,0,0.15);
    display:flex; align-items:center; gap:0.75rem;">
    <i class="fa-solid fa-circle-xmark"></i>
    {{ session('error') ?? $errors->first() }}
</div>
<script>setTimeout(() => { const m = document.getElementById('flashErr'); if(m) m.style.display='none'; }, 4000);</script>
@endif

<div class="calendar-container">
    <div class="calendar-header">
        <h2 id="current-month-year"></h2>
        <div class="header-actions">
            <button class="add-event-btn" onclick="openEventModal()">
                <i class="fa-solid fa-plus"></i> إضافة حدث
            </button>
            <div class="month-nav">
                <button onclick="prevMonth()" title="الشهر السابق"><i class="fa-solid fa-chevron-right"></i></button>
                <button onclick="nextMonth()" title="الشهر التالي"><i class="fa-solid fa-chevron-left"></i></button>
            </div>
        </div>
    </div>
    
    <div class="weekdays">
        <div>الأحد</div><div>الإثنين</div><div>الثلاثاء</div><div>الأربعاء</div><div>الخميس</div><div>الجمعة</div><div>السبت</div>
    </div>
    
    <div class="days-grid" id="days-grid">
        <!-- يتم توليد الأيام هنا عبر JavaScript -->
    </div>
    
    <div class="events-section">
        <h3>أحداث الشهر</h3>
        <div class="event-cards-grid" id="events-list">
            <!-- يتم توليد الأحداث هنا عبر JavaScript -->
        </div>
    </div>
</div>

    <!-- Modal اضافة حدث -->
<div class="modal-overlay" id="eventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">إضافة حدث جديد</h3>
            <button class="close-modal" onclick="closeEventModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="addEventForm" method="POST" action="{{ route('affairs.calendar.store') }}">
            @csrf

            {{-- التاريخ — يطلع كالندر تلقائياً --}}
            <div class="form-group">
                <label><i class="fa-solid fa-calendar" style="color:var(--accent-color);margin-left:0.4rem;"></i>تاريخ الحدث</label>
                <input type="date" name="event_date" id="event-date" required
                       style="cursor:pointer;">
            </div>

            {{-- العنوان --}}
            <div class="form-group">
                <label><i class="fa-solid fa-pen" style="color:var(--accent-color);margin-left:0.4rem;"></i>عنوان الحدث</label>
                <input type="text" name="title" id="event-title" placeholder="أدخل عنوان الحدث" required>
            </div>

            {{-- الوقت — picker مخصص --}}
            <div class="form-group">
                <label><i class="fa-solid fa-clock" style="color:var(--accent-color);margin-left:0.4rem;"></i>وقت الحدث</label>

                {{-- الحقل المخفي الذي يُرسل للسيرفر --}}
                <input type="hidden" name="event_time" id="event-time-hidden">

                {{-- زر يفتح الـ picker --}}
                <div id="time-display-btn" onclick="openTimePicker()"
                     style="width:100%; padding:0.8rem 1rem; border-radius:0.8rem;
                            border:1px solid var(--border-color); background:var(--bg-primary);
                            color:var(--text-primary); font-family:inherit; font-size:0.95rem;
                            cursor:pointer; display:flex; align-items:center; justify-content:space-between;">
                    <span id="time-display-text" style="color:var(--text-secondary);">اختر الوقت</span>
                    <i class="fa-solid fa-clock" style="color:var(--accent-color);"></i>
                </div>
            </div>

            {{-- المكان --}}
            <div class="form-group">
                <label><i class="fa-solid fa-location-dot" style="color:var(--accent-color);margin-left:0.4rem;"></i>المكان</label>
                <input type="text" name="location" id="event-location" placeholder="أدخل مكان الحدث">
            </div>

            <button type="submit" class="submit-btn">حفظ الحدث</button>
        </form>
    </div>
</div>

{{-- ══════════════ TIME PICKER OVERLAY ══════════════ --}}
<div id="timePickerOverlay" onclick="if(event.target===this)closeTimePicker()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55);
            z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div style="background:var(--bg-secondary); border-radius:1.5rem; padding:2rem;
                width:340px; max-width:95vw; box-shadow:0 20px 50px rgba(0,0,0,0.3);
                animation:tpSlide 0.3s ease;">

        {{-- Header --}}
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="font-size:0.85rem; color:var(--text-secondary); font-weight:700; margin-bottom:0.3rem;">الوقت المختار</div>
            <div id="tp-preview"
                 style="font-size:3rem; font-weight:900; color:var(--text-primary); letter-spacing:2px; line-height:1;">
                12:00
            </div>
            {{-- AM / PM --}}
            <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:0.75rem;">
                <button onclick="setAmPm('AM')" id="btn-am"
                        style="padding:0.4rem 1.5rem; border-radius:2rem; border:2px solid var(--accent-color);
                               font-weight:800; cursor:pointer; transition:all 0.2s;
                               background:var(--accent-color); color:var(--primary-dark);">
                    صباحاً
                </button>
                <button onclick="setAmPm('PM')" id="btn-pm"
                        style="padding:0.4rem 1.5rem; border-radius:2rem; border:2px solid var(--border-color);
                               font-weight:800; cursor:pointer; transition:all 0.2s;
                               background:transparent; color:var(--text-secondary);">
                    مساءً
                </button>
            </div>
        </div>

        {{-- Hours --}}
        <div style="margin-bottom:1rem;">
            <div style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); margin-bottom:0.5rem; text-align:center;">الساعة</div>
            <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:0.4rem;" id="hours-grid">
                <!-- Generated by JS -->
            </div>
        </div>

        {{-- Minutes --}}
        <div style="margin-bottom:1.5rem;">
            <div style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); margin-bottom:0.5rem; text-align:center;">الدقائق</div>
            <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:0.4rem;" id="minutes-grid">
                <!-- Generated by JS -->
            </div>
        </div>

        {{-- Confirm --}}
        <button onclick="confirmTime()"
                style="width:100%; padding:0.9rem; background:var(--accent-color);
                       color:var(--primary-dark); border:none; border-radius:0.8rem;
                       font-weight:800; font-size:1rem; cursor:pointer; transition:opacity 0.2s;">
            <i class="fa-solid fa-check" style="margin-left:0.5rem;"></i>
            تأكيد الوقت
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // تهيئة التاريخ الحالي
    let currentDate = new Date();
    
    // قائمة الأحداث من قاعدة البيانات
    let events = [
        @foreach($events as $ev)
        { 
            id: {{ $ev->id }}, 
            date: '{{ $ev->event_date }}', 
            title: '{!! addslashes($ev->title) !!}', 
            time: '{{ substr($ev->event_time, 0, 5) }}', 
            location: '{!! addslashes($ev->location) !!}' 
        },
        @endforeach
    ];

    const monthNames = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];

    function formatTime(timeStr) {
        if(!timeStr) return '';
        let [hours, minutes] = timeStr.split(':');
        let h = parseInt(hours);
        let ampm = h >= 12 ? 'مساءً' : 'صباحاً';
        h = h % 12;
        h = h ? h : 12; 
        return `${h}:${minutes} ${ampm}`;
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        document.getElementById('current-month-year').innerText = `${monthNames[month]} ${year}`;
        
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        const daysGrid = document.getElementById('days-grid');
        daysGrid.innerHTML = '';
        
        // خلايا فارغة قبل بداية الشهر
        for(let i = 0; i < firstDay; i++) {
            daysGrid.innerHTML += `<div class="day empty"></div>`;
        }
        
        // أيام الشهر
        const today = new Date();
        for(let i = 1; i <= daysInMonth; i++) {
            let fullDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            
            // هل اليوم هو اليوم الحالي؟
            let isToday = (i === today.getDate() && month === today.getMonth() && year === today.getFullYear());
            
            // هل يوجد حدث في هذا اليوم؟
            let hasEvent = events.some(e => e.date === fullDateStr);
            
            daysGrid.innerHTML += `
                <div class="day ${isToday ? 'today' : ''}" onclick="openEventModal('${fullDateStr}')">
                    ${i}
                    ${hasEvent ? '<div class="event-dot"></div>' : ''}
                </div>
            `;
        }
        
        renderEvents();
    }

    function renderEvents() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        const eventsList = document.getElementById('events-list');
        eventsList.innerHTML = '';
        
        // فلترة أحداث الشهر الحالي
        const currentMonthEvents = events.filter(e => {
            const eDate = new Date(e.date + 'T00:00:00');
            return eDate.getMonth() === month && eDate.getFullYear() === year;
        });

        // ترتيب الأحداث حسب اليوم
        currentMonthEvents.sort((a, b) => new Date(a.date + 'T00:00:00') - new Date(b.date + 'T00:00:00'));
        
        if(currentMonthEvents.length === 0) {
            eventsList.innerHTML = `<div style="text-align:center; padding: 2rem; color: var(--text-secondary);">لا توجد أحداث في هذا الشهر.</div>`;
            return;
        }

        currentMonthEvents.forEach(e => {
            const eDate = new Date(e.date + 'T00:00:00');
            eventsList.innerHTML += `
                <div class="event-card">
                    <div class="event-date-box">
                        <div class="day-num">${eDate.getDate()}</div>
                        <div class="month-name">${monthNames[eDate.getMonth()]}</div>
                    </div>
                    <div class="event-details">
                        <h4>${e.title}</h4>
                        <p>
                            <i class="fa-regular fa-clock"></i> ${formatTime(e.time)} 
                            &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                            <i class="fa-solid fa-location-dot"></i> ${e.location}
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <button onclick="editEvent(${e.id})" style="background: var(--bg-secondary); border: none; color: #3b82f6; cursor: pointer; font-size: 1.1rem; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" title="تعديل" onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='var(--bg-secondary)'"><i class="fa-solid fa-pen-to-square"></i></button>
                        <form method="POST" action="/affairs/calendar/events/delete/${e.id}" style="margin: 0; display: inline;">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit" style="background: var(--bg-secondary); border: none; color: #ef4444; cursor: pointer; font-size: 1.1rem; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الحدث؟')" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='var(--bg-secondary)'"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                    </div>
                </div>
            `;
        });
    }

    function prevMonth() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    }

    function nextMonth() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    }

    // Modal Functions
    function openEventModal(dateStr = null) {
        const modal = document.getElementById('eventModal');
        const dateInput = document.getElementById('event-date');
        
        document.getElementById('addEventForm').reset();
        document.getElementById('addEventForm').action = "{{ route('affairs.calendar.store') }}";
        document.getElementById('modal-title').innerText = "إضافة حدث جديد";

        if(dateStr) {
            dateInput.value = dateStr;
            dateInput.readOnly = true;
        } else {
            // إضافة حدث جديد بشكل حر
            dateInput.value = '';
            dateInput.readOnly = false;
        }
        
        modal.classList.add('active');
    }

    function editEvent(id) {
        const event = events.find(e => e.id === id);
        if(!event) return;

        document.getElementById('event-date').value = event.date;
        document.getElementById('event-date').readOnly = false;
        document.getElementById('event-title').value = event.title;
        document.getElementById('event-location').value = event.location || '';

        // تعيين الوقت في الـ picker
        if(event.time) {
            setTimeFromStr(event.time);
        }

        document.getElementById('addEventForm').action = "/affairs/calendar/events/update/" + id;
        document.getElementById('modal-title').innerText = "تعديل الحدث";

        const modal = document.getElementById('eventModal');
        modal.classList.add('active');
    }

    function closeEventModal() {
        const modal = document.getElementById('eventModal');
        modal.classList.remove('active');
        document.getElementById('addEventForm').reset();
    }

    // إغلاق النافذة المنبثقة عند النقر خارجها
    document.getElementById('eventModal').addEventListener('click', function(e) {
        if(e.target === this) closeEventModal();
    });

    // رسم التقويم لأول مرة عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', () => {
        renderCalendar();
        initTimePicker();
    });

    // ══════════════ TIME PICKER LOGIC ══════════════
    let tp_hour   = 12;
    let tp_minute = 0;
    let tp_ampm   = 'AM';

    function initTimePicker() {
        // ── بناء شبكة الساعات 1-12 ──
        const hGrid = document.getElementById('hours-grid');
        for(let h = 1; h <= 12; h++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = h;
            btn.id = 'h-' + h;
            btn.style.cssText = `padding:0.45rem 0; border-radius:0.5rem; border:1px solid var(--border-color);
                background:var(--bg-primary); color:var(--text-primary); font-weight:700;
                cursor:pointer; transition:all 0.15s; font-size:0.9rem;`;
            btn.onclick = () => selectHour(h);
            hGrid.appendChild(btn);
        }

        // ── بناء شبكة الدقائق 00-55 كل 5 دقائق ──
        const mGrid = document.getElementById('minutes-grid');
        for(let m = 0; m < 60; m += 5) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(m).padStart(2,'0');
            btn.id = 'm-' + m;
            btn.style.cssText = `padding:0.45rem 0; border-radius:0.5rem; border:1px solid var(--border-color);
                background:var(--bg-primary); color:var(--text-primary); font-weight:700;
                cursor:pointer; transition:all 0.15s; font-size:0.9rem;`;
            btn.onclick = () => selectMinute(m);
            mGrid.appendChild(btn);
        }

        highlightSelection();
    }

    function selectHour(h) {
        tp_hour = h;
        highlightSelection();
        updatePreview();
    }

    function selectMinute(m) {
        tp_minute = m;
        highlightSelection();
        updatePreview();
    }

    function setAmPm(val) {
        tp_ampm = val;
        const btnAM = document.getElementById('btn-am');
        const btnPM = document.getElementById('btn-pm');
        if(val === 'AM') {
            btnAM.style.background = 'var(--accent-color)';
            btnAM.style.color = 'var(--primary-dark)';
            btnAM.style.borderColor = 'var(--accent-color)';
            btnPM.style.background = 'transparent';
            btnPM.style.color = 'var(--text-secondary)';
            btnPM.style.borderColor = 'var(--border-color)';
        } else {
            btnPM.style.background = 'var(--accent-color)';
            btnPM.style.color = 'var(--primary-dark)';
            btnPM.style.borderColor = 'var(--accent-color)';
            btnAM.style.background = 'transparent';
            btnAM.style.color = 'var(--text-secondary)';
            btnAM.style.borderColor = 'var(--border-color)';
        }
        updatePreview();
    }

    function highlightSelection() {
        // ساعات
        for(let h = 1; h <= 12; h++) {
            const btn = document.getElementById('h-' + h);
            if(!btn) continue;
            if(h === tp_hour) {
                btn.style.background = 'var(--accent-color)';
                btn.style.color = 'var(--primary-dark)';
                btn.style.borderColor = 'var(--accent-color)';
            } else {
                btn.style.background = 'var(--bg-primary)';
                btn.style.color = 'var(--text-primary)';
                btn.style.borderColor = 'var(--border-color)';
            }
        }
        // دقائق
        for(let m = 0; m < 60; m += 5) {
            const btn = document.getElementById('m-' + m);
            if(!btn) continue;
            if(m === tp_minute) {
                btn.style.background = 'var(--accent-color)';
                btn.style.color = 'var(--primary-dark)';
                btn.style.borderColor = 'var(--accent-color)';
            } else {
                btn.style.background = 'var(--bg-primary)';
                btn.style.color = 'var(--text-primary)';
                btn.style.borderColor = 'var(--border-color)';
            }
        }
        updatePreview();
    }

    function updatePreview() {
        const hh = String(tp_hour).padStart(2,'0');
        const mm = String(tp_minute).padStart(2,'0');
        document.getElementById('tp-preview').textContent = `${hh}:${mm}`;
    }

    function openTimePicker() {
        const overlay = document.getElementById('timePickerOverlay');
        overlay.style.display = 'flex';
    }

    function closeTimePicker() {
        document.getElementById('timePickerOverlay').style.display = 'none';
    }

    function confirmTime() {
        // تحويل للصيغة 24 ساعة للحفظ
        let h24 = tp_hour;
        if(tp_ampm === 'AM' && tp_hour === 12) h24 = 0;
        if(tp_ampm === 'PM' && tp_hour !== 12) h24 = tp_hour + 12;

        const hh = String(h24).padStart(2,'0');
        const mm = String(tp_minute).padStart(2,'0');
        const timeVal = `${hh}:${mm}`;

        // حفظ في الحقل المخفي (للسيرفر)
        document.getElementById('event-time-hidden').value = timeVal;

        // عرض النص للمستخدم
        const ampmAr = tp_ampm === 'AM' ? 'صباحاً' : 'مساءً';
        const hDisplay = String(tp_hour).padStart(2,'0');
        const mDisplay = String(tp_minute).padStart(2,'0');
        document.getElementById('time-display-text').textContent = `${hDisplay}:${mDisplay} ${ampmAr}`;
        document.getElementById('time-display-text').style.color = 'var(--text-primary)';

        closeTimePicker();
    }

    // تعيين الوقت عند التعديل
    function setTimeFromStr(timeStr) {
        if(!timeStr) return;
        const [hh, mm] = timeStr.split(':');
        let h = parseInt(hh);
        const m = parseInt(mm);
        tp_ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        tp_hour = h === 0 ? 12 : h;
        tp_minute = m - (m % 5); // أقرب 5 دقائق
        setAmPm(tp_ampm);
        highlightSelection();
        confirmTime();
    }
</script>
@endpush
