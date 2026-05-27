@extends('layouts.admin')

@section('title', 'تخصيص رئيس قسم')
@section('header-title', 'تخصيص رئيس قسم')
@section('header-subtitle', 'تعيين رئيس قسم لإدارة القسم الأكاديمي')

@section('header-actions')
    <a href="{{ route('admin.courses') }}" class="w-10 h-10 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
        <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
    </a>
@endsection

@section('content')

    <form action="{{ route('admin.courses.assign-hod.store') }}" method="POST" class="flex flex-col gap-5">
        @csrf

        {{-- Department Selector --}}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">القسم المستهدف</label>
            <div class="relative">
                <select id="department-select" name="department_id"
                        class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3.5 px-4 text-sm font-bold text-slate-800 dark:text-white appearance-none transition-all outline-none" required>
                    <option value="" disabled selected>اختر القسم...</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}" {{ old('department_id') == $dept->department_id ? 'selected' : '' }}>
                            🎓 {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-lg pointer-events-none">expand_more</span>
            </div>
            @error('department_id')
                <span class="text-xs text-red-500 font-medium">{{ $message }}</span>
            @enderror
        </div>

        {{-- Search --}}
        <div class="relative">
            <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input id="hod-search" type="text"
                   class="w-full bg-surface-light dark:bg-surface-dark rounded-2xl border border-slate-100 dark:border-slate-700 focus:border-primary focus:ring-2 focus:ring-primary/30 py-3 pr-12 pl-4 text-xs font-medium text-slate-800 dark:text-white placeholder:text-slate-400 transition-all outline-none"
                   placeholder="بحث عن رئيس قسم..." onkeyup="filterHODs()" />
        </div>

        {{-- Available Users Header --}}
        <div class="flex items-center justify-between px-1">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">المتاحين في النظام</h3>
            <span class="text-xs font-bold text-primary">{{ count($availableUsers) }} متاح</span>
        </div>

        {{-- Users List --}}
        <div class="flex flex-col gap-2 max-h-[400px] overflow-y-auto hide-scrollbar">
            @foreach($availableUsers as $user)
                @php
                    $initials = mb_substr($user->full_name, 0, 2);
                    $avatarColors = [
                        'bg-blue-100 text-blue-600', 'bg-purple-100 text-purple-600',
                        'bg-emerald-100 text-emerald-600', 'bg-rose-100 text-rose-600',
                        'bg-orange-100 text-orange-600', 'bg-cyan-100 text-cyan-600',
                    ];
                    $avatarColor = $avatarColors[$loop->index % count($avatarColors)];
                    $currentDept = $user->department ?? '';
                    $isHOD = $user->role_id == 5;
                @endphp
                <label class="relative cursor-pointer group hod-user-item" data-name="{{ strtolower($user->full_name) }}">
                    <input type="radio" name="user_id" value="{{ $user->user_id }}" class="peer sr-only" {{ old('user_id') == $user->user_id ? 'checked' : '' }} />
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700/50 transition-all duration-200 group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl {{ $avatarColor }} flex items-center justify-center font-extrabold text-sm flex-shrink-0">
                                {{ $initials }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $user->full_name }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    {{ $isHOD ? 'رئيس قسم حالي' : ($user->department ?? 'بدون تخصيص') }}
                                </span>
                            </div>
                        </div>
                        {{-- Radio indicator --}}
                        <div class="w-6 h-6 rounded-full border-2 border-slate-200 dark:border-slate-600 flex items-center justify-center peer-checked:border-primary peer-checked:bg-primary transition-all">
                            <div class="w-2.5 h-2.5 rounded-full bg-transparent peer-checked:bg-primary-content transition-all"></div>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        @error('user_id')
            <span class="text-xs text-red-500 font-medium">{{ $message }}</span>
        @enderror

        {{-- Submit --}}
        <div class="pt-2 pb-8">
            <button type="submit" class="group relative w-full overflow-hidden rounded-[1.5rem] bg-primary p-4 transition-all hover:bg-primary-dark active:scale-[0.98] shadow-glow">
                <div class="relative z-10 flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined text-primary-content text-xl">check_circle</span>
                    <span class="font-bold text-primary-content text-lg">تأكيد التعيين</span>
                </div>
            </button>
        </div>
    </form>

@endsection

@push('scripts')
<script>
    function filterHODs() {
        const query = document.getElementById('hod-search').value.toLowerCase();
        document.querySelectorAll('.hod-user-item').forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(query) ? 'block' : 'none';
        });
    }

    // Style radio buttons correctly
    document.querySelectorAll('input[name="user_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Reset all indicators
            document.querySelectorAll('.hod-user-item .w-6 .w-2\\.5').forEach(dot => {
                dot.classList.remove('!bg-primary-content');
            });
            // Highlight selected
            if (this.checked) {
                const dot = this.closest('label').querySelector('.w-6 .w-2\\.5');
                if (dot) dot.classList.add('!bg-primary-content');
            }
        });
    });
</script>
@endpush
