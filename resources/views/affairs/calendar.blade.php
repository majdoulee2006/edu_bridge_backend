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
    }
    .calendar-header h2 {
        font-size: 1.6rem;
        font-weight: 800;
    }
    .month-nav button {
        background: var(--accent-color);
        border: none;
        color: var(--primary-dark);
        padding: 0.5rem 0.9rem;
        border-radius: 0.5rem;
        cursor: pointer;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .month-nav button:hover { opacity: 0.9; }
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
        padding: 0.8rem;
        background: var(--bg-primary);
        border-radius: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }
    .day:hover {
        background: var(--accent-color);
        color: var(--primary-dark);
    }
    .day.today {
        background: var(--accent-color);
        color: var(--primary-dark);
    }
    .event-dot {
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 6px;
        height: 6px;
        background: var(--accent-color);
        border-radius: 50%;
    }
    /* Events list redesign */
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
    .event-card:hover {
        transform: translateY(-2px);
    }
    .event-date-box {
        background: var(--accent-color);
        color: var(--primary-dark);
        border-radius: 0.8rem;
        padding: 0.5rem 1rem;
        text-align: center;
        font-weight: 800;
        min-width: 70px;
    }
    .event-date-box .day-num { font-size: 1.4rem; line-height: 1.2; }
    .event-date-box .month-name { font-size: 0.8rem; opacity: 0.8; }
    .event-details { flex: 1; }
    .event-details h4 { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.3rem; }
    .event-details p { font-size: 0.9rem; color: var(--text-secondary); }
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
</style>
@endpush

@section('content')
<div class="calendar-container">
    <div class="calendar-header">
        <h2>سبتمبر 2026</h2>
        <div class="month-nav">
            <button><i class="fa-solid fa-chevron-left"></i>السابق</button>
            <button>التالي <i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>
    <!-- Weekday titles (Arabic) -->
    <div class="weekdays">
        <div>الأحد</div><div>الإثنين</div><div>الثلاثاء</div><div>الأربعاء</div><div>الخميس</div><div>الجمعة</div><div>السبت</div>
    </div>
    <div class="days-grid">
        <!-- Empty cells for first week offset (Sep 2026 starts on Thursday) -->
        <div class="day"></div><div class="day"></div><div class="day"></div><div class="day"></div>
        <!-- 1 – 30 Sep -->
        <div class="day">1</div>
        <div class="day">2</div>
        <div class="day">3</div>
        <div class="day">4</div>
        <div class="day">5</div>
        <div class="day">6</div>
        <div class="day today">7<div class="event-dot"></div></div>
        <div class="day">8</div>
        <div class="day">9</div>
        <div class="day">10</div>
        <div class="day">11</div>
        <div class="day">12</div>
        <div class="day">13</div>
        <div class="day">14</div>
        <div class="day">15<div class="event-dot"></div></div>
        <div class="day">16</div>
        <div class="day">17</div>
        <div class="day">18</div>
        <div class="day">19</div>
        <div class="day">20</div>
        <div class="day">21</div>
        <div class="day">22</div>
        <div class="day">23</div>
        <div class="day">24</div>
        <div class="day">25</div>
        <div class="day">26</div>
        <div class="day">27</div>
        <div class="day">28</div>
        <div class="day">29</div>
        <div class="day">30</div>
        <!-- Fill remaining cells to keep grid shape -->
        <div class="day"></div><div class="day"></div>
    </div>
    <div class="events-section">
        <h3>أحداث الشهر</h3>
        <div class="event-cards-grid">
            <div class="event-card">
                <div class="event-date-box">
                    <div class="day-num">7</div>
                    <div class="month-name">سبتمبر</div>
                </div>
                <div class="event-details">
                    <h4>اجتماع عام للموظفين</h4>
                    <p>10:00 صباحاً - القاعة الرئيسية</p>
                </div>
                <div class="event-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            
            <div class="event-card">
                <div class="event-date-box">
                    <div class="day-num">15</div>
                    <div class="month-name">سبتمبر</div>
                </div>
                <div class="event-details">
                    <h4>ورشة تدريبية للموظفين</h4>
                    <p>02:00 مساءً - قاعة التدريب</p>
                </div>
                <div class="event-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div>

            <div class="event-card">
                <div class="event-date-box">
                    <div class="day-num">22</div>
                    <div class="month-name">سبتمبر</div>
                </div>
                <div class="event-details">
                    <h4>موعد دفع الرواتب</h4>
                    <p>التحويل البنكي</p>
                </div>
                <div class="event-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>
        </div>
    </div>
</div>
@endsection
