@extends('layouts.admin')

@section('title', 'إضافة دورة جديدة')
@section('header-title', 'إضافة دورة جديدة')
@section('header-subtitle', 'إنشاء برنامج أودورة جديدة في أحد الأقسام')

@section('header-actions')
    <a href="{{ route('admin.courses') }}" class="w-10 h-10 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
        <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
    </a>
@endsection

@section('content')

    <form action="{{ route('admin.courses.store') }}" method="POST" class="flex flex-col gap-5">
        @csrf

        {{-- Course Name --}}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">اسم الدورة</label>
            <div class="relative">
                <input name="name" value="{{ old('name') }}" type="text"
                       class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white placeholder:text-slate-400 transition-all outline-none"
                       placeholder="مثال: دورة البرمجة بلغة جافا" required />
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-lg">edit</span>
            </div>
            @error('name')
                <span class="text-xs text-red-500 font-medium">{{ $message }}</span>
            @enderror
        </div>

        {{-- Department --}}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">القسم</label>
            <div class="relative">
                <select name="department_id"
                        class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer" required>
                    <option value="" disabled selected>اختر القسم التابع للدورة</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}" {{ old('department_id') == $dept->department_id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-lg pointer-events-none">expand_more</span>
            </div>
            @error('department_id')
                <span class="text-xs text-red-500 font-medium">{{ $message }}</span>
            @enderror
        </div>

        {{-- Academic Year & Semester --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Academic Year --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">السنة الدراسية</label>
                <div class="relative">
                    <select name="year" required
                            class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر السنة الدراسية</option>
                        <option value="سنة أولى" {{ old('year') == 'سنة أولى' ? 'selected' : '' }}>سنة أولى</option>
                        <option value="سنة ثانية" {{ old('year') == 'سنة ثانية' ? 'selected' : '' }}>سنة ثانية</option>
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-lg pointer-events-none">expand_more</span>
                </div>
            </div>

            {{-- Semester --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">الفصل الدراسي</label>
                <div class="relative">
                    <select name="semester" required
                            class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-semibold text-slate-800 dark:text-white appearance-none transition-all outline-none cursor-pointer">
                        <option value="" disabled selected>اختر الفصل الدراسي</option>
                        <option value="فصل أول" {{ old('semester') == 'فصل أول' ? 'selected' : '' }}>فصل أول</option>
                        <option value="فصل ثاني" {{ old('semester') == 'فصل ثاني' ? 'selected' : '' }}>فصل ثاني</option>
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-lg pointer-events-none">expand_more</span>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">وصف الدورة</label>
            <div class="relative">
                <textarea name="description" rows="4"
                          class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 p-4 text-sm text-slate-800 dark:text-white resize-none placeholder:text-slate-400 transition-all outline-none"
                          placeholder="اكتب وصفاً مختصراً عن محتوى الدورة وأهدافها...">{{ old('description') }}</textarea>
                <span class="material-symbols-outlined absolute left-3 top-3 text-slate-300 text-lg">description</span>
            </div>
        </div>

        {{-- Submit --}}
        <div class="pt-2 pb-8">
            <button type="submit" class="group relative w-full overflow-hidden rounded-[1.5rem] bg-primary p-4 transition-all hover:bg-primary-dark active:scale-[0.98] shadow-glow">
                <div class="relative z-10 flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined text-primary-content text-xl">add_circle</span>
                    <span class="font-bold text-primary-content text-lg">حفظ وإضافة الدورة</span>
                </div>
            </button>
        </div>
    </form>

@endsection
