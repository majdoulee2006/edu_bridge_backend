@extends('layouts.admin')

@section('title', 'الرسائل والتعاميم')
@section('header-title', 'رسالة إدارية')
@section('header-subtitle', 'إرسال تعاميم وقرارات إدارية')

@section('content')

    <form action="{{ route('admin.messages.send') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf

        <div class="space-y-3">
            <label class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-lg">alternate_email</span>
                توجيه الرسالة إلى
            </label>
            
            <div class="grid grid-cols-3 gap-3">
                <!-- Everyone -->
                <label class="relative cursor-pointer group">
                    <input checked class="peer recipient-radio sr-only" name="recipient_type" value="all" type="radio" onchange="toggleRecipientList('all')"/>
                    <div class="flex flex-col items-center justify-center p-3 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700 h-28 transition-all duration-300 group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary">
                        <div class="icon-box w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400 mb-2 transition-colors duration-300 peer-checked:bg-primary peer-checked:text-primary-content">
                            <span class="material-symbols-outlined text-xl">groups</span>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 text-center">الجميع</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 text-center">كل المستخدمين</span>
                    </div>
                </label>

                <!-- Departments -->
                <label class="relative cursor-pointer group">
                    <input class="peer recipient-radio sr-only" name="recipient_type" value="departments" type="radio" onchange="toggleRecipientList('departments')"/>
                    <div class="flex flex-col items-center justify-center p-3 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700 h-28 transition-all duration-300 group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary">
                        <div class="icon-box w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400 mb-2 transition-colors duration-300 peer-checked:bg-primary peer-checked:text-primary-content">
                            <span class="material-symbols-outlined text-xl">domain</span>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 text-center">الأقسام</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 text-center">إدارة محددة</span>
                    </div>
                </label>

                <!-- Individuals -->
                <label class="relative cursor-pointer group">
                    <input class="peer recipient-radio sr-only" name="recipient_type" value="individuals" type="radio" onchange="toggleRecipientList('individuals')"/>
                    <div class="flex flex-col items-center justify-center p-3 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700 h-28 transition-all duration-300 group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary">
                        <div class="icon-box w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400 mb-2 transition-colors duration-300 peer-checked:bg-primary peer-checked:text-primary-content">
                            <span class="material-symbols-outlined text-xl">person_search</span>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 text-center">أفراد</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 text-center">تحديد بالاسم</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- ================= RECIPENTS EXPANDABLE LIST ================= -->
        <!-- Departments Selector -->
        <div id="departments-selector" class="hidden flex flex-col gap-3 bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">اختر الأقسام المستهدفة</h3>
            <div class="grid grid-cols-2 gap-2">
                @foreach($departments as $dept)
                    <label class="relative flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-transparent cursor-pointer hover:border-primary/20">
                        <input type="checkbox" name="target_departments[]" value="{{ $dept->department_id }}" class="rounded text-primary focus:ring-primary">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $dept->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Individuals Selector -->
        <div id="individuals-selector" class="hidden flex flex-col gap-4 bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft max-h-[350px] overflow-y-auto hide-scrollbar">
            <div class="relative group">
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <span class="material-symbols-outlined">search</span>
                </span>
                <input id="user-search-input" onkeyup="filterUsers()" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl py-3 pr-12 pl-4 text-xs font-medium focus:ring-2 focus:ring-primary/50 transition-all outline-none placeholder:text-slate-400" placeholder="بحث عن اسم..." type="text"/>
            </div>
            
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between px-1 mb-1">
                    <h2 class="text-xs font-bold text-slate-400 dark:text-slate-500">الأفراد المتاحين</h2>
                    <button type="button" onclick="selectAllUsers()" class="text-xs font-bold text-primary hover:text-primary-dark transition-colors">تحديد الكل</button>
                </div>

                @foreach($users as $user)
                    <label class="relative cursor-pointer group user-item-label" data-name="{{ strtolower($user->full_name) }}">
                        <input type="checkbox" name="target_users[]" value="{{ $user->user_id }}" class="peer sr-only user-checkbox"/>
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-transparent transition-all duration-200 group-hover:shadow-sm peer-checked:border-primary peer-checked:bg-primary/5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/20 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-bold text-sm">
                                    {{ mb_substr($user->full_name, 0, 1) }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $user->full_name }}</span>
                                    <span class="text-[9px] text-slate-400 font-medium">{{ $user->role_name ?? 'موظف' }}</span>
                                </div>
                            </div>
                            <div class="check-circle w-5 h-5 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center text-transparent peer-checked:bg-primary peer-checked:border-primary peer-checked:text-primary-content">
                                <span class="material-symbols-outlined text-xs font-bold">check</span>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Message Compose Box -->
        <div class="bg-surface-light dark:bg-surface-dark rounded-[2rem] p-1 shadow-soft border border-slate-100 dark:border-slate-800/80 transition-colors">
            <div class="p-4 space-y-4">
                <div class="relative group">
                    <label class="text-[11px] font-semibold text-slate-400 px-1 mb-1 block uppercase tracking-wider" for="subject">الموضوع</label>
                    <input name="subject" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-xl border-none focus:ring-2 focus:ring-primary/50 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-slate-100 placeholder:font-normal placeholder:text-slate-400 transition-all" id="subject" placeholder="أدخل عنواناً واضحاً للرسالة..." type="text" required/>
                </div>
                <div class="relative group">
                    <label class="text-[11px] font-semibold text-slate-400 px-1 mb-1 block uppercase tracking-wider" for="message">نص الرسالة</label>
                    <textarea name="message" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-xl border-none focus:ring-2 focus:ring-primary/50 p-4 text-sm leading-relaxed text-slate-800 dark:text-slate-100 resize-none placeholder:text-slate-400 transition-all" id="message" placeholder="اكتب تفاصيل الرسالة الإدارية هنا..." rows="6" oninput="updateCharCount(this)" required></textarea>
                </div>
            </div>
            <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-900/50 rounded-b-[1.7rem] px-4 py-3 mt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="flex gap-2">
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-primary hover:border-primary transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-lg">attach_file</span>
                        <span>ملف مرفق</span>
                        <input type="file" name="attachment" class="hidden">
                    </label>
                </div>
                <span id="char-count" class="text-[10px] font-medium text-slate-400">0 حرف</span>
            </div>
        </div>

        <div class="pt-2 pb-8">
            <button class="group relative w-full overflow-hidden rounded-[1.5rem] bg-primary p-4 transition-all hover:bg-primary-dark active:scale-[0.98] shadow-glow" type="submit">
                <div class="relative z-10 flex items-center justify-center gap-3">
                    <span class="font-bold text-primary-content text-lg">إرسال التعميم الإداري</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-black/10 transition-transform duration-300 group-hover:translate-x-1 group-hover:-translate-y-1">
                        <span class="material-symbols-outlined text-primary-content text-xl rtl:rotate-180">send</span>
                    </div>
                </div>
            </button>
            <p class="text-center text-[10px] text-slate-400 mt-3 flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-sm">info</span>
                سيتم إرسال إشعار فوري لجميع المستلمين المحددين وتثبيت الرسالة
            </p>
        </div>
    </form>

@endsection

@push('scripts')
<script>
    function toggleRecipientList(type) {
        document.getElementById('departments-selector').classList.add('hidden');
        document.getElementById('individuals-selector').classList.add('hidden');

        if (type === 'departments') {
            document.getElementById('departments-selector').classList.remove('hidden');
        } else if (type === 'individuals') {
            document.getElementById('individuals-selector').classList.remove('hidden');
        }
    }

    function filterUsers() {
        const query = document.getElementById('user-search-input').value.toLowerCase();
        const items = document.querySelectorAll('.user-item-label');
        
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            if (name.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function selectAllUsers() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
        checkboxes.forEach(cb => {
            cb.checked = anyUnchecked;
        });
    }

    function updateCharCount(textarea) {
        document.getElementById('char-count').textContent = textarea.value.length + ' حرف';
    }
</script>
@endpush
