@extends('layouts.admin')
@section('title', 'سجل المحاضرات')

@section('content')
<div class="p-6 font-Cairo max-w-7xl mx-auto flex flex-col gap-6">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-primary/20 text-slate-900 dark:text-primary flex items-center justify-center shadow-sm">
                <i class="fa-solid fa-chalkboard-user text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-slate-850 dark:text-white leading-tight">سجل المحاضرات والدروس</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">تصفح واستعرض المحاضرات المرفوعة حسب القسم، الدورة، السنة، والمادة</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-4 py-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-sm">
                إجمالي المحاضرات: <span class="text-primary-dark dark:text-primary font-black">{{ $lectures->count() }}</span>
            </span>
        </div>
    </div>

    {{-- ===== Cascaded Filter Form ===== --}}
    <div class="bg-white dark:bg-surface-dark p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col gap-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3">
            <h3 class="text-xs font-extrabold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <i class="fa-solid fa-filter text-primary-dark dark:text-primary"></i> تصفية المحاضرات
            </h3>
            @if($selectedDept || $selectedProgram || $selectedYear || $selectedCourse)
                <a href="{{ route('admin.lectures') }}" class="text-xs font-bold text-rose-500 hover:text-rose-600 transition-colors flex items-center gap-1">
                    <i class="fa-solid fa-rotate-left"></i> إعادة ضبط التصفية
                </a>
            @endif
        </div>

        <form id="filter-form" method="GET" action="{{ route('admin.lectures') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- 1. القسم الأكاديمي --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">1. القسم الأكاديمي</label>
                <select name="department_id" id="filter-dept" onchange="onDepartmentChange()" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-3 px-4 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                    <option value="">جميع الأقسام الأكاديمية</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->department_id }}" {{ $selectedDept == $d->department_id ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 2. الدورة / الفرع --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">2. الدورة / الفرع</label>
                <select name="program_id" id="filter-program" onchange="onProgramChange()" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-3 px-4 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                    <option value="">جميع الدورات</option>
                    @foreach($programs as $p)
                        <option value="{{ $p->id }}" data-dept="{{ $p->department_id }}" {{ $selectedProgram == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 3. السنة الدراسية --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">3. السنة الدراسية</label>
                <select name="year" id="filter-year" onchange="onYearChange()" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-3 px-4 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                    <option value="">جميع السنوات</option>
                    <option value="1" {{ $selectedYear == '1' ? 'selected' : '' }}>السنة الأولى</option>
                    <option value="2" {{ $selectedYear == '2' ? 'selected' : '' }}>السنة الثانية</option>
                </select>
            </div>

            {{-- 4. المادة الدراسية --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">4. المادة الدراسية</label>
                <select name="course_id" id="filter-course" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-3 px-4 text-xs font-bold text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer">
                    <option value="">جميع المواد الدراسية</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->course_id }}"
                                data-depts="{{ implode(',', $c->department_ids) }}"
                                data-progs="{{ implode(',', $c->program_ids) }}"
                                data-year="{{ $c->year }}"
                                data-teachers="{{ $c->teacher_names }}"
                                {{ $selectedCourse == $c->course_id ? 'selected' : '' }}>
                            {{ $c->title }} {{ $c->teacher_names ? '('.$c->teacher_names.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

        </form>

        {{-- Active Course Teacher Info Banner --}}
        @if(!empty($assignedTeachers))
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-extrabold text-amber-600 dark:text-amber-400 uppercase">استاذ المادة المختارة:</span>
                        <h4 class="text-sm font-extrabold text-slate-850 dark:text-white mt-0.5">
                            {{ implode('، ', $assignedTeachers) }}
                        </h4>
                    </div>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 shadow-sm border border-slate-200/50 dark:border-slate-700/50">
                    {{ $lectures->count() }} محاضرة مضافة
                </span>
            </div>
        @endif
    </div>

    {{-- ===== Lectures Grid / Cards ===== --}}
    @if($lectures->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($lectures as $l)
                <div class="bg-white dark:bg-surface-dark rounded-3xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between gap-4 group">
                    <div class="flex flex-col gap-3">
                        
                        {{-- Top Header info --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-primary/20 text-slate-900 dark:text-primary flex items-center justify-center font-bold flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-extrabold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ $l->course_title }}</span>
                                    <h3 class="text-sm font-extrabold text-slate-850 dark:text-white line-clamp-1 mt-0.5">{{ $l->title }}</h3>
                                </div>
                            </div>
                        </div>

                        {{-- Description if present --}}
                        @if($l->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2 bg-slate-50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-100/60 dark:border-slate-800/40">
                                {{ $l->description }}
                            </p>
                        @endif

                        {{-- Metadata: Teacher Name & Date --}}
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-400">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-user-tie text-amber-500"></i>
                                <span class="text-slate-700 dark:text-slate-300 font-extrabold">{{ $l->teacher_name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar"></i>
                                <span>{{ \Carbon\Carbon::parse($l->created_at)->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Attachment section with View and Download buttons --}}
                    <div class="flex flex-col gap-2">
                        @if($l->file_path || $l->content_url)
                            @php
                                $rawUrl = $l->file_path ?: $l->content_url;
                                $isExternal = str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://');
                                if ($isExternal) {
                                    $finalUrl = $rawUrl;
                                } else {
                                    $path = ltrim($rawUrl, '/');
                                    if (!str_starts_with($path, 'storage/')) {
                                        $path = 'storage/' . $path;
                                    }
                                    $finalUrl = asset($path);
                                }
                            @endphp

                            <div class="grid grid-cols-2 gap-2">
                                {{-- View / Preview Button --}}
                                <a href="{{ $finalUrl }}" target="_blank"
                                   class="py-2.5 px-3 rounded-2xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 dark:text-blue-300 text-xs font-extrabold flex items-center justify-center gap-1.5 border border-blue-500/30 transition-all">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    <span>معاينة</span>
                                </a>

                                {{-- Download Button --}}
                                <a href="{{ $finalUrl }}" download target="_blank"
                                   class="py-2.5 px-3 rounded-2xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-700 dark:text-amber-300 text-xs font-extrabold flex items-center justify-center gap-1.5 border border-amber-500/40 transition-all">
                                    <i class="fa-solid fa-download text-xs"></i>
                                    <span>تنزيل</span>
                                </a>
                            </div>
                        @else
                            <div class="text-center py-2 text-[11px] font-semibold text-slate-400 bg-slate-50 dark:bg-slate-900/30 rounded-2xl">
                                محاضرة بدون ملف مرفق
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-surface-dark rounded-3xl p-12 text-center border border-slate-100 dark:border-slate-800 flex flex-col items-center justify-center gap-3">
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 text-2xl">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-850 dark:text-white">لا توجد محاضرات متاحة</h3>
            <p class="text-xs font-semibold text-slate-400 max-w-sm">اختر قسم ودورة ومادة لعرض المحاضرات والملفات المرفوقة بها</p>
        </div>
    @endif

</div>

@push('scripts')
<script>
    function onDepartmentChange() {
        document.getElementById('filter-program').value = '';
        document.getElementById('filter-course').value = '';
        document.getElementById('filter-form').submit();
    }

    function onProgramChange() {
        document.getElementById('filter-course').value = '';
        document.getElementById('filter-form').submit();
    }

    function onYearChange() {
        document.getElementById('filter-course').value = '';
        document.getElementById('filter-form').submit();
    }
</script>
@endpush
@endsection
