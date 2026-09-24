@extends('layouts.affairs')

@section('title', 'إدارة الحسابات')

@section('content')
<div class="space-y-5">

    <!-- Top Action Bar (Header matching Admin view) -->
    <div class="bg-white dark:bg-surface-dark p-5 rounded-2xl shadow-soft border border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">manage_accounts</span>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    كافة الحسابات
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-primary/20 text-slate-900 dark:text-primary">
                        {{ $counts['all'] ?? 0 }}
                    </span>
                </h2>
                <p class="text-xs text-slate-400">عرض وإدارة مستخدمي النظام بكل سهولة</p>
            </div>
        </div>

        <!-- Single Create Account Button (+) -->
        <button type="button" onclick="openModal('createAccountModal')" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-slate-950 font-bold text-xs hover:bg-primary-hover transition-all shadow-md shadow-primary/20 active:scale-95 cursor-pointer shrink-0">
            <span class="material-symbols-outlined font-black text-lg">add</span>
            <span>إنشاء حساب جديد</span>
        </button>
    </div>

    @if(isset($pendingUsers) && count($pendingUsers) > 0)
        <!-- قسم الطلبات المعلقة بانتظار الاعتماد -->
        <div class="bg-amber-500/10 border border-amber-500/30 p-5 rounded-2xl shadow-soft space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-amber-500 text-2xl animate-pulse">pending_actions</span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300 flex items-center gap-2">
                            طلبات إنشاء الحسابات بانتظار الاعتماد
                            <span class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-500 text-slate-950">
                                {{ count($pendingUsers) }}
                            </span>
                        </h3>
                        <p class="text-xs text-amber-700/80 dark:text-amber-400/80">طلبات جديدة تم تقديمها من الويب/التطبيق وبانتظار موافقة الشؤون</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($pendingUsers as $pUser)
                    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-amber-200 dark:border-amber-900/40 flex flex-col justify-between gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">
                                    {{ mb_substr($pUser->full_name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ $pUser->full_name }}</h4>
                                    <p class="text-[11px] text-slate-400">{{ $pUser->email ?? $pUser->phone }}</p>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ $pUser->role_id == 3 ? 'طالب' : ($pUser->role_id == 4 ? 'ولي أمر' : 'مستخدم') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-3">
                            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($pUser->created_at)->diffForHumans() }}</span>
                            <div class="flex items-center gap-1.5">
                                <form action="{{ route('affairs.pending_accounts.reject', $pUser->user_id) }}" method="POST" class="inline" onsubmit="return confirm('هل تريد بالتأكيد رفض وحذف هذا الطلب؟')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1" title="رفض الطلب">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                        <span>رفض</span>
                                    </button>
                                </form>
                                <form action="{{ route('affairs.pending_accounts.approve', $pUser->user_id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        <span>اعتماد الحساب</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filter Pills & Search Bar -->
    <div class="bg-white dark:bg-surface-dark p-4 rounded-2xl shadow-soft border border-slate-100 dark:border-slate-800 space-y-3">
        
        <!-- Filter Tabs -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                <a href="{{ route('affairs.accounts', ['role' => 'all', 'search' => $search]) }}" 
                   class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'all' ? 'bg-primary text-slate-950 font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>الكل</span>
                    <span class="opacity-75">({{ $counts['all'] ?? 0 }})</span>
                </a>

                <a href="{{ route('affairs.accounts', ['role' => 'student', 'search' => $search]) }}" 
                   class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'student' ? 'bg-blue-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>الطلاب</span>
                    <span class="opacity-75">({{ $counts['student'] ?? 0 }})</span>
                </a>

                <a href="{{ route('affairs.accounts', ['role' => 'teacher', 'search' => $search]) }}" 
                   class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'teacher' ? 'bg-emerald-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>المعلمون</span>
                    <span class="opacity-75">({{ $counts['teacher'] ?? 0 }})</span>
                </a>

                <a href="{{ route('affairs.accounts', ['role' => 'hod', 'search' => $search]) }}" 
                   class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'hod' ? 'bg-purple-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>رؤساء الأقسام</span>
                    <span class="opacity-75">({{ $counts['hod'] ?? 0 }})</span>
                </a>

                <a href="{{ route('affairs.accounts', ['role' => 'parent', 'search' => $search]) }}" 
                   class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'parent' ? 'bg-orange-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>أولياء الأمور</span>
                    <span class="opacity-75">({{ $counts['parent'] ?? 0 }})</span>
                </a>

                <a href="{{ route('affairs.accounts', ['role' => 'affairs', 'search' => $search]) }}" 
                   class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'affairs' ? 'bg-rose-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>الشؤون</span>
                    <span class="opacity-75">({{ $counts['affairs'] ?? 0 }})</span>
                </a>
            </div>
        </div>

        <!-- Search Bar Input -->
        <form action="{{ route('affairs.accounts') }}" method="GET" class="relative">
            <input type="hidden" name="role" value="{{ $roleFilter }}">
            <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input type="text" 
                   name="search" 
                   value="{{ $search }}" 
                   placeholder="بحث بالاسم، اسم المستخدم، البريد، أو الهاتف..." 
                   class="w-full pr-10 pl-20 py-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary transition-colors">
            
            <div class="absolute left-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                @if(!empty($search))
                    <a href="{{ route('affairs.accounts', ['role' => $roleFilter]) }}" class="px-2 py-1 text-[11px] font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        إلغاء
                    </a>
                @endif
                <button type="submit" class="px-3 py-1 bg-slate-800 text-white dark:bg-primary dark:text-slate-950 rounded-lg text-[11px] font-bold">
                    بحث
                </button>
            </div>
        </form>
    </div>

    <!-- Accounts Minimal Clean Table with 4 Action Icons -->
    <div class="bg-white dark:bg-surface-dark rounded-2xl shadow-soft border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">المستخدم والنوع</th>
                        <th class="py-3 px-4">معرف الحساب</th>
                        <th class="py-3 px-4">بيانات التواصل</th>
                        <th class="py-3 px-4 text-center">الحالة</th>
                        <th class="py-3 px-4 text-center">التاريخ</th>
                        <th class="py-3 px-4 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($users as $usr)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- User & Role -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold shrink-0
                                        @if($usr->role_id == 3) bg-blue-500/10 text-blue-500
                                        @elseif($usr->role_id == 2) bg-emerald-500/10 text-emerald-500
                                        @elseif($usr->role_id == 5) bg-purple-500/10 text-purple-500
                                        @elseif($usr->role_id == 4) bg-orange-500/10 text-orange-500
                                        @elseif($usr->role_id == 6) bg-rose-500/10 text-rose-500
                                        @else bg-slate-500/10 text-slate-500
                                        @endif">
                                        <span class="material-symbols-outlined text-xl">
                                            @if($usr->role_id == 3) school
                                            @elseif($usr->role_id == 2) sports
                                            @elseif($usr->role_id == 5) supervisor_account
                                            @elseif($usr->role_id == 4) family_restroom
                                            @elseif($usr->role_id == 6) badge
                                            @else person
                                            @endif
                                        </span>
                                    </div>

                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $usr->full_name }}
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-400">
                                            @if($usr->role_id == 3) طالب
                                            @elseif($usr->role_id == 2) معلم / مدرب
                                            @elseif($usr->role_id == 5) رئيس قسم
                                            @elseif($usr->role_id == 4) ولي أمر
                                            @elseif($usr->role_id == 6) موظف شؤون
                                            @else مستخدم
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Username / University Code -->
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-700 dark:text-slate-300">
                                {{ $usr->username }}
                                @if(!empty($usr->university_id))
                                    <span class="block text-[10px] text-slate-400 font-sans">({{ $usr->university_id }})</span>
                                @endif
                            </td>

                            <!-- Contact info -->
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                <div>{{ $usr->email ?? '-' }}</div>
                                @if(!empty($usr->phone))
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $usr->phone }}</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $usr->status === 'active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $usr->status === 'active' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ $usr->status === 'active' ? 'نشط' : 'معلق' }}
                                </span>
                            </td>

                            <!-- Date -->
                            <td class="py-3.5 px-4 text-center text-[11px] text-slate-400 font-mono">
                                {{ \Carbon\Carbon::parse($usr->created_at)->format('Y/m/d') }}
                            </td>

                            <!-- Actions (الـ 4 أيقونات: تعديل، إيقاف/تفعيل، فك ربط، حذف) -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- 1. تعديل (Edit) -->
                                    <button type="button" 
                                            onclick="openEditModal({{ $usr->user_id }}, '{{ addslashes($usr->full_name) }}', '{{ addslashes($usr->email ?? '') }}', '{{ addslashes($usr->phone ?? '') }}')" 
                                            class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-colors inline-flex items-center justify-center shadow-sm" 
                                            title="تعديل بيانات الحساب">
                                        <span class="material-symbols-outlined text-base">edit</span>
                                    </button>

                                    <!-- 2. إيقاف / تفعيل (Toggle Status) -->
                                    <form action="{{ route('affairs.accounts.toggle', $usr->user_id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من {{ $usr->status === 'active' ? 'إيقاف' : 'تفعيل' }} حساب ({{ $usr->full_name }})؟')">
                                        @csrf
                                        @if($usr->status === 'active')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white transition-colors inline-flex items-center justify-center shadow-sm" title="إيقاف الحساب">
                                                <span class="material-symbols-outlined text-base">block</span>
                                            </button>
                                        @else
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-colors inline-flex items-center justify-center shadow-sm" title="تفعيل الحساب">
                                                <span class="material-symbols-outlined text-base">check_circle</span>
                                            </button>
                                        @endif
                                    </form>

                                    <!-- 3. فك ربط (Unlink Device / Sessions) -->
                                    <form action="{{ route('affairs.accounts.unlink', $usr->user_id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من فك ربط حساب ({{ $usr->full_name }})؟\n\n- للطلاب: سيتم فك ربط الجهاز الحالي ليتسنى له الدخول من جهاز جديد.\n- لأولياء الأمور: فك ربط الأبناء بالجلسة.\n- سيتم إنهاء كافة الجلسات النشطة للمستخدم.')">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-500 hover:bg-purple-500 hover:text-white transition-colors inline-flex items-center justify-center shadow-sm" title="فك ربط الجهاز والجلسات">
                                            <span class="material-symbols-outlined text-base">link_off</span>
                                        </button>
                                    </form>

                                    <!-- 4. حذف (Delete) -->
                                    <form action="{{ route('affairs.accounts.delete', $usr->user_id) }}" method="POST" class="inline-block" onsubmit="return confirm('تحذير نهائي: هل أنت متأكد من حذف حساب ({{ $usr->full_name }})؟\nهذا الإجراء سيقوم بحذف كافة السجلات التابعة للحساب نهائياً.')">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white transition-colors inline-flex items-center justify-center shadow-sm" title="حذف الحساب">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl mb-2 text-slate-500">search_off</span>
                                <p class="text-xs font-bold">لا توجد حسابات مسجلة تطابق التصفية الحالية</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-3 border-t border-slate-100 dark:border-slate-800 flex justify-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ========================================================= -->
<!-- MODAL: CREATE ACCOUNT (معلم أو رئيس قسم) -->
<!-- ========================================================= -->
<div id="createAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-lg p-6 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 m-4 transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">person_add</span>
                إنشاء حساب جديد
            </h3>
            <button type="button" onclick="closeModal('createAccountModal')" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form action="{{ route('affairs.accounts.store') }}" method="POST" class="space-y-4 mt-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                <input type="text" name="full_name" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" placeholder="أدخل اسم المستخدم الكامل" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                <input type="email" name="email" dir="ltr" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" placeholder="example@edu-bridge.com" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">رقم الهاتف</label>
                <input type="tel" name="phone" dir="ltr" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" placeholder="09xxxxxxxx">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نوع الحساب (الدور) <span class="text-rose-500">*</span></label>
                <select name="role_id" id="create_role_id" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" required onchange="toggleAffairsExtraFields()">
                    <option value="">-- اختر الدور --</option>
                    <option value="2">معلم / مدرب</option>
                    <option value="5">رئيس قسم</option>
                </select>
            </div>

            <div id="create_dept_group" style="display:none;">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">القسم الأكاديمي <span class="text-rose-500">*</span></label>
                <select name="department_id" id="create_department_id" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" onchange="filterAffairsCoursesByDept()">
                    <option value="">-- اختر القسم --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="create_spec_group" style="display:none;">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">الاختصاص (الفرع)</label>
                <select name="specialization" id="create_spec_select" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary">
                    <option value="">الرجاء اختيار القسم أولاً</option>
                </select>
            </div>

            <div id="create_courses_group" style="display:none;">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">الدورات (المواد الموكلة)</label>
                <select name="courses[]" id="create_courses_select" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" multiple style="height: 110px;">
                    @foreach($courses as $course)
                        <option value="{{ $course->course_id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
                <small class="text-[11px] text-slate-400 block mt-1">اضغط Ctrl لتحديد أكثر من مادة</small>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">كلمة المرور المؤقتة <span class="text-rose-500">*</span></label>
                <input type="password" name="password" minlength="6" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary" placeholder="على الأقل 6 خانات" required>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                <button type="button" onclick="closeModal('createAccountModal')" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    إلغاء
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-slate-950 font-bold text-xs hover:bg-primary-hover shadow-md shadow-primary/20 active:scale-95 transition-all">
                    حفظ وإنشاء الحساب
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL: EDIT ACCOUNT -->
<!-- ========================================================= -->
<div id="editAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-lg p-6 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 m-4 transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500 text-xl">edit</span>
                تعديل بيانات الحساب
            </h3>
            <button type="button" onclick="closeModal('editAccountModal')" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="editAccountForm" method="POST" class="space-y-4 mt-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_full_name" name="full_name" required class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                <input type="email" id="edit_email" name="email" required dir="ltr" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">رقم الهاتف</label>
                <input type="tel" id="edit_phone" name="phone" dir="ltr" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    كلمة المرور الجديدة 
                    <span class="text-[11px] text-slate-400 font-normal">(اتركها فارغة إذا لم ترد التغيير)</span>
                </label>
                <input type="password" id="edit_password" name="password" minlength="6" placeholder="••••••••" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">تأكيد كلمة المرور الجديدة</label>
                <input type="password" id="edit_password_confirmation" name="password_confirmation" placeholder="••••••••" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-primary">
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                <button type="button" onclick="closeModal('editAccountModal')" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    إلغاء
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 active:scale-95 transition-all">
                    حفظ التعديلات
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div').classList.remove('scale-95');
            modal.querySelector('div').classList.add('scale-100');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.querySelector('div').classList.remove('scale-100');
        modal.querySelector('div').classList.add('scale-95');
        modal.classList.add('opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function openEditModal(userId, fullName, email, phone) {
        const form = document.getElementById('editAccountForm');
        form.action = `/affairs/accounts/update/${userId}`;
        document.getElementById('edit_full_name').value = fullName;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_password_confirmation').value = '';
        openModal('editAccountModal');
    }

    // Close modals when clicking backdrop
    ['createAccountModal', 'editAccountModal'].forEach(id => {
        const m = document.getElementById(id);
        if (m) {
            m.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(id);
                }
            });
        }
    });

    // Filtering courses & branches in Create Modal
    const deptCourses = @json($deptCourses ?? []);
    const deptBranches = @json($deptBranches ?? []);

    function filterAffairsCoursesByDept() {
        const deptId = document.getElementById('create_department_id').value;
        const coursesSelect = document.getElementById('create_courses_select');
        const specSelect = document.getElementById('create_spec_select');
        
        coursesSelect.innerHTML = '';
        specSelect.innerHTML = '';
        
        if (!deptId) {
            const optCourse = document.createElement('option');
            optCourse.value = ""; optCourse.text = "الرجاء اختيار القسم أولاً";
            optCourse.disabled = true; optCourse.selected = true;
            coursesSelect.appendChild(optCourse);

            const optSpec = document.createElement('option');
            optSpec.value = ""; optSpec.text = "الرجاء اختيار القسم أولاً";
            optSpec.disabled = true; optSpec.selected = true;
            specSelect.appendChild(optSpec);
            return;
        }

        const courses = deptCourses[deptId] || [];
        if(courses.length === 0) {
            const opt = document.createElement('option');
            opt.value = ""; opt.text = "لا توجد مواد لهذا القسم";
            opt.disabled = true; opt.selected = true;
            coursesSelect.appendChild(opt);
        } else {
            courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.id;
                option.text = course.title;
                coursesSelect.appendChild(option);
            });
        }

        const branches = deptBranches[deptId] || [];
        if(branches.length === 0) {
            const opt = document.createElement('option');
            opt.value = ""; opt.text = "لا توجد أفرع لهذا القسم";
            opt.disabled = true; opt.selected = true;
            specSelect.appendChild(opt);
        } else {
            branches.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.name;
                option.text = branch.name;
                specSelect.appendChild(option);
            });
        }
    }

    function toggleAffairsExtraFields() {
        const role = document.getElementById('create_role_id').value;
        const deptGroup = document.getElementById('create_dept_group');
        const specGroup = document.getElementById('create_spec_group');
        const coursesGroup = document.getElementById('create_courses_group');
        const deptSelect = document.getElementById('create_department_id');

        deptGroup.style.display = 'none';
        specGroup.style.display = 'none';
        coursesGroup.style.display = 'none';
        deptSelect.required = false;

        if (role === '5') { // HOD
            deptGroup.style.display = 'block';
            deptSelect.required = true;
        } else if (role === '2') { // Teacher
            deptGroup.style.display = 'block';
            specGroup.style.display = 'block';
            coursesGroup.style.display = 'block';
            deptSelect.required = true;
            filterAffairsCoursesByDept();
        }
    }
</script>
@endpush
@endsection
