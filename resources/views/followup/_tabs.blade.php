{{-- تبويبا "لوحة المتابعة": نظرة عامة (الدورات والمرشدون) والطلاب المعرَّضون. نفس الصفحة منطقيًا، رابطان --}}
@php $on = $active ?? 'overview'; @endphp
@push('styles')
<style>
    .lm-tabs { display:flex; gap:.4rem; margin-bottom:1.2rem; padding:.3rem; background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1rem; width:fit-content; max-width:100%; flex-wrap:wrap; }
    .lm-tabs a { padding:.55rem 1.3rem; border-radius:.75rem; font-weight:800; font-size:.9rem; text-decoration:none; color: var(--text-secondary); display:inline-flex; align-items:center; gap:.5rem; }
    .lm-tabs a.active { background: var(--primary-color); color:#fff; }
</style>
@endpush
<div class="lm-tabs">
    <a href="{{ url('/' . $prefix . '/follow-up') }}" class="{{ $on === 'overview' ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> نظرة عامة</a>
    <a href="{{ url('/' . $prefix . '/risk') }}" class="{{ $on === 'students' ? 'active' : '' }}"><i class="fa-solid fa-user-graduate"></i> الطلاب المعرَّضون</a>
</div>
