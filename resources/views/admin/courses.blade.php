@extends('layouts.admin')

@section('title', 'الدورات')
@section('header-title', 'الدورات')
@section('header-subtitle', 'إدارة البرامج والدورات التدريبية')

@section('content')

{{-- ===== Action Buttons ===== --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Add New Course --}}
    <a href="{{ route('admin.courses.create') }}" class="flex items-center justify-between p-5 rounded-3xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 shadow-soft hover:border-primary/45 hover:shadow-lg transition-all group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary flex items-center justify-center text-primary-content shadow-glow group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-2xl">add</span>
            </div>
            <div class="flex flex-col">
                <span class="text-base font-bold text-slate-850 dark:text-white">إضافة دورة جديدة</span>
                <span class="text-xs text-slate-400 mt-0.5">إنشاء برنامج تدريبي جديد في أحد الأقسام</span>
            </div>
        </div>
        <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform">arrow_back</span>
    </a>

    {{-- Assign HOD --}}
    <a href="{{ route('admin.courses.assign-hod') }}" class="flex items-center justify-between p-5 rounded-3xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 shadow-soft hover:border-primary/45 hover:shadow-lg transition-all group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-2xl">supervisor_account</span>
            </div>
            <div class="flex flex-col">
                <span class="text-base font-bold text-slate-850 dark:text-white">تخصيص رئيس القسم</span>
                <span class="text-xs text-slate-400 mt-0.5">تعيين وإدارة رؤساء الأقسام للبرامج</span>
            </div>
        </div>
        <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform">arrow_back</span>
    </a>
</div>

{{-- ===== Courses List Header ===== --}}
<div class="flex items-center justify-between mt-6 mb-3 px-1">
    <div class="flex items-center gap-2">
        <span class="w-1.5 h-6 bg-[#f2f20d] rounded-full shadow-glow"></span>
        <h3 class="text-base font-bold text-slate-800 dark:text-white">الدورات والبرامج الحالية</h3>
    </div>
    <span class="text-xs font-extrabold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-555 dark:text-slate-400">{{ count($programs) }} برنامج تدريبي</span>
</div>

{{-- ===== Courses Cards ===== --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($programs as $program)
        @php
            $icons = ['code', 'design_services', 'language', 'analytics', 'school', 'science', 'psychology', 'brush'];
            $colors = [
                'bg-blue-50 dark:bg-blue-900/20 text-blue-500',
                'bg-purple-50 dark:bg-purple-900/20 text-purple-500',
                'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500',
                'bg-orange-50 dark:bg-orange-900/20 text-orange-500',
                'bg-rose-50 dark:bg-rose-900/20 text-rose-500',
                'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-500',
            ];
            $icon = $icons[$loop->index % count($icons)];
            $color = $colors[$loop->index % count($colors)];
        @endphp
        <div onclick="showCourseDetails({{ json_encode($program) }}, '{{ $color }}', '{{ $icon }}')" class="cursor-pointer flex flex-col justify-between p-6 rounded-3xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 shadow-soft hover:border-primary/45 hover:shadow-xl transition-all duration-300 group relative overflow-hidden">
            <!-- Dynamic left hover accent line -->
            <div class="absolute right-0 top-0 bottom-0 w-1 bg-transparent group-hover:bg-[#f2f20d] transition-colors"></div>

            <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-2xl {{ $color }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform duration-300 shadow-sm">
                    <span class="material-symbols-outlined text-[28px]">{{ $icon }}</span>
                </div>
                
                <form action="{{ route('admin.courses.delete', $program->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الدورة؟')" onclick="event.stopPropagation()" class="z-10">
                    @csrf
                    <button type="submit" class="w-9 h-9 rounded-full flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all active:scale-90">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </form>
            </div>
            
            <div class="flex flex-col mt-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-1 block">{{ $program->department_name }}</span>
                <h4 class="text-lg font-bold text-slate-850 dark:text-white leading-snug group-hover:text-primary-dark dark:group-hover:text-primary transition-colors">{{ $program->name }}</h4>
                <div class="flex items-center gap-2 mt-4">
                    <span class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-extrabold border border-slate-100/50 dark:border-slate-700/50">{{ $program->course_count }} مواد</span>
                    <span class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-extrabold border border-slate-100/50 dark:border-slate-700/50">{{ $program->total_hours }} ساعة معتمدة</span>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full flex flex-col items-center justify-center py-16 gap-4 bg-white dark:bg-surface-dark rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft text-center">
            <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-300">
                <span class="material-symbols-outlined text-4xl">school</span>
            </div>
            <div class="flex flex-col gap-1">
                <p class="text-base font-bold text-slate-800 dark:text-white">لا توجد دورات تدريبية حالياً</p>
                <p class="text-xs text-slate-400">يمكنك البدء في إعداد المنهج التعليمي بإضافة أول دورة.</p>
            </div>
            <a href="{{ route('admin.courses.create') }}" class="px-5 py-2.5 rounded-full bg-primary text-primary-content text-xs font-black shadow-glow hover:scale-105 active:scale-95 transition-all">أضف دورة جديدة</a>
        </div>
    @endforelse
</div>

{{-- ===== Course Details Modal ===== --}}
<div id="course-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="modal-card">
        <!-- Header -->
        <div class="p-6 pb-4 flex items-start justify-between border-b border-slate-100 dark:border-slate-800/85">
            <div class="flex items-center gap-3">
                <div id="modal-icon-container" class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span id="modal-icon" class="material-symbols-outlined text-[24px]">school</span>
                </div>
                <div class="flex flex-col">
                    <h3 id="modal-title" class="text-lg font-bold text-slate-850 dark:text-white leading-tight"></h3>
                    <span id="modal-dept" class="text-xs font-bold text-slate-400 mt-1"></span>
                </div>
            </div>
            <button onclick="closeCourseModal()" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 flex flex-col gap-5 overflow-y-auto max-h-[60vh] hide-scrollbar">
            <!-- Program details stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex flex-col items-center justify-center text-center border border-slate-100/50 dark:border-slate-800/30">
                    <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 mb-1">class</span>
                    <span id="modal-courses-count" class="text-sm font-black text-slate-800 dark:text-white"></span>
                    <span class="text-[10px] font-bold text-slate-400 mt-0.5">عدد المواد الدراسية</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex flex-col items-center justify-center text-center border border-slate-100/50 dark:border-slate-800/30">
                    <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 mb-1">schedule</span>
                    <span id="modal-hours" class="text-sm font-black text-slate-800 dark:text-white"></span>
                    <span class="text-[10px] font-bold text-slate-400 mt-0.5">إجمالي الساعات المعتمدة</span>
                </div>
            </div>

            <!-- Program Description -->
            <div class="flex flex-col gap-2">
                <h4 class="text-xs font-bold text-slate-450 dark:text-slate-500">وصف البرنامج التدريبي</h4>
                <p id="modal-desc" class="text-xs text-slate-600 dark:text-slate-350 leading-relaxed bg-slate-50 dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100/50 dark:border-slate-800/30">
                </p>
            </div>

            <!-- Subjects / Courses list -->
            <div class="flex flex-col gap-3">
                <h4 class="text-xs font-bold text-slate-450 dark:text-slate-500">المواد والمناهج الدراسية المشمولة</h4>
                <div id="modal-subjects-list" class="flex flex-col gap-2.5">
                    <!-- Dynamic subject items -->
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Spacer --}}
<div class="h-8"></div>

@endsection

@push('scripts')
<script>
    setTimeout(() => {
        document.querySelectorAll('.alert-toast').forEach(el => {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        });
    }, 4000);

    function showCourseDetails(program, colorClass, iconName) {
        const modal = document.getElementById('course-modal');
        const modalCard = document.getElementById('modal-card');
        
        // Set text details
        document.getElementById('modal-title').textContent = program.name;
        document.getElementById('modal-dept').textContent = program.department_name;
        document.getElementById('modal-courses-count').textContent = program.course_count + ' مواد';
        document.getElementById('modal-hours').textContent = program.total_hours + ' ساعة';
        
        // Set dynamic description
        if (program.description) {
            document.getElementById('modal-desc').textContent = program.description;
        } else {
            document.getElementById('modal-desc').textContent = 'هذا البرنامج يهدف لتزويد الطلاب بالخبرات الأكاديمية والعملية اللازمة للنجاح والتميز في سوق العمل في تخصص ' + program.name + '.';
        }
        
        // Configure header icon styles
        const iconContainer = document.getElementById('modal-icon-container');
        iconContainer.className = 'w-12 h-12 rounded-2xl flex items-center justify-center ' + colorClass;
        document.getElementById('modal-icon').textContent = iconName;
        
        // Build subjects list
        const subjectsList = document.getElementById('modal-subjects-list');
        subjectsList.innerHTML = '';
        
        if (program.courses_list && program.courses_list.length > 0) {
            program.courses_list.forEach(subject => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100/50 dark:border-slate-800/30';
                item.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-[#f2f20d] rounded-full"></span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">${subject.course_name}</span>
                    </div>
                    <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">${subject.course_code || 'SUB-101'}</span>
                `;
                subjectsList.appendChild(item);
            });
        } else {
            subjectsList.innerHTML = `
                <div class="text-center p-6 bg-slate-50 dark:bg-slate-800/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 text-slate-400 text-xs font-bold">
                    لا توجد مواد مضافة لهذا البرنامج حالياً
                </div>
            `;
        }
        
        // Show Modal with transition
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        }, 20);
    }

    function closeCourseModal() {
        const modal = document.getElementById('course-modal');
        const modalCard = document.getElementById('modal-card');
        
        modal.classList.remove('opacity-100');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close when clicking outside card
    document.getElementById('course-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCourseModal();
        }
    });
</script>
@endpush
