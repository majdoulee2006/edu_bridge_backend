@extends('layouts.affairs')
@section('title', 'الأنشطة')

@push('styles')
<style>
    .activities-container { max-width: 900px; margin: 2rem auto; }
    .activity-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow); }
    .activity-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; }
    .activity-icon { width: 40px; height: 40px; border-radius: 0.5rem; background: var(--accent-color); color: var(--primary-dark); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .activity-title { font-weight: 800; font-size: 1rem; color: var(--text-primary); }
    .activity-meta { font-size: 0.85rem; color: var(--text-secondary); }
</style>
@endpush

@section('content')
<div class="activities-container">
    <h2 class="section-title" style="margin-bottom:1rem;">الأنشطة الحالية</h2>
    <div class="activity-card">
        <div class="activity-header">
            <div class="activity-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <div class="activity-title">اجتماع عام للموظفين</div>
                <div class="activity-meta">25 سبتمبر 2026 • 10:00 ص</div>
            </div>
        </div>
        <p class="activity-meta">المكان: قاعة الاجتماعات – المبنى الرئيسي.</p>
    </div>
    <div class="activity-card">
        <div class="activity-header">
            <div class="activity-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <div class="activity-title">ورشة تدريبية للموظفين</div>
                <div class="activity-meta">02 أكتوبر 2026 • 02:00 م</div>
            </div>
        </div>
        <p class="activity-meta">الموضوع: تحسين مهارات التواصل داخل الفرق.</p>
    </div>
</div>
@endsection
