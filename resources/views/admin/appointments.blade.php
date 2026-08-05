@extends('layouts.admin')

@section('title', 'إدارة المواعيد واللقاءات')

@section('content')
<div class="space-y-6">
    {{-- هيدر الصفحة --}}
    <div class="relative rounded-2xl overflow-hidden bg-gradient-to-l from-yellow-400 to-yellow-300 dark:from-yellow-500 dark:to-yellow-400 p-5 shadow-glow">
        <div class="absolute left-0 top-0 bottom-0 w-28 opacity-10 pointer-events-none overflow-hidden">
            <span class="material-symbols-outlined text-[110px] text-black absolute -left-3 -top-3">calendar_month</span>
        </div>
        <p class="text-[10px] font-extrabold text-yellow-900/60 mb-0.5 uppercase tracking-widest">المواعيد والاتصال</p>
        <h2 class="text-xl font-extrabold text-slate-900 leading-tight">إدارة المواعيد واستدعاءات أولياء الأمور</h2>
        <p class="text-xs text-slate-800/70 mt-1">تتيح لك هذه اللوحة إدارة طلبات اللقاءات الواردة من الأهالي واستدعائهم عند الضرورة.</p>
    </div>

    {{-- رسائل التنبيه --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold">
            {{ session('error') }}
        </div>
    @endif

    {{-- تقسيم الصفحة --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- الجدول الرئيسي لطلبات الأهالي --}}
        <div class="lg:col-span-2 space-y-6">
            
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
                                    <th class="pb-3">الموضوع</th>
                                    <th class="pb-3">تاريخ اللقاء</th>
                                    <th class="pb-3">الحالة</th>
                                    <th class="pb-3 pl-2">الردود</th>
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
                                            <button onclick="openResponseModal({{ json_encode($meeting) }})"
                                                    class="px-3 py-1.5 rounded-xl font-bold text-[11px] bg-slate-100 hover:bg-primary hover:text-black dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors">
                                                الرد والتحكم
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

        {{-- قسم إرسال استدعاء جديد --}}
        <div class="space-y-6">
            <div class="rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-700/50 p-6 sticky top-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-1 h-5 bg-rose-500 rounded-full"></span>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">إرسال استدعاء لولي أمر طالب</h3>
                </div>

                <form action="{{ route('admin.summons.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-400 font-bold mb-1.5">اختر الطالب المعني:</label>
                        <select name="student_id" required class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white">
                            <option value="" disabled selected class="dark:bg-slate-900">-- اختر الطالب --</option>
                            @foreach($students as $st)
                                <option value="{{ $st->student_id }}" class="dark:bg-slate-900">
                                    {{ $st->user->full_name ?? 'بدون اسم' }} - [{{ $st->level }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold mb-1.5">سبب الاستدعاء باختصار:</label>
                        <input type="text" name="reason_title" required placeholder="مثال: الغياب المتكرر / مناقشة سلوك الطالب" 
                               class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold mb-1.5">تفاصيل الاستدعاء وملاحظات الإدارة:</label>
                        <textarea name="details" required rows="4" placeholder="اكتب هنا التفاصيل التي ستظهر لولي الأمر في التطبيق مع نصائح الحضور..."
                                  class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold mb-1.5">تاريخ الحضور المطلوب:</label>
                        <input type="date" name="summon_date" 
                               class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white" />
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl font-bold bg-rose-500 hover:bg-rose-600 text-white shadow-md active:scale-95 transition-all">
                        إرسال الاستدعاء الآن
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

{{-- مودال الرد على طلب الموعد --}}
<div id="responseModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl p-6 w-full max-w-md shadow-2xl text-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white" id="modalTitle">الرد على طلب ولي الأمر</h3>
            <button onclick="closeResponseModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="modalForm" method="POST" class="space-y-4 text-xs">
            @csrf
            
            <div>
                <label class="block text-slate-400 font-bold mb-1.5">القرار النهائي للموعد:</label>
                <select name="status" id="modalStatus" required class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white">
                    <option value="approved">موافقة وتحديد موعد</option>
                    <option value="rejected">اعتذار / رفض الطلب</option>
                    <option value="completed">تم اكتمال المقابلة بنجاح</option>
                </select>
            </div>

            <div id="dateInputContainer">
                <label class="block text-slate-400 font-bold mb-1.5">تحديد تاريخ ووقت اللقاء المثبت:</label>
                <input type="datetime-local" name="scheduled_at" id="modalScheduledAt"
                       class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white" />
            </div>

            <div>
                <label class="block text-slate-400 font-bold mb-1.5">ملاحظات الإدارة المرسلة للأهل:</label>
                <textarea name="admin_response" id="modalAdminResponse" rows="3" placeholder="مثال: يرجى الحضور للدور الثالث مكتب المدير في الموعد المحدد..."
                          class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent dark:text-white"></textarea>
            </div>

            <div class="flex items-center gap-2 justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeResponseModal()" class="px-4 py-2 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    إلغاء
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl font-bold bg-primary hover:bg-primary-hover text-black shadow-md">
                    حفظ الرد
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResponseModal(meeting) {
        document.getElementById('modalForm').action = `/admin/appointments/${meeting.id}/respond`;
        document.getElementById('modalTitle').innerText = `الرد على الموعد لـ: ${meeting.parent.full_name}`;
        document.getElementById('modalStatus').value = meeting.status;
        document.getElementById('modalAdminResponse').value = meeting.admin_response || '';
        
        if(meeting.scheduled_at) {
            // Convert to Y-m-d\TH:i for datetime-local value format
            const date = new Date(meeting.scheduled_at);
            const formatted = date.toISOString().slice(0, 16);
            document.getElementById('modalScheduledAt').value = formatted;
        } else {
            document.getElementById('modalScheduledAt').value = '';
        }

        document.getElementById('responseModal').classList.remove('hidden');
    }

    function closeResponseModal() {
        document.getElementById('responseModal').classList.add('hidden');
    }

    // إخفاء حقل الوقت عند الرفض للتسهيل
    document.getElementById('modalStatus').addEventListener('change', function() {
        const container = document.getElementById('dateInputContainer');
        if(this.value === 'rejected') {
            container.style.display = 'none';
        } else {
            container.style.display = 'block';
        }
    });
</script>
@endsection
