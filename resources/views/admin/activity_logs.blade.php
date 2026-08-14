@extends('layouts.admin')

@section('title', 'سجل النشاطات والأمان')

@section('header-title', 'سجل تتبع حركة المستخدمين 🛡️')
@section('header-subtitle', 'مراقبة وتتبع عمليات تسجيل الدخول والخروج والأنشطة لجميع مستخدمي النظام')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Filter & Search Bar -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-soft border border-slate-100 dark:border-slate-700/50">
        <form id="filterForm" action="{{ route('admin.activity_logs') }}" method="GET" class="flex flex-col md:flex-row items-center gap-4">
            
            <!-- Search Query -->
            <div class="relative flex-1 w-full">
                <i class="fa-solid fa-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="بحث باسم المستخدم أو الحركة..."
                       class="w-full pr-11 pl-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:border-primary">
            </div>

            <!-- Filter by Role -->
            <div class="w-full md:w-56">
                <select name="role" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:border-primary">
                    <option value="">جميع الأدوار</option>
                    <option value="إدارة" {{ request('role') == 'إدارة' ? 'selected' : '' }}>إدارة</option>
                    <option value="شؤون طلاب" {{ request('role') == 'شؤون طلاب' ? 'selected' : '' }}>شؤون طلاب</option>
                    <option value="رئيس قسم" {{ request('role') == 'رئيس قسم' ? 'selected' : '' }}>رئيس قسم</option>
                    <option value="معلم" {{ request('role') == 'معلم' ? 'selected' : '' }}>معلم</option>
                    <option value="طالب" {{ request('role') == 'طالب' ? 'selected' : '' }}>طالب</option>
                    <option value="ولي أمر" {{ request('role') == 'ولي أمر' ? 'selected' : '' }}>ولي أمر</option>
                </select>
            </div>

            <!-- Filter by Action -->
            <div class="w-full md:w-56">
                <select name="action_type" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:border-primary">
                    <option value="">جميع الحركات</option>
                    @if(isset($allActions))
                        @foreach($allActions as $actType)
                            <option value="{{ $actType }}" {{ request('action_type') == $actType ? 'selected' : '' }}>
                                {{ $actType }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Reset Filter Button -->
            @if(request()->hasAny(['search', 'role', 'action_type']))
                <a href="{{ route('admin.activity_logs') }}" title="إعادة ضبط الفلاتر" class="bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40 font-bold text-sm rounded-2xl py-3 px-5 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-rotate-left"></i> إلغاء الفلترة
                </a>
            @endif
        </form>
    </div>

    <!-- Logs Table Card -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-soft border border-slate-100 dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex flex-wrap items-center justify-between gap-4">
            <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-primary"></i>
                سجل العمليات والنشاط الأمني
            </h3>
            
            <div class="flex items-center gap-4">
                <span class="text-xs font-bold text-slate-400">إجمالي السجلات: {{ $activities->total() }}</span>
                
                <form action="{{ route('admin.activity_logs.clean') }}" method="POST" onsubmit="return confirm('هل أنت تأكد من رغبتك بتنظيف السجلات القديمة أكثر من 90 يوماً؟');">
                    @csrf
                    <input type="hidden" name="days" value="90">
                    <button type="submit" class="bg-slate-100 dark:bg-slate-700 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-600 hover:border-rose-200 text-xs font-bold px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-broom"></i> تنظيف السجلات القديمة (+90 يوماً)
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="py-4 px-6">المستخدم</th>
                        <th class="py-4 px-6">الدور</th>
                        <th class="py-4 px-6">نوع الحركة</th>
                        <th class="py-4 px-6">التفاصيل</th>
                        <th class="py-4 px-6">التاريخ والوقت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/30">
                    @forelse($activities as $act)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-all">
                            
                            <!-- User Info -->
                            <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center font-black text-xs text-slate-700 dark:text-slate-300">
                                        {{ mb_substr($act->user_name ?? 'ز', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span>{{ $act->user_name ?? 'غير معروف' }}</span>
                                        @if($act->user_id)
                                            <span class="text-[11px] text-slate-400 font-medium">ID: {{ $act->user_id }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Role Badge -->
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ $act->role_name ?? 'غير محدد' }}
                                </span>
                            </td>

                            <!-- Action Badge -->
                            <td class="py-4 px-6">
                                @if(str_contains($act->action, 'دخول'))
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40 flex items-center gap-1.5 w-fit">
                                        <i class="fa-solid fa-right-to-bracket"></i> {{ $act->action }}
                                    </span>
                                @elseif(str_contains($act->action, 'خمول'))
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40 flex items-center gap-1.5 w-fit">
                                        <i class="fa-solid fa-clock-rotate-left"></i> {{ $act->action }}
                                    </span>
                                @elseif(str_contains($act->action, 'خروج'))
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40 flex items-center gap-1.5 w-fit">
                                        <i class="fa-solid fa-right-from-bracket"></i> {{ $act->action }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800/40 flex items-center gap-1.5 w-fit">
                                        <i class="fa-solid fa-gear"></i> {{ $act->action }}
                                    </span>
                                @endif
                            </td>

                            <!-- Description -->
                            <td class="py-4 px-6 text-slate-600 dark:text-slate-300 font-medium">
                                {{ $act->description ?? '—' }}
                            </td>

                            <!-- Timestamp -->
                            <td class="py-4 px-6 text-slate-500 dark:text-slate-400 font-semibold text-xs dir-ltr">
                                {{ $act->created_at ? $act->created_at->format('Y-m-d H:i:s') : '—' }}
                                <span class="text-[11px] block text-slate-400 font-normal">{{ $act->created_at ? $act->created_at->diffForHumans() : '' }}</span>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-4xl mb-3 block"></i>
                                لا توجد سجلات نشاط مطابقة حالياً.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($activities->hasPages())
            <div class="p-6 border-t border-slate-100 dark:border-slate-700/50">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const roleSelect = document.querySelector('select[name="role"]');
    const actionSelect = document.querySelector('select[name="action_type"]');
    const searchInput = document.querySelector('input[name="search"]');
    const roleActionsMap = @json($roleActionsMap ?? []);

    function updateActionOptions() {
        if (!roleSelect || !actionSelect) return;
        
        const selectedRole = roleSelect.value.trim();
        const allowedActions = roleActionsMap[selectedRole] || null;

        const options = actionSelect.querySelectorAll('option');
        options.forEach(opt => {
            if (!opt.value) {
                opt.hidden = false;
                opt.disabled = false;
                return;
            }

            if (!allowedActions) {
                opt.hidden = false;
                opt.disabled = false;
            } else {
                const optVal = opt.value.trim();
                const isMatch = allowedActions.some(act => optVal.includes(act) || act.includes(optVal));
                if (isMatch) {
                    opt.hidden = false;
                    opt.disabled = false;
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                }
            }
        });
    }

    if (roleSelect) {
        updateActionOptions();

        roleSelect.addEventListener('change', function() {
            updateActionOptions();
            // تصفية عند تغيير الدور مع إبقاء أو إعادة تعيين نوع الحركة إذا لزم الأمر
            filterForm.submit();
        });
    }

    if (actionSelect) {
        actionSelect.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterForm.submit();
            }
        });
    }
});
</script>
@endsection
