@extends('layouts.affairs')
@section('title', 'الحسابات')

@push('styles')
<style>
    .accounts-container { max-width: 900px; margin: 2rem auto; }
    .account-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow); }
    .account-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; }
    .account-icon { width: 40px; height: 40px; border-radius: 0.5rem; background: var(--accent-color); color: var(--primary-dark); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .account-name { font-weight: 800; font-size: 1rem; color: var(--text-primary); }
    .account-role { font-size: 0.85rem; color: var(--text-secondary); }
</style>
@endpush

@section('content')
<div class="accounts-container">
    <h2 class="section-title" style="margin-bottom:1rem;">قائمة الحسابات</h2>
    <div class="account-card">
        <div class="account-header">
            <div class="account-icon"><i class="fa-solid fa-user"></i></div>
            <div>
                <div class="account-name">أحمد علي</div>
                <div class="account-role">موظف شؤون</div>
            </div>
        </div>
        <p class="account-role">البريد: ahmed@example.com</p>
    </div>
    <div class="account-card">
        <div class="account-header">
            <div class="account-icon"><i class="fa-solid fa-user"></i></div>
            <div>
                <div class="account-name">سارة محمد</div>
                <div class="account-role">مدير نظام</div>
            </div>
        </div>
        <p class="account-role">البريد: sara@example.com</p>
    </div>
</div>
@endsection
