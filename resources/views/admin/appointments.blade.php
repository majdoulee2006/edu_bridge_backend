@extends('layouts.admin')

@section('title', __('messages.appointments_management'))

@section('content')
<div class="space-y-6">
    {{-- هيدر الصفحة --}}
    <div class="relative rounded-2xl overflow-hidden bg-primary text-primary-content p-5 shadow-glow">
        <div class="absolute left-0 top-0 bottom-0 w-28 opacity-10 pointer-events-none overflow-hidden">
            <span class="material-symbols-outlined text-[110px] text-black absolute -left-3 -top-3">calendar_month</span>
        </div>
        <p class="text-[10px] font-extrabold opacity-75 mb-0.5 uppercase tracking-widest">{{ __('messages.appointments_and_contact') }}</p>
        <h2 class="text-xl font-extrabold leading-tight">{{ __('messages.appointments_and_summons_log') }}</h2>
        <p class="text-xs opacity-90 mt-1">{{ __('messages.appointments_header_desc') }}</p>
    </div>

    {{-- قسم الجداول الرئيسية --}}
    <div class="space-y-6">
        
        {{-- طلبات الأهالي --}}
        <div class="rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-5 bg-primary rounded-full"></span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">{{ __('messages.parent_appointment_requests') }}</h3>
            </div>
                
                @if($meetings->isEmpty())
                    <div class="text-center py-10 text-slate-400 dark:text-slate-500 text-xs">
                        <span class="material-symbols-outlined text-[48px] block mb-2 text-slate-300 dark:text-slate-700">chat_bubble</span>
                        {{ __('messages.no_appointment_requests_yet') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-bold">
                                    <th class="pb-3 pr-2">{{ __('messages.parent') }}</th>
                                    <th class="pb-3">{{ __('messages.student') }}</th>
                                    <th class="pb-3">{{ __('messages.subject_and_reason') }}</th>
                                    <th class="pb-3">{{ __('messages.meeting_date') }}</th>
                                    <th class="pb-3">{{ __('messages.status') }}</th>
                                    <th class="pb-3 pl-2">{{ __('messages.details') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                @foreach($meetings as $meeting)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="py-3 pr-2 font-bold text-slate-800 dark:text-slate-200">
                                            {{ $meeting->parent->full_name ?? __('messages.parent') }}
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            {{ $meeting->student->user->full_name ?? __('messages.unspecified') }}
                                        </td>
                                        <td class="py-3">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $meeting->subject }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $meeting->reason }}</span>
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            @if($meeting->scheduled_at)
                                                <span class="text-emerald-500 font-bold block">{{ date('Y-m-d h:i A', strtotime($meeting->scheduled_at)) }}</span>
                                            @elseif($meeting->preferred_date)
                                                <span class="text-slate-400">{{ __('messages.preferred') }}: {{ date('Y-m-d', strtotime($meeting->preferred_date)) }}</span>
                                            @else
                                                <span class="text-slate-400">{{ __('messages.unspecified') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if($meeting->status === 'pending')
                                                <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/30 text-[10px] font-extrabold">{{ __('messages.pending') }}</span>
                                            @elseif($meeting->status === 'approved')
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 text-[10px] font-extrabold">{{ __('messages.approved') }}</span>
                                            @elseif($meeting->status === 'rejected')
                                                <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/30 text-[10px] font-extrabold">{{ __('messages.rejected') }}</span>
                                            @elseif($meeting->status === 'completed')
                                                <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/30 text-[10px] font-extrabold">{{ __('messages.completed') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pl-2">
                                            <button onclick="openViewModal({{ json_encode($meeting) }})"
                                                    class="px-3 py-1.5 rounded-xl font-bold text-[11px] bg-slate-100 hover:bg-primary hover:text-black dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                {{ __('messages.view_details') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- استدعاءات أولياء الأمور الصادرة --}}
            <div class="rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-5 bg-rose-500 rounded-full"></span>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">{{ __('messages.outgoing_parent_summons') }}</h3>
                </div>

                @if($summons->isEmpty())
                    <div class="text-center py-10 text-slate-400 dark:text-slate-500 text-xs">
                        <span class="material-symbols-outlined text-[48px] block mb-2 text-slate-300 dark:text-slate-700">mail_lock</span>
                        {{ __('messages.no_summons_sent_yet') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-bold">
                                    <th class="pb-3 pr-2">{{ __('messages.sender_staff') }}</th>
                                    <th class="pb-3">{{ __('messages.student') }}</th>
                                    <th class="pb-3">{{ __('messages.reason_and_details') }}</th>
                                    <th class="pb-3">{{ __('messages.summon_date') }}</th>
                                    <th class="pb-3 pl-2">{{ __('messages.parent_acknowledgement_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                @foreach($summons as $summon)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="py-3 pr-2 font-bold text-slate-800 dark:text-slate-200">
                                            {{ $summon->sender->full_name ?? __('messages.admin') }}
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            {{ $summon->student->user->full_name ?? __('messages.unknown') }}
                                        </td>
                                        <td class="py-3">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $summon->reason_title }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $summon->details }}</span>
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            {{ $summon->summon_date ? date('Y-m-d', strtotime($summon->summon_date)) : __('messages.unspecified') }}
                                        </td>
                                        <td class="py-3 pl-2">
                                            @if($summon->status === 'sent')
                                                <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/30 text-[10px] font-extrabold">{{ __('messages.sent') }}</span>
                                            @elseif($summon->status === 'acknowledged')
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 text-[10px] font-extrabold">{{ __('messages.acknowledged_presence') }}</span>
                                            @elseif($summon->status === 'cancelled')
                                                <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/30 text-[10px] font-extrabold">{{ __('messages.parent_apologized') }}</span>
                                            @elseif($summon->status === 'attended')
                                                <span class="px-2 py-0.5 rounded-full bg-slate-500/10 text-slate-500 border border-slate-500/30 text-[10px] font-extrabold">{{ __('messages.attended_meeting') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

    </div>
</div>

{{-- مودال عرض تفاصيل طلب الموعد (للاطلاع فقط للإدارة) --}}
<div id="viewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl p-6 w-full max-w-md shadow-2xl text-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white" id="modalTitle">{{ __('messages.appointment_details') }}</h3>
            <button onclick="closeViewModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl space-y-2 border border-slate-100 dark:border-slate-700/30">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-bold">{{ __('messages.parent') }}:</span>
                    <span id="viewParentName" class="font-extrabold text-slate-800 dark:text-slate-200"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-bold">{{ __('messages.student') }}:</span>
                    <span id="viewStudentName" class="font-bold text-slate-700 dark:text-slate-300"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-bold">{{ __('messages.subject') }}:</span>
                    <span id="viewSubject" class="font-bold text-slate-800 dark:text-slate-200"></span>
                </div>
            </div>

            <div>
                <label class="block text-slate-400 font-bold mb-1">{{ __('messages.appointment_reason_from_parent') }}:</label>
                <div id="viewReason" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/30 text-slate-700 dark:text-slate-300 font-medium"></div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-slate-400 font-bold mb-1">{{ __('messages.request_status') }}:</label>
                    <div id="viewStatusBadge"></div>
                </div>
                <div>
                    <label class="block text-slate-400 font-bold mb-1">{{ __('messages.meeting_date_and_time') }}:</label>
                    <div id="viewScheduledAt" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/30 font-bold text-slate-700 dark:text-slate-300"></div>
                </div>
            </div>

            <div>
                <label class="block text-slate-400 font-bold mb-1">{{ __('messages.affairs_response_and_notes') }}:</label>
                <div id="viewAdminResponse" class="p-3 rounded-xl bg-amber-500/5 border border-amber-500/20 text-slate-800 dark:text-slate-200 font-medium min-h-[50px]"></div>
            </div>

            <div class="flex items-center justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeViewModal()" class="px-5 py-2 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openViewModal(meeting) {
        const parentName = meeting.parent ? meeting.parent.full_name : @json(__('messages.parent'));
        document.getElementById('modalTitle').innerText = `${@json(__('messages.appointment_details'))} - ${parentName}`;
        document.getElementById('viewParentName').innerText = meeting.parent ? meeting.parent.full_name : @json(__('messages.unspecified'));
        document.getElementById('viewStudentName').innerText = (meeting.student && meeting.student.user) ? meeting.student.user.full_name : @json(__('messages.unspecified'));
        document.getElementById('viewSubject').innerText = meeting.subject || '-';
        document.getElementById('viewReason').innerText = meeting.reason || @json(__('messages.no_details'));
        
        let statusBadge = '';
        if(meeting.status === 'pending') {
            statusBadge = `<span class="px-2 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/30 text-[11px] font-extrabold block text-center">${@json(__('messages.pending'))}</span>`;
        } else if(meeting.status === 'approved') {
            statusBadge = `<span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 text-[11px] font-extrabold block text-center">${@json(__('messages.approved'))}</span>`;
        } else if(meeting.status === 'rejected') {
            statusBadge = `<span class="px-2 py-1 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/30 text-[11px] font-extrabold block text-center">${@json(__('messages.rejected'))}</span>`;
        } else if(meeting.status === 'completed') {
            statusBadge = `<span class="px-2 py-1 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/30 text-[11px] font-extrabold block text-center">${@json(__('messages.completed'))}</span>`;
        }
        document.getElementById('viewStatusBadge').innerHTML = statusBadge;

        if(meeting.scheduled_at) {
            const date = new Date(meeting.scheduled_at);
            const localeCode = document.documentElement.getAttribute('lang') === 'en' ? 'en-US' : 'ar-EG';
            document.getElementById('viewScheduledAt').innerText = date.toLocaleString(localeCode, { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        } else if(meeting.preferred_date) {
            document.getElementById('viewScheduledAt').innerText = `${@json(__('messages.preferred'))}: ${meeting.preferred_date}`;
        } else {
            document.getElementById('viewScheduledAt').innerText = @json(__('messages.not_scheduled_yet'));
        }

        document.getElementById('viewAdminResponse').innerText = meeting.admin_response || @json(__('messages.no_response_yet'));

        document.getElementById('viewModal').classList.remove('hidden');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
</script>
@endsection

