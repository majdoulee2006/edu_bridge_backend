@extends('layouts.admin')

@section('title', 'تخصيص رؤساء الأقسام')
@section('header-title', 'تخصيص وإدارة رؤساء الأقسام')
@section('header-subtitle', 'استعراض كافة الأقسام ورؤساء الأقسام وتحديد أو إضافة رؤساء أقسام جدد')

@section('header-actions')
    <a href="{{ route('admin.courses') }}" class="w-10 h-10 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft" title="رجوع للدورات">
        <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
    </a>
@endsection

@section('content')
<div class="font-Cairo max-w-6xl mx-auto flex flex-col gap-8 pb-12">

    {{-- Success Message Alert --}}
    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 p-5 rounded-3xl flex items-center gap-3 text-sm font-black shadow-sm">
            <span class="material-symbols-outlined text-xl">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Validation Errors Alert --}}
    @if($errors->any())
        <div class="bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 p-5 rounded-3xl flex flex-col gap-1.5 text-xs font-bold shadow-sm">
            <div class="flex items-center gap-2 font-black text-sm">
                <span class="material-symbols-outlined text-xl">warning</span>
                <span>يرجى تصحيح الأخطاء التالية:</span>
            </div>
            <ul class="list-disc list-inside text-xs pr-6">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header title card --}}
    <div class="flex items-center justify-between px-1">
        <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <span class="material-symbols-outlined text-amber-500 text-2xl">workspace_premium</span>
                قائمة الأقسام ورؤساء الأقسام الحاليين
            </h2>
            <p class="text-xs font-bold text-slate-400 mt-1">انقر على زر "تغيير / تعيين" لتعديل رئيس القسم أو إضافة رئيس قسم جديد</p>
        </div>
        <span class="px-4 py-2 rounded-2xl bg-primary/10 border border-primary/20 text-xs font-black text-primary-dark dark:text-primary shadow-sm">
            {{ count($departments) }} أقسام أكاديمية
        </span>
    </div>

    {{-- Departments List Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($departments as $dept)
            @php
                $hasHOD = !empty($dept->current_hod_user_id);
            @endphp
            <div class="bg-white dark:bg-surface-dark p-7 rounded-[2rem] border border-slate-100 dark:border-slate-800/80 shadow-md hover:shadow-xl transition-all duration-300 flex items-center justify-between gap-5 group">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl {{ $hasHOD ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center font-bold text-2xl flex-shrink-0 shadow-sm group-hover:scale-105 transition-all">
                        <span class="material-symbols-outlined text-2xl">{{ $hasHOD ? 'verified_user' : 'no_accounts' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h3 class="text-base font-black text-slate-850 dark:text-white group-hover:text-primary transition-colors">قسم {{ $dept->name }}</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400">رئيس القسم:</span>
                            <span class="text-sm font-extrabold {{ $hasHOD ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                {{ $dept->current_hod_name }}
                            </span>
                        </div>
                    </div>
                </div>

                <button type="button"
                        onclick="openHodOptionModal({{ $dept->department_id }}, '{{ addslashes($dept->name) }}', '{{ addslashes($dept->current_hod_name) }}')"
                        class="px-5 py-3 rounded-2xl bg-primary text-slate-900 border border-primary/40 text-xs font-black transition-all flex items-center gap-2 flex-shrink-0 shadow-md hover:bg-primary-dark hover:scale-[1.03] active:scale-[0.98]">
                    <span class="material-symbols-outlined text-lg">edit</span>
                    <span>تغيير / تعيين</span>
                </button>
            </div>
        @endforeach
    </div>

</div>

{{-- ===== MODAL: Select Option or Register New HOD ===== --}}
<div id="hod-action-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 font-Cairo transition-all duration-200 opacity-0">
    <div id="modal-card" class="bg-white dark:bg-surface-dark w-full max-w-2xl rounded-3xl border border-slate-100 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col transform scale-95 transition-all duration-200">
        
        {{-- Modal Header --}}
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/20 text-slate-900 dark:text-primary flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-xl">manage_accounts</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-primary-dark dark:text-primary uppercase">تخصيص رئيس قسم</span>
                    <h3 id="modal-dept-title" class="text-sm font-black text-slate-850 dark:text-white"></h3>
                </div>
            </div>
            <button type="button" onclick="closeHodModal()" class="w-8 h-8 rounded-xl bg-slate-200/60 dark:bg-slate-800 text-slate-500 hover:text-rose-500 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Option Selector Tabs --}}
        <div class="p-4 bg-slate-100/50 dark:bg-slate-900/60 border-b border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2">
            <button type="button" id="tab-existing" onclick="switchHodTab('existing')" class="py-3 px-4 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700">
                <span class="material-symbols-outlined text-lg">badge</span>
                <span>شخص موجود بالمنظومة</span>
            </button>

            <button type="button" id="tab-new" onclick="switchHodTab('new')" class="py-3 px-4 rounded-2xl font-extrabold text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-2 transition-all">
                <span class="material-symbols-outlined text-lg">person_add</span>
                <span>إضافة رئيس قسم جديد</span>
            </button>
        </div>

        {{-- TAB 1: Existing Staff Form --}}
        <div id="form-existing-container" class="p-6 flex flex-col gap-4">
            <form action="{{ route('admin.courses.assign-hod.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="department_id" id="existing-dept-id" value="" />

                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-extrabold text-slate-800 dark:text-white">اختر رئيس القسم من المدرسين والمدربين المتاحين:</h4>
                    <span class="text-xs font-bold text-primary">{{ count($availableUsers) }} متاح</span>
                </div>

                {{-- Department Filter & Search --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="relative">
                        <select id="hod-dept-filter" onchange="filterHODs()" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-bold text-slate-800 dark:text-white outline-none cursor-pointer">
                            <option value="">جميع الأقسام الأكاديمية</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->name }}">قسم {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
                        <input id="hod-search" type="text"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 pr-9 pl-3 text-xs font-bold text-slate-800 dark:text-white placeholder:text-slate-400 transition-all outline-none"
                               placeholder="بحث باسم المدرب..." onkeyup="filterHODs()" />
                    </div>
                </div>

                {{-- Users List --}}
                <div class="flex flex-col gap-2 max-h-[260px] overflow-y-auto hide-scrollbar pr-1">
                    @foreach($availableUsers as $user)
                        @php
                            $initials = mb_substr($user->full_name, 0, 2);
                            $isHOD = $user->role_id == 5;
                        @endphp
                        <label class="relative cursor-pointer group hod-user-item" data-name="{{ strtolower($user->full_name) }}" data-dept="{{ $user->department ?? '' }}">
                            <input type="radio" name="user_id" value="{{ $user->user_id }}" class="peer sr-only" required />
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800 transition-all group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:ring-2 peer-checked:ring-primary/40">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary/20 text-slate-900 dark:text-primary flex items-center justify-center font-black text-xs flex-shrink-0">
                                        {{ $initials }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-slate-900 dark:text-white">{{ $user->full_name }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold mt-0.5">
                                            {{ $isHOD ? 'رئيس قسم ('.$user->department.')' : 'مدرب/مدرس في '.$user->department }}
                                        </span>
                                    </div>
                                </div>
                                <div class="w-4 h-4 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center peer-checked:border-primary peer-checked:bg-primary transition-all">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-900"></div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-primary text-slate-900 font-extrabold text-xs flex items-center justify-center gap-2 shadow-md hover:bg-primary-dark transition-all">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        <span>تأكيد تعيين رئيس القسم</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- TAB 2: Register New External HOD Form --}}
        <div id="form-new-container" class="p-6 hidden flex-col gap-4">
            <form action="{{ route('admin.courses.assign-hod.store-new') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="department_id" id="new-dept-id" value="" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- الاسم الأول --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">الاسم الأول *</label>
                        <input type="text" name="first_name" required placeholder="مثال: أحمد"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>

                    {{-- الاسم الثاني / العائلة --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">الاسم الثاني / العائلة *</label>
                        <input type="text" name="last_name" required placeholder="مثال: علي"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- البريد الإلكتروني --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">البريد الإلكتروني *</label>
                        <input type="email" name="email" required placeholder="name@example.com"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>

                    {{-- اسم المستخدم --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">اسم المستخدم (Username) *</label>
                        <input type="text" name="username" required placeholder="ahmed_ali"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>
                </div>

                {{-- رقم الهاتف --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">رقم الهاتف *</label>
                    <input type="text" name="phone" required placeholder="09xxxxxxxx"
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- كلمة المرور --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">كلمة المرور *</label>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>

                    {{-- تأكيد كلمة المرور --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">تأكيد كلمة المرور *</label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-primary hover:bg-primary-dark text-slate-900 font-extrabold text-xs flex items-center justify-center gap-2 shadow-md transition-all">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        <span>إنشاء الحساب وتعيينه رئيساً للقسم</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function openHodOptionModal(deptId, deptName, currentHodName) {
        document.getElementById('modal-dept-title').textContent = deptName + ' (الحالي: ' + currentHodName + ')';
        document.getElementById('existing-dept-id').value = deptId;
        document.getElementById('new-dept-id').value = deptId;

        // Set department filter to the selected department
        const filterSelect = document.getElementById('hod-dept-filter');
        if (filterSelect) {
            filterSelect.value = deptName;
        }

        switchHodTab('existing');
        filterHODs();

        const modal = document.getElementById('hod-action-modal');
        const card = document.getElementById('modal-card');

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 20);
    }

    function closeHodModal() {
        const modal = document.getElementById('hod-action-modal');
        const card = document.getElementById('modal-card');

        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    function switchHodTab(tab) {
        const tabExisting = document.getElementById('tab-existing');
        const tabNew = document.getElementById('tab-new');
        const formExisting = document.getElementById('form-existing-container');
        const formNew = document.getElementById('form-new-container');

        if (tab === 'existing') {
            tabExisting.className = 'py-3 px-4 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
            tabNew.className = 'py-3 px-4 rounded-2xl font-extrabold text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-2 transition-all';
            
            formExisting.classList.remove('hidden');
            formExisting.classList.add('flex');
            formNew.classList.remove('flex');
            formNew.classList.add('hidden');
        } else {
            tabNew.className = 'py-3 px-4 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
            tabExisting.className = 'py-3 px-4 rounded-2xl font-extrabold text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-2 transition-all';
            
            formNew.classList.remove('hidden');
            formNew.classList.add('flex');
            formExisting.classList.remove('flex');
            formExisting.classList.add('hidden');
        }
    }

    function filterHODs() {
        const query = (document.getElementById('hod-search').value || '').toLowerCase();
        const deptFilter = document.getElementById('hod-dept-filter').value;

        document.querySelectorAll('.hod-user-item').forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const dept = item.getAttribute('data-dept') || '';

            const matchName = name.includes(query);
            const matchDept = !deptFilter || dept === deptFilter;

            if (matchName && matchDept) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>
@endpush
