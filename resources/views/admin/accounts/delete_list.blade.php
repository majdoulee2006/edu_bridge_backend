@extends('layouts.admin')

@section('title', 'حذف حسابات ' . $roleTitlePlural)

@section('content')

    <!-- Top Header Bar with Back Arrow and Delete Action Button -->
    <div class="flex items-center justify-between bg-white dark:bg-surface-dark p-4 md:p-5 rounded-2xl shadow-soft mb-6 border border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.accounts') }}" 
               class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all shadow-sm" 
               title="الرجوع لإدارة الحسابات">
                <span class="material-symbols-outlined text-2xl">arrow_forward</span>
            </a>
            <div>
                <h1 class="text-lg md:text-xl font-bold text-slate-800 dark:text-white">
                    حذف حسابات {{ $roleTitlePlural }}
                </h1>
                <p class="text-xs text-slate-400 dark:text-slate-500 hidden sm:block">حدد الحسابات المراد حذفها نهائياً من النظام</p>
            </div>
        </div>

        <!-- Header Delete Button -->
        <button type="button" 
                id="header-delete-btn" 
                onclick="openDeleteModal()" 
                disabled
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white font-bold text-sm shadow-md shadow-red-500/20 transition-all opacity-50 cursor-not-allowed active:scale-95">
            <span class="material-symbols-outlined text-xl">delete</span>
            <span class="hidden sm:inline">حذف الحسابات المحددة</span>
            <span class="sm:hidden">حذف</span>
            <span id="selected-badge" class="hidden bg-white/25 px-2 py-0.5 rounded-full text-xs font-black">0</span>
        </button>
    </div>

    <!-- Unified form for bulk delete -->
    <form id="bulk-delete-form" action="{{ route('admin.accounts.delete', ['role_id' => $roleId]) }}" method="POST" class="space-y-6 pb-20">
        @csrf

        <!-- Search Bar -->
        <div class="relative w-full">
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                <span class="material-symbols-outlined text-xl">search</span>
            </span>
            <input id="user-search" 
                   class="w-full py-3.5 pr-11 pl-4 bg-white dark:bg-surface-dark border-none rounded-2xl shadow-soft text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary/50 text-sm placeholder:text-slate-400 transition-all border border-slate-100/50 dark:border-slate-800" 
                   placeholder="{{ $searchPlaceholder }}" 
                   type="text"/>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between px-2 pb-1">
                <h2 class="text-sm font-bold text-slate-500 dark:text-slate-400">قائمة {{ $roleTitlePlural }} (العدد: {{ count($users) }})</h2>
                <div class="text-xs text-primary-dark font-extrabold cursor-pointer hover:underline select-none px-3 py-1.5 rounded-lg bg-primary/10" 
                     id="toggle-select-all" 
                     onclick="toggleSelectAll()">تحديد الكل</div>
            </div>

            <div class="space-y-3" id="users-container">
                @forelse($users as $usr)
                    <label class="bg-white dark:bg-surface-dark p-4 rounded-[1.25rem] shadow-soft flex items-center gap-4 cursor-pointer group hover:shadow-lg transition-all duration-300 border border-slate-100/60 dark:border-slate-800/80 hover:border-red-500/30 select-none user-label-item" 
                           data-search-name="{{ $usr->full_name }}" 
                           data-search-extra="{{ $usr->username ?? '' }} {{ $usr->email ?? '' }}">
                        <input type="checkbox" 
                               name="user_ids[]" 
                               value="{{ $usr->user_id }}" 
                               class="checkbox-custom w-5 h-5 rounded-md border-slate-300 text-primary focus:ring-primary focus:ring-offset-0 bg-slate-50 dark:bg-slate-800 dark:border-slate-650 size-5 select-user-checkbox" 
                               onchange="updateTrashButtonState();"/>
                        
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $cardIconColor }}">
                            <span class="material-symbols-outlined text-xl">{{ $cardIcon }}</span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-800 dark:text-white truncate text-base group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">{{ $usr->full_name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                @if($roleId == 3)
                                    الرقم الجامعي: {{ $usr->student_code ?? $usr->username }}
                                @elseif($roleId == 2)
                                    التخصص: {{ $usr->specialization ?? 'عام' }}
                                @elseif($roleId == 5)
                                    القسم: {{ $usr->department ?? 'غير مخصص' }}
                                @else
                                    اسم المستخدم: {{ $usr->username }}
                                @endif
                                @if($usr->email) - {{ $usr->email }} @endif
                            </p>
                        </div>
                    </label>
                @empty
                    <div class="flex flex-col items-center justify-center p-10 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
                        <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-700 mb-3">person_off</span>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white">لا يوجد حسابات حالياً</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">لم يتم العثور على أي حساب مسجل لهذه الفئة.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Floating Bottom Delete Action Bar -->
        <div id="floating-delete-bar" class="fixed bottom-6 right-4 left-4 md:right-80 md:left-8 z-40 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none">
            <div class="bg-slate-900/90 dark:bg-slate-800/95 backdrop-blur-md text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center justify-between border border-slate-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center font-bold text-sm" id="bottom-selected-count">
                        0
                    </div>
                    <span class="text-sm font-bold">حسابات محددة للحذف</span>
                </div>
                <button type="button" 
                        onclick="openDeleteModal()" 
                        class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-red-500/30 transition-all active:scale-95 pointer-events-auto">
                    <span class="material-symbols-outlined text-lg">delete</span>
                    <span>تأكيد الحذف الآن</span>
                </button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="hidden fixed inset-0 z-50 flex items-center justify-center px-4" id="delete-confirmation-modal">
            <div class="absolute inset-0 bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
            <div class="relative bg-white dark:bg-surface-dark w-full max-w-sm rounded-[2.2rem] p-6 shadow-2xl transform transition-transform duration-300 flex flex-col items-center text-center gap-4 border border-slate-100 dark:border-slate-800 z-10">
                <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-950/40 flex items-center justify-center text-red-500 mb-2 shadow-sm">
                    <span class="material-symbols-outlined text-4xl">warning</span>
                </div>
                <div class="space-y-2">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">هل تريد الحذف بالفعل؟</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        سيتم حذف <span id="modal-count-text" class="font-black text-red-500">0</span> من حسابات الـ {{ $roleTitlePlural }} المحددة نهائياً من النظام. لا يمكن التراجع عن هذا الإجراء.
                    </p>
                </div>
                <div class="flex gap-3 w-full mt-4">
                    <button type="button" 
                            onclick="closeDeleteModal()" 
                            class="flex-1 py-3.5 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-750 transition-colors text-center">
                        إلغاء
                    </button>
                    <button type="submit" 
                            class="flex-1 py-3.5 px-4 rounded-2xl bg-red-500 hover:bg-red-600 text-white font-bold text-sm shadow-lg shadow-red-500/20 active:scale-95 transition-all">
                        نعم، حذف
                    </button>
                </div>
            </div>
        </div>
    </form>

    <style>
        .checkbox-custom:checked {
            background-color: #f2f20d !important;
            border-color: #f2f20d !important;
            color: #1a2633 !important;
        }
    </style>

    <script>
        function openDeleteModal() {
            const checkedCount = document.querySelectorAll('.select-user-checkbox:checked').length;
            if (checkedCount === 0) return;
            document.getElementById('modal-count-text').textContent = checkedCount;
            document.getElementById('delete-confirmation-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-confirmation-modal').classList.add('hidden');
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.select-user-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });

            const toggleBtn = document.getElementById('toggle-select-all');
            if (!allChecked) {
                toggleBtn.textContent = 'إلغاء التحديد';
            } else {
                toggleBtn.textContent = 'تحديد الكل';
            }

            updateTrashButtonState();
        }

        function updateTrashButtonState() {
            const checked = document.querySelectorAll('.select-user-checkbox:checked');
            const count = checked.length;
            
            const headerBtn = document.getElementById('header-delete-btn');
            const badge = document.getElementById('selected-badge');
            const bottomBar = document.getElementById('floating-delete-bar');
            const bottomCount = document.getElementById('bottom-selected-count');

            if (count > 0) {
                headerBtn.disabled = false;
                headerBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                badge.classList.remove('hidden');
                badge.textContent = count;
                
                bottomBar.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
                bottomBar.classList.add('pointer-events-auto');
                bottomCount.textContent = count;
            } else {
                headerBtn.disabled = true;
                headerBtn.classList.add('opacity-50', 'cursor-not-allowed');
                badge.classList.add('hidden');
                badge.textContent = '0';
                
                bottomBar.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                bottomBar.classList.remove('pointer-events-auto');
                bottomCount.textContent = '0';
            }
        }

        // Live search filter
        const searchInput = document.getElementById('user-search');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                document.querySelectorAll('.user-label-item').forEach(item => {
                    const name = (item.getAttribute('data-search-name') || '').toLowerCase();
                    const extra = (item.getAttribute('data-search-extra') || '').toLowerCase();
                    if (name.includes(query) || extra.includes(query)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
    </script>
@endsection
