@extends('layouts.admin')

@section('title', 'الفصول والمواد')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center justify-between mb-2">
        {{-- يمين: سهم الرجوع + العنوان --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
                <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
            </a>
            <div class="flex flex-col">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white leading-tight">الفصول الدراسية والمواد</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500 mt-1">عرض وإدارة المواد الدراسية حسب القسم والفصل</span>
            </div>
        </div>

        {{-- يسار: زر إضافة مادة --}}
        <button onclick="openAddSubjectModal()"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary text-primary-content text-xs font-bold rounded-full shadow-glow hover:scale-105 active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[18px]">add</span>
            إضافة مادة جديدة
        </button>
    </div>

    {{-- ===== Filters ===== --}}
    <form method="GET" action="{{ route('admin.semesters-subjects') }}" id="filter-form" class="bg-white dark:bg-surface-dark p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft flex flex-col gap-4 transition-colors">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Department Filter --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">القسم الأكاديمي</label>
                <select name="department_id" id="dept-select" onchange="filterPrograms(this.value)"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none">
                    <option value="">جميع الأقسام</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}" {{ $selectedDept == $dept->department_id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Program Filter --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">الدورة / البرنامج</label>
                <select name="program_id" id="program-select" onchange="document.getElementById('filter-form').submit()"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none">
                    <option value="">جميع الدورات</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}"
                                data-dept="{{ $prog->department_id }}"
                                {{ $selectedProgram == $prog->id ? 'selected' : '' }}>
                            {{ $prog->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Year Filter --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">السنة الدراسية</label>
                <select name="year" onchange="document.getElementById('filter-form').submit()"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none">
                    <option value="">جميع السنوات</option>
                    <option value="1" {{ $selectedYear == '1' ? 'selected' : '' }}>السنة الأولى</option>
                    <option value="2" {{ $selectedYear == '2' ? 'selected' : '' }}>السنة الثانية</option>
                </select>
            </div>
        </div>

        {{-- Semester Tabs --}}
        @php
            $sem1 = $semesters->firstWhere(fn($s) => str_contains($s->name, 'أول'))  ?? $semesters->first();
            $sem2 = $semesters->firstWhere(fn($s) => str_contains($s->name, 'ثاني')) ?? $semesters->skip(1)->first();
        @endphp
        <div class="flex flex-col gap-2 mt-2">
            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">الفصل الدراسي</label>
            <div class="grid grid-cols-2 gap-3">
                {{-- فصل أول --}}
                @if($sem1)
                <button type="button"
                        onclick="document.querySelector('[name=semester_id]').value='{{ $sem1->semester_id }}'; document.getElementById('filter-form').submit()"
                        class="py-3.5 px-4 rounded-2xl text-sm font-bold text-center transition-all flex items-center justify-center gap-2
                        {{ $selectedSemester == $sem1->semester_id
                            ? 'bg-primary text-primary-content shadow-glow scale-[1.02]'
                            : 'bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-primary/50 hover:text-primary' }}">
                    <span class="material-symbols-outlined text-[18px]">looks_one</span>
                    الفصل الأول
                </button>
                @else
                <button type="button" disabled
                        class="py-3.5 px-4 rounded-2xl text-sm font-bold text-center bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-400 opacity-50 cursor-not-allowed flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">looks_one</span>
                    الفصل الأول
                </button>
                @endif

                {{-- فصل ثاني --}}
                @if($sem2)
                <button type="button"
                        onclick="document.querySelector('[name=semester_id]').value='{{ $sem2->semester_id }}'; document.getElementById('filter-form').submit()"
                        class="py-3.5 px-4 rounded-2xl text-sm font-bold text-center transition-all flex items-center justify-center gap-2
                        {{ $selectedSemester == $sem2->semester_id
                            ? 'bg-primary text-primary-content shadow-glow scale-[1.02]'
                            : 'bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-primary/50 hover:text-primary' }}">
                    <span class="material-symbols-outlined text-[18px]">looks_two</span>
                    الفصل الثاني
                </button>
                @else
                <button type="button" disabled
                        class="py-3.5 px-4 rounded-2xl text-sm font-bold text-center bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-400 opacity-50 cursor-not-allowed flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">looks_two</span>
                    الفصل الثاني
                </button>
                @endif
            </div>
        </div>
        <input type="hidden" name="semester_id" value="{{ $selectedSemester }}" />
    </form>

    {{-- ===== Subjects Header ===== --}}
    <div class="flex items-center justify-between mt-6 mb-3 px-1">
        <div class="flex items-center gap-2">
            <span class="w-1.5 h-6 bg-[#f2f20d] rounded-full shadow-glow"></span>
            <h3 class="text-base font-bold text-slate-800 dark:text-white">المواد الدراسية الحالية</h3>
        </div>
        <span class="text-xs font-extrabold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-555 dark:text-slate-400">{{ count($courses) }} مادة</span>
    </div>

    {{-- ===== Subjects List ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($courses as $course)
            @php
                $subjectIcons = ['calculate', 'code', 'storage', 'translate', 'science', 'brush', 'psychology', 'analytics'];
                $subjectColors = [
                    'bg-blue-50 dark:bg-blue-900/20 text-blue-500',
                    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500',
                    'bg-purple-50 dark:bg-purple-900/20 text-purple-500',
                    'bg-orange-50 dark:bg-orange-900/20 text-orange-500',
                    'bg-rose-50 dark:bg-rose-900/20 text-rose-500',
                    'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-500',
                ];
                $sIcon = $subjectIcons[$loop->index % count($subjectIcons)];
                $sColor = $subjectColors[$loop->index % count($subjectColors)];
            @endphp
            <div onclick="showSubjectDetails({{ json_encode($course) }}, '{{ $sColor }}', '{{ $sIcon }}')" class="cursor-pointer flex flex-col justify-between p-6 rounded-3xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 shadow-soft hover:border-primary/45 hover:shadow-xl transition-all duration-300 group relative overflow-hidden">
                <!-- Hover bar -->
                <div class="absolute right-0 top-0 bottom-0 w-1 bg-transparent group-hover:bg-[#f2f20d] transition-colors"></div>

                <div class="absolute top-4 left-4 flex flex-col gap-1 z-10">
                    <button type="button" onclick="event.stopPropagation(); openEditSubjectModal({{ json_encode($course) }})" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                    <form action="{{ route('admin.semesters-subjects.delete', $course->course_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المادة؟')" onclick="event.stopPropagation()">
                        @csrf
                        <button type="submit" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </form>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl {{ $sColor }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform duration-300 shadow-sm">
                        <span class="material-symbols-outlined text-[24px]">{{ $sIcon }}</span>
                    </div>
                    
                    <div class="flex-1 min-w-0 font-Cairo">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mb-1 block">{{ $course->semester_name }}</span>
                        <h4 class="text-base font-bold text-slate-850 dark:text-white leading-snug group-hover:text-primary-dark dark:group-hover:text-primary transition-colors truncate">{{ $course->title }}</h4>
                        
                        <div class="flex items-center gap-1.5 mt-2 text-slate-500 dark:text-slate-400">
                            <span class="material-symbols-outlined text-[14px]">person</span>
                            <span class="text-xs font-semibold truncate">{{ $course->teacher_name ?? 'غير محدد' }}</span>
                        </div>

                        @if(!empty($course->departments_list) && count($course->departments_list) > 0)
                            <div class="flex items-center gap-1.5 mt-1 text-slate-500 dark:text-slate-400">
                                <span class="material-symbols-outlined text-[14px] text-slate-400">apartment</span>
                                <span class="text-xs font-semibold truncate">{{ implode(', ', $course->departments_list->toArray()) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-50 dark:border-slate-800/60 pt-4 mt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-555 dark:text-slate-400 font-extrabold border border-slate-100/50 dark:border-slate-700/50">
                            {{ count($course->lessons_list) }} محاضرات
                        </span>
                        <span class="text-[10px] px-2.5 py-1 rounded-lg bg-primary/10 text-primary-dark dark:text-primary font-extrabold border border-primary/20">
                            {{ $course->hours ?? 0 }} ساعة مخصصة
                        </span>
                        <span class="text-[10px] px-2.5 py-1 rounded-lg font-black bg-blue-50 text-blue-650 dark:bg-blue-950/20 dark:text-blue-400">
                            {{ $course->year == 2 ? 'السنة الثانية' : 'السنة الأولى' }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16 gap-4 bg-white dark:bg-surface-dark rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft text-center">
                <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-350">
                    <span class="material-symbols-outlined text-4xl">menu_book</span>
                </div>
                <div class="flex flex-col gap-1 font-Cairo">
                    <p class="text-base font-bold text-slate-850 dark:text-white">لا توجد مواد دراسية حالياً</p>
                    <p class="text-xs text-slate-400">تأكد من ضبط خيارات التصفية أو القسم المختار.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- ===== Subject Details Modal ===== --}}
    <div id="subject-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
        <div class="bg-white dark:bg-surface-dark w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="modal-card">
            <!-- Header -->
            <div class="p-6 pb-4 flex items-start justify-between border-b border-slate-100 dark:border-slate-800/85">
                <div class="flex items-center gap-3 font-Cairo">
                    <div id="modal-icon-container" class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                        <span id="modal-icon" class="material-symbols-outlined text-[24px]">calculate</span>
                    </div>
                    <div class="flex flex-col">
                        <h3 id="modal-title" class="text-lg font-bold text-slate-850 dark:text-white leading-tight"></h3>
                        <span id="modal-semester" class="text-xs font-bold text-slate-400 mt-1"></span>
                    </div>
                </div>
                <button onclick="closeSubjectModal()" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 flex flex-col gap-5 overflow-y-auto max-h-[60vh] hide-scrollbar font-Cairo">
                <!-- Details Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex items-center gap-3 border border-slate-100/50 dark:border-slate-800/30">
                        <span class="material-symbols-outlined text-primary text-[22px]">person</span>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400">المدرس المسؤول</span>
                            <span id="modal-teacher" class="text-xs font-black text-slate-800 dark:text-white mt-0.5"></span>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex items-center gap-3 border border-slate-100/50 dark:border-slate-800/30">
                        <span class="material-symbols-outlined text-indigo-500 text-[22px]">school</span>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400">السنة الدراسية</span>
                            <span id="modal-level" class="text-xs font-black text-slate-800 dark:text-white mt-0.5"></span>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex items-center gap-3 border border-slate-100/50 dark:border-slate-800/30 col-span-2">
                        <span class="material-symbols-outlined text-emerald-500 text-[22px]">apartment</span>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400">الأقسام الأكاديمية</span>
                            <span id="modal-department" class="text-xs font-black text-slate-800 dark:text-white mt-0.5"></span>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex items-center gap-3 border border-slate-100/50 dark:border-slate-800/30 col-span-2">
                        <span class="material-symbols-outlined text-blue-500 text-[22px]">schedule</span>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400">الساعات المخصصة للمادة</span>
                            <span id="modal-hours" class="text-xs font-black text-slate-800 dark:text-white mt-0.5"></span>
                        </div>
                    </div>
                </div>

                <!-- Subject Description -->
                <div class="flex flex-col gap-2">
                    <h4 class="text-xs font-bold text-slate-450 dark:text-slate-500">تفاصيل ووصف المادة</h4>
                    <p id="modal-desc" class="text-xs text-slate-600 dark:text-slate-350 leading-relaxed bg-slate-50 dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100/50 dark:border-slate-800/30">
                    </p>
                </div>

                <!-- Go to Lectures Page Button -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <a id="modal-lectures-btn" href="#" class="w-full py-3.5 px-5 rounded-2xl bg-primary text-slate-900 font-extrabold text-xs flex items-center justify-center gap-2.5 shadow-md hover:bg-primary-dark transition-all">
                        <i class="fa-solid fa-chalkboard-user text-base"></i>
                        <span>انتقال إلى سجل المحاضرات لهذه المادة</span>
                        <i class="fa-solid fa-arrow-left text-xs mr-auto"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Add Subject Modal ===== --}}
    <div id="add-subject-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
        <div class="bg-white dark:bg-surface-dark w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="add-modal-card">
            <!-- Header -->
            <div class="p-6 pb-4 flex items-start justify-between border-b border-slate-100 dark:border-slate-800/85">
                <div class="flex items-center gap-3 font-Cairo">
                    <div class="w-12 h-12 rounded-2xl bg-primary/20 text-primary flex items-center justify-center shadow-sm">
                        <span class="material-symbols-outlined text-[24px]">menu_book</span>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-lg font-bold text-slate-850 dark:text-white leading-tight">إضافة مادة دراسية جديدة</h3>
                        <span class="text-xs font-bold text-slate-400 mt-1">تعبئة تفاصيل المادة وتعيين المدرس والفرع</span>
                    </div>
                </div>
                <button onclick="closeAddSubjectModal()" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.semesters-subjects.store') }}" method="POST" class="p-6 flex flex-col gap-4 overflow-y-auto max-h-[70vh] hide-scrollbar font-Cairo">
                @csrf
                
                {{-- Subject Name --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">اسم المادة الدراسية</label>
                    <input type="text" name="title" required placeholder="مثال: أساسيات البرمجة، خوارزميات..."
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white transition-all outline-none animate-input" />
                </div>

                {{-- Subject Description --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">وصف المادة</label>
                    <textarea name="description" rows="3" placeholder="أدخل تفاصيل ومحتوى هذه المادة..."
                              class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-855 dark:text-white transition-all outline-none resize-none"></textarea>
                </div>

                {{-- Hours --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">عدد الساعات المخصصة</label>
                    <input type="number" name="hours" min="1" required value="40"
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white transition-all outline-none" />
                </div>

                {{-- Year --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">السنة الدراسية</label>
                    <select name="year" required
                            class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر السنة...</option>
                        <option value="1">السنة الأولى</option>
                        <option value="2">السنة الثانية</option>
                    </select>
                </div>

                {{-- Department --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">القسم الأكاديمي</label>
                    <select id="add-modal-dept-select" onchange="filterModalCourses(this.value, 'add-modal-program-select')" required
                            class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر القسم الأكاديمي أولاً...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course / Program --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">الدورة</label>
                    <select name="program_id" id="add-modal-program-select" required
                            class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر الدورة...</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}">{{ $prog->name }} ({{ $prog->department_name ?? 'بدون قسم' }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">الفصل الدراسي</label>
                    <select name="semester_id" required
                            class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر الفصل الدراسي...</option>
                        @foreach($semesters as $sem)
                            @php
                                $displaySemName = str_contains($sem->name, 'ثاني') ? 'فصل ثاني' : (str_contains($sem->name, 'أول') ? 'فصل أول' : $sem->name);
                            @endphp
                            <option value="{{ $sem->semester_id }}">{{ $displaySemName }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Button --}}
                <button type="submit" class="w-full py-4 mt-2 bg-[#f2f20d] text-slate-900 text-sm font-bold rounded-2xl shadow-glow hover:scale-[1.02] active:scale-95 transition-all">
                    تأكيد وإضافة المادة الدراسية
                </button>
            </form>
        </div>
    </div>

    {{-- ===== Edit Subject Modal ===== --}}
    <div id="edit-subject-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
        <div class="bg-white dark:bg-surface-dark w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="edit-modal-card">
            <div class="p-6 pb-4 flex items-start justify-between border-b border-slate-100 dark:border-slate-800/85">
                <div class="flex items-center gap-3 font-Cairo">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center shadow-sm">
                        <span class="material-symbols-outlined text-[24px]">edit</span>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-lg font-bold text-slate-850 dark:text-white leading-tight">تعديل المادة الدراسية</h3>
                        <span class="text-xs font-bold text-slate-400 mt-1">تحديث بيانات المادة الدراسية</span>
                    </div>
                </div>
                <button onclick="closeEditSubjectModal()" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form id="edit-subject-form" method="POST" class="p-6 flex flex-col gap-4 overflow-y-auto max-h-[70vh] hide-scrollbar font-Cairo">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">اسم المادة الدراسية</label>
                    <input type="text" id="edit-title" name="title" required class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white transition-all outline-none animate-input" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">وصف المادة</label>
                    <textarea id="edit-description" name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-855 dark:text-white transition-all outline-none resize-none"></textarea>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">عدد الساعات المخصصة</label>
                    <input type="number" id="edit-hours" name="hours" min="1" required class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white transition-all outline-none" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">السنة الدراسية</label>
                    <select id="edit-year" name="year" required class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="1">السنة الأولى</option>
                        <option value="2">السنة الثانية</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">القسم الأكاديمي</label>
                    <select id="edit-dept-select" onchange="filterModalCourses(this.value, 'edit-program')" required class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر القسم الأكاديمي...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">الدورة</label>
                    <select id="edit-program" name="program_id" required class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر الدورة...</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}">{{ $prog->name }} ({{ $prog->department_name ?? 'بدون قسم' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">الفصل الدراسي</label>
                    <select id="edit-semester" name="semester_id" required class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        @foreach($semesters as $sem)
                            @php
                                $displaySemName = str_contains($sem->name, 'ثاني') ? 'فصل ثاني' : (str_contains($sem->name, 'أول') ? 'فصل أول' : $sem->name);
                            @endphp
                            <option value="{{ $sem->semester_id }}">{{ $displaySemName }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full py-4 mt-2 bg-[#f2f20d] text-slate-900 text-sm font-bold rounded-2xl shadow-glow hover:scale-[1.02] active:scale-95 transition-all">
                    حفظ التعديلات
                </button>
            </form>
        </div>
    </div>

    {{-- Spacer --}}
    <div class="h-8"></div>

@endsection

@push('scripts')
<script>
    function showSubjectDetails(course, colorClass, iconName) {
        const modal = document.getElementById('subject-modal');
        const modalCard = document.getElementById('modal-card');
        
        // Set text details
        document.getElementById('modal-title').textContent = course.title;
        document.getElementById('modal-semester').textContent = course.semester_name;
        document.getElementById('modal-teacher').textContent = course.teacher_name || 'لم يعين مدرس بعد';
        document.getElementById('modal-level').textContent = course.year == 2 ? 'السنة الثانية' : 'السنة الأولى';
        document.getElementById('modal-lectures-btn').href = '{{ route('admin.lectures') }}?course_id=' + course.course_id;
        
        // Departments and hours
        const depts = course.departments_list && course.departments_list.length > 0 
            ? course.departments_list.join(', ') 
            : 'غير محدد';
        document.getElementById('modal-department').textContent = depts;
        document.getElementById('modal-hours').textContent = (course.hours || 0) + ' ساعة دراسية معتمدة';
        
        // Set dynamic description
        if (course.description) {
            document.getElementById('modal-desc').textContent = course.description;
        } else {
            document.getElementById('modal-desc').textContent = 'هذه المادة الدراسية تغطي كافة المفاهيم الأساسية والتطبيقات العملية اللازمة لتمكين الطلاب في مجالات تخصصهم الأكاديمي والمهني.';
        }
        
        // Configure header icon styles
        const iconContainer = document.getElementById('modal-icon-container');
        iconContainer.className = 'w-12 h-12 rounded-2xl flex items-center justify-center ' + colorClass;
        document.getElementById('modal-icon').textContent = iconName;
        
        // Show Modal with transition
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        }, 20);
    }

    function closeSubjectModal() {
        const modal = document.getElementById('subject-modal');
        const modalCard = document.getElementById('modal-card');
        
        modal.classList.remove('opacity-100');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function openAddSubjectModal() {
        const modal = document.getElementById('add-subject-modal');
        const modalCard = document.getElementById('add-modal-card');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        }, 20);
    }

    function closeAddSubjectModal() {
        const modal = document.getElementById('add-subject-modal');
        const modalCard = document.getElementById('add-modal-card');
        
        modal.classList.remove('opacity-100');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function filterModalCourses(deptId, targetSelectId) {
        const sel = document.getElementById(targetSelectId);
        if (!sel) return;

        let selectedOptionStillVisible = false;

        [...sel.options].forEach(opt => {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            const optDept = opt.getAttribute('data-dept');
            const matches = !deptId || String(optDept) === String(deptId);
            opt.style.display = matches ? '' : 'none';
            if (matches && sel.value && opt.value === sel.value) {
                selectedOptionStillVisible = true;
            }
        });

        if (!selectedOptionStillVisible) {
            sel.value = '';
        }
    }

    function openEditSubjectModal(course) {
        const modal = document.getElementById('edit-subject-modal');
        const modalCard = document.getElementById('edit-modal-card');
        
        document.getElementById('edit-subject-form').action = `/admin/semesters-subjects/update/${course.course_id}`;
        document.getElementById('edit-title').value = course.title || '';
        document.getElementById('edit-description').value = course.description || '';
        if (document.getElementById('edit-level')) {
            document.getElementById('edit-level').value = course.level || 'مبتدئ';
        }
        document.getElementById('edit-hours').value = course.hours || '40';
        document.getElementById('edit-year').value = course.year || '1';
        document.getElementById('edit-semester').value = course.semester_id || '';
        
        if (course.program_id) {
            const editProgSel = document.getElementById('edit-program');
            if (editProgSel) {
                const opt = editProgSel.querySelector(`option[value="${course.program_id}"]`);
                if (opt) {
                    const deptId = opt.getAttribute('data-dept');
                    if (deptId) {
                        const editDeptSel = document.getElementById('edit-dept-select');
                        if (editDeptSel) editDeptSel.value = deptId;
                        filterModalCourses(deptId, 'edit-program');
                    }
                    editProgSel.value = course.program_id;
                }
            }
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        }, 20);
    }

    function closeEditSubjectModal() {
        const modal = document.getElementById('edit-subject-modal');
        const modalCard = document.getElementById('edit-modal-card');
        
        modal.classList.remove('opacity-100');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // ===== فلترة الدورات حسب القسم =====
    function filterPrograms(deptId) {
        const sel = document.getElementById('program-select');
        let firstVisible = null;

        [...sel.options].forEach(opt => {
            if (opt.value === '') {
                opt.style.display = '';
                return;
            }
            const show = !deptId || opt.dataset.dept === deptId;
            opt.style.display = show ? '' : 'none';
            if (show && !firstVisible) firstVisible = opt;
        });

        // إذا الخيار الحالي مش من هذا القسم → ارجع لـ "جميع الدورات"
        const currentOpt = sel.options[sel.selectedIndex];
        if (currentOpt && currentOpt.value && currentOpt.dataset.dept !== deptId && deptId) {
            sel.value = '';
        }

        // Submit الفورم بعد الفلترة
        document.getElementById('filter-form').submit();
    }

    // تطبيق الفلترة عند تحميل الصفحة (إذا في قسم محدد)
    document.addEventListener('DOMContentLoaded', function () {
        const deptVal = document.getElementById('dept-select').value;
        if (deptVal) {
            [...document.getElementById('program-select').options].forEach(opt => {
                if (opt.value === '') return;
                opt.style.display = (!opt.dataset.dept || opt.dataset.dept === deptVal) ? '' : 'none';
            });
        }
    });

    // Close when clicking outside cards
    document.getElementById('subject-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSubjectModal();
        }
    });

    document.getElementById('add-subject-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddSubjectModal();
        }
    });

    document.getElementById('edit-subject-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditSubjectModal();
        }
    });
</script>
@endpush
