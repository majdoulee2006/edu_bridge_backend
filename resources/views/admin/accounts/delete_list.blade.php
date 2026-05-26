@extends('layouts.admin')

@section('title', 'حذف حسابات ' . $roleTitlePlural)
@section('header-title', 'حذف حسابات ' . $roleTitlePlural)
@section('header-subtitle', 'إدارة وحذف مستخدمي فئة ' . $roleTitlePlural)

@section('back-button')
    <a href="{{ route('admin.accounts') }}" class="p-2 -mr-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
        <span class="material-symbols-outlined text-slate-800 dark:text-white text-2xl">arrow_forward</span>
    </a>
@endsection

@section('content')

    <!-- Unified form for bulk delete -->
    <form id="bulk-delete-form" action="{{ route('admin.accounts.delete', ['role_id' => $roleId]) }}" method="POST" class="space-y-6 pb-10">
        @csrf

        <!-- Search Bar -->
        <div class="relative w-full">
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                <span class="material-symbols-outlined text-xl">search</span>
            </span>
            <input id="user-search" class="w-full py-3.5 pr-11 pl-4 bg-white dark:bg-surface-dark border-none rounded-2xl shadow-soft text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary/50 text-sm placeholder:text-slate-400 transition-all border border-slate-100/50 dark:border-slate-800" placeholder="{{ $searchPlaceholder }}" type="text"/>
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between px-2 pb-1">
                <h2 class="text-sm font-bold text-slate-500 dark:text-slate-400">قائمة الـ {{ $roleTitlePlural }}</h2>
                <div class="text-xs text-primary-dark font-extrabold cursor-pointer hover:underline select-none" id="toggle-select-all" onclick="toggleSelectAll()">تحديد الكل</div>
            </div>

            <div class="space-y-3" id="users-container">
                @forelse($users as $usr)
                    <label class="bg-white dark:bg-surface-dark p-4 rounded-[1.25rem] shadow-soft flex items-center gap-4 cursor-pointer group hover:shadow-lg transition-all duration-300 border border-transparent hover:border-red-500/20 select-none user-label-item" data-search-name="{{ $usr->full_name }}" data-search-extra="{{ $usr->username ?? '' }} {{ $usr->email ?? '' }}">
                        <input type="checkbox" name="user_ids[]" value="{{ $usr->user_id }}" class="checkbox-custom w-5 h-5 rounded-md border-slate-300 text-primary focus:ring-primary focus:ring-offset-0 bg-slate-50 dark:bg-slate-800 dark:border-slate-650 size-5 select-user-checkbox" onclick="event.stopPropagation(); updateTrashButtonState();"/>
                        
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
                    <div class="flex flex-col items-center justify-center p-8 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
                        <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-700 mb-3">person_off</span>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white">لا يوجد حسابات حالياً</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">لم يتم العثور على أي حساب مسجل لهذه الفئة.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Floating Delete Button -->
        <div id="floating-trash-container" class="fixed bottom-28 right-6 z-40 scale-0 transition-transform duration-300">
            <label class="flex items-center justify-center w-14 h-14 rounded-full bg-red-500 text-white shadow-lg shadow-red-500/30 hover:bg-red-650 hover:scale-105 active:scale-95 transition-all cursor-pointer border-4 border-white dark:border-background-dark" for="delete-trigger">
                <span class="material-symbols-outlined text-2xl">delete</span>
            </label>
        </div>

        <!-- Modal Trigger -->
        <input class="hidden" id="delete-trigger" type="checkbox"/>
        
        <!-- Confirmation Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4" id="delete-confirmation-modal">
            <label class="absolute inset-0 bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-opacity" for="delete-trigger"></label>
            <div class="modal-content relative bg-white dark:bg-surface-dark w-full max-w-sm rounded-[2.2rem] p-6 shadow-2xl transform scale-95 transition-transform duration-300 flex flex-col items-center text-center gap-4 border border-slate-100 dark:border-slate-800">
                <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-950/40 flex items-center justify-center text-red-500 mb-2 shadow-sm">
                    <span class="material-symbols-outlined text-4xl">warning</span>
                </div>
                <div class="space-y-2">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">هل تريد الحذف بالفعل؟</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        سيتم حذف حسابات الـ {{ $roleTitlePlural }} المحددة نهائياً من النظام. لا يمكن التراجع عن هذا الإجراء مطلقاً.
                    </p>
                </div>
                <div class="flex gap-3 w-full mt-4">
                    <label class="flex-1 py-3.5 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-sm cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-750 transition-colors text-center" for="delete-trigger">
                        لا، إلغاء
                    </label>
                    <button type="submit" class="flex-1 py-3.5 px-4 rounded-2xl bg-red-500 text-white font-bold text-sm shadow-lg shadow-red-500/20 hover:bg-red-650 active:scale-95 transition-all">
                        نعم، حذف
                    </button>
                </div>
            </div>
        </div>
    </form>

    <style>
        #delete-confirmation-modal {
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }
        #delete-trigger:checked ~ #delete-confirmation-modal {
            opacity: 1;
            visibility: visible;
        }
        #delete-trigger:checked ~ #delete-confirmation-modal .modal-content {
            transform: scale(1);
        }
        .checkbox-custom:checked {
            background-color: #f2f20d !important;
            border-color: #f2f20d !important;
            color: #1a2633 !important;
        }
    </style>

    <script>
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
            const checkboxes = document.querySelectorAll('.select-user-checkbox');
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            const trashContainer = document.getElementById('floating-trash-container');
            
            if (anyChecked) {
                trashContainer.classList.remove('scale-0');
                trashContainer.classList.add('scale-100');
            } else {
                trashContainer.classList.remove('scale-100');
                trashContainer.classList.add('scale-0');
            }
        }

        // Live search filter
        document.getElementById('user-search').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.user-label-item').forEach(item => {
                const name = item.getAttribute('data-search-name').toLowerCase();
                const extra = item.getAttribute('data-search-extra').toLowerCase();
                if (name.includes(query) || extra.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
@endsection
