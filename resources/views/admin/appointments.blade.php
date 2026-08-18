@extends('layouts.admin')

@section('title', 'إدارة المواعيد واللقاءات')

@section('content')
<div class="space-y-6">
    {{-- هيدر الصفحة --}}
    <div class="relative rounded-2xl overflow-hidden bg-primary text-primary-content p-5 shadow-glow">
        <div class="absolute left-0 top-0 bottom-0 w-28 opacity-10 pointer-events-none overflow-hidden">
            <span class="material-symbols-outlined text-[110px] text-black absolute -left-3 -top-3">calendar_month</span>
        </div>
        <p class="text-[10px] font-extrabold opacity-75 mb-0.5 uppercase tracking-widest">المواعيد والاتصال</p>
        <h2 class="text-xl font-extrabold leading-tight">سجل المواعيد واستدعاءات أولياء الأمور</h2>
        <p class="text-xs opacity-90 mt-1">تتيح لك هذه اللوحة استطلاع ومتابعة طلبات اللقاءات الواردة من الأهالي وسجل الاستدعاءات الصادرة من الشؤون (للاطلاع فقط).</p>
    </div>

    {{-- قسم الجداول الرئيسية --}}
    <div class="space-y-6">
        
        {{-- طلبات الأهالي --}}
        <div class="rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-5 bg-primary rounded-full"></span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">طلبات المواعيد الواردة من الأهالي</h3>
            </div>
                
                @if($meetings->isEmpty())
                    <div class="text-center py-10 text-slate-400 dark:text-slate-500 text-xs">
                        <span class="material-symbols-outlined text-[48px] block mb-2 text-slate-300 dark:text-slate-700">chat_bubble</span>
                        لا توجد طلبات مواعيد واردة حتى الآن.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-bold">
                                    <th class="pb-3 pr-2">ولي الأمر</th>
                                    <th class="pb-3">الطالب المعني</th>
                                    <th class="pb-3">الموضوع والسبب</th>
                                    <th class="pb-3">تاريخ اللقاء</th>
                                    <th class="pb-3">الحالة</th>
                                    <th class="pb-3 pl-2">التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                @foreach($meetings as $meeting)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="py-3 pr-2 font-bold text-slate-800 dark:text-slate-200">
                                            {{ $meeting->parent->full_name ?? 'ولي أمر' }}
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            {{ $meeting->student->user->full_name ?? 'غير محدد' }}
                                        </td>
                                        <td class="py-3">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $meeting->subject }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $meeting->reason }}</span>
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            @if($meeting->scheduled_at)
                                                <span class="text-emerald-500 font-bold block">{{ date('Y-m-d h:i A', strtotime($meeting->scheduled_at)) }}</span>
                                            @elseif($meeting->preferred_date)
                                                <span class="text-slate-400">مفضل: {{ date('Y-m-d', strtotime($meeting->preferred_date)) }}</span>
                                            @else
                                                <span class="text-slate-400">غير محدد</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if($meeting->status === 'pending')
                                                <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/30 text-[10px] font-extrabold">قيد الانتظار</span>
                                            @elseif($meeting->status === 'approved')
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 text-[10px] font-extrabold">مقبول</span>
                                            @elseif($meeting->status === 'rejected')
                                                <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/30 text-[10px] font-extrabold">مرفوض</span>
                                            @elseif($meeting->status === 'completed')
                                                <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/30 text-[10px] font-extrabold">مكتمل</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pl-2">
                                            <button onclick="openViewModal({{ json_encode($meeting) }})"
                                                    class="px-3 py-1.5 rounded-xl font-bold text-[11px] bg-slate-100 hover:bg-primary hover:text-black dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                عرض التفاصيل
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
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">طلبات استدعاء أولياء الأمور المرسلة</h3>
                </div>

                @if($summons->isEmpty())
                    <div class="text-center py-10 text-slate-400 dark:text-slate-500 text-xs">
                        <span class="material-symbols-outlined text-[48px] block mb-2 text-slate-300 dark:text-slate-700">mail_lock</span>
                        لم يتم إرسال أي استدعاء لولي أمر حتى الآن.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-bold">
                                    <th class="pb-3 pr-2">المرسل من الكادر</th>
                                    <th class="pb-3">الطالب</th>
                                    <th class="pb-3">السبب والتفاصيل</th>
                                    <th class="pb-3">تاريخ الاستدعاء</th>
                                    <th class="pb-3 pl-2">حالة اطلاع الأهل</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                @foreach($summons as $summon)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="py-3 pr-2 font-bold text-slate-800 dark:text-slate-200">
                                            {{ $summon->sender->full_name ?? 'الادارة' }}
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            {{ $summon->student->user->full_name ?? 'غير معروف' }}
                                        </td>
                                        <td class="py-3">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $summon->reason_title }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $summon->details }}</span>
                                        </td>
                                        <td class="py-3 text-slate-600 dark:text-slate-400">
                                            {{ $summon->summon_date ? date('Y-m-d', strtotime($summon->summon_date)) : 'غير محدد' }}
                                        </td>
                                        <td class="py-3 pl-2">
                                            @if($summon->status === 'sent')
                                                <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/30 text-[10px] font-extrabold">تم الإرسال</span>
                                            @elseif($summon->status === 'acknowledged')
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 text-[10px] font-extrabold">تم الاطلاع وتأكيد الحضور ✓</span>
                                            @elseif($summon->status === 'cancelled')
                                                <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/30 text-[10px] font-extrabold">اعتذر ولي الأمر</span>
                                            @elseif($summon->status === 'attended')
                                                <span class="px-2 py-0.5 rounded-full bg-slate-500/10 text-slate-500 border border-slate-500/30 text-[10px] font-extrabold">حضر الاجتماع</span>
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
            <h3 class="text-sm font-bold text-slate-800 dark:text-white" id="modalTitle">تفاصيل طلب الموعد</h3>
            <button onclick="closeViewModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl space-y-2 border border-slate-100 dark:border-slate-700/30">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-bold">ولي الأمر:</span>
                    <span id="viewParentName" class="font-extrabold text-slate-800 dark:text-slate-200"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-bold">الطالب المعني:</span>
                    <span id="viewStudentName" class="font-bold text-slate-700 dark:text-slate-300"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-bold">الموضوع:</span>
                    <span id="viewSubject" class="font-bold text-slate-800 dark:text-slate-200"></span>
                </div>
            </div>

            <div>
                <label class="block text-slate-400 font-bold mb-1">سبب طلب الموعد (من ولي الأمر):</label>
                <div id="viewReason" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/30 text-slate-700 dark:text-slate-300 font-medium"></div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-slate-400 font-bold mb-1">حالة الطلب:</label>
                    <div id="viewStatusBadge"></div>
                </div>
                <div>
                    <label class="block text-slate-400 font-bold mb-1">تاريخ ووقت اللقاء:</label>
                    <div id="viewScheduledAt" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/30 font-bold text-slate-700 dark:text-slate-300"></div>
                </div>
            </div>

            <div>
                <label class="block text-slate-400 font-bold mb-1">رد وملاحظات موظف الشؤون للأهل:</label>
                <div id="viewAdminResponse" class="p-3 rounded-xl bg-amber-500/5 border border-amber-500/20 text-slate-800 dark:text-slate-200 font-medium min-h-[50px]"></div>
            </div>

            <div class="flex items-center justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeViewModal()" class="px-5 py-2 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    إغلاق
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openViewModal(meeting) {
        document.getElementById('modalTitle').innerText = `تفاصيل الموعد - ${meeting.parent ? meeting.parent.full_name : 'ولي أمر'}`;
        document.getElementById('viewParentName').innerText = meeting.parent ? meeting.parent.full_name : 'غير محدد';
        document.getElementById('viewStudentName').innerText = (meeting.student && meeting.student.user) ? meeting.student.user.full_name : 'غير محدد';
        document.getElementById('viewSubject').innerText = meeting.subject || '-';
        document.getElementById('viewReason').innerText = meeting.reason || 'لا يوجد تفاصيل إضافية';
        
        let statusBadge = '';
        if(meeting.status === 'pending') {
            statusBadge = '<span class="px-2 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/30 text-[11px] font-extrabold block text-center">قيد الانتظار</span>';
        } else if(meeting.status === 'approved') {
            statusBadge = '<span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 text-[11px] font-extrabold block text-center">مقبول</span>';
        } else if(meeting.status === 'rejected') {
            statusBadge = '<span class="px-2 py-1 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/30 text-[11px] font-extrabold block text-center">مرفوض</span>';
        } else if(meeting.status === 'completed') {
            statusBadge = '<span class="px-2 py-1 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/30 text-[11px] font-extrabold block text-center">مكتمل</span>';
        }
        document.getElementById('viewStatusBadge').innerHTML = statusBadge;

        if(meeting.scheduled_at) {
            const date = new Date(meeting.scheduled_at);
            document.getElementById('viewScheduledAt').innerText = date.toLocaleString('ar-EG', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        } else if(meeting.preferred_date) {
            document.getElementById('viewScheduledAt').innerText = `مفضل: ${meeting.preferred_date}`;
        } else {
            document.getElementById('viewScheduledAt').innerText = 'لم يحدد بعد';
        }

        document.getElementById('viewAdminResponse').innerText = meeting.admin_response || 'لا يوجد رد مُسجّل حتى الآن من موظف الشؤون.';

        document.getElementById('viewModal').classList.remove('hidden');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
</script>
@endsection
