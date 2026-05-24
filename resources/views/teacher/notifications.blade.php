@extends('layouts.teacher')
@section('title', 'الإشعارات')

@push('styles')
<style>
    .notif-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; display: flex; gap: 1rem; align-items: flex-start; border-right: 4px solid transparent; transition: all 0.2s; }
    .notif-card.unread { border-right-color: var(--accent-color); }
    .notif-icon { width: 46px; height: 46px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
</style>
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <p style="color: var(--text-secondary);">{{ $notifications->count() }} إشعار</p>
    </div>

    @forelse($notifications as $n)
        @php
            $isRead = $n->is_read ?? false;
            $type   = $n->type ?? 'general';
            $iconMap = [
                'assignment' => ['icon' => 'fa-book-open', 'color' => '#ffe600', 'bg' => '#fffbe6'],
                'message'    => ['icon' => 'fa-envelope', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'admin'      => ['icon' => 'fa-calendar', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                'grade'      => ['icon' => 'fa-check', 'color' => '#10b981', 'bg' => '#ecfdf5'],
                'general'    => ['icon' => 'fa-bell', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
            ];
            $style = $iconMap[$type] ?? $iconMap['general'];
        @endphp
        <div class="notif-card {{ !$isRead ? 'unread' : '' }}">
            <div class="notif-icon" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                <i class="fa-solid {{ $style['icon'] }}"></i>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div>
                        @if(isset($n->category))
                            <span style="font-size: 0.78rem; font-weight: 700; color: {{ $style['color'] }};">{{ $n->category }}</span>
                            <br>
                        @endif
                        <span style="font-weight: {{ $isRead ? '600' : '800' }}; font-size: 0.97rem;">{{ $n->title }}</span>
                    </div>
                    <span style="font-size: 0.78rem; color: var(--text-secondary); white-space: nowrap;">
                        {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                    </span>
                </div>
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.4rem; line-height: 1.5;">
                    {{ $n->body ?? $n->message ?? '' }}
                </div>
            </div>
            @if(!$isRead)
                <div style="width: 9px; height: 9px; border-radius: 50%; background: var(--accent-color); flex-shrink: 0; margin-top: 4px;"></div>
            @endif
        </div>
    @empty
        <div style="text-align: center; padding: 4rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary);">
            <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
            <p style="font-size: 1.1rem; font-weight: 600;">لا توجد إشعارات حتى الآن</p>
        </div>
    @endforelse
@endsection
