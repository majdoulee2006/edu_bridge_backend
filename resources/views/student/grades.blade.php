@extends('layouts.student')
@section('title', 'درجاتي')
@section('subtitle', 'سجل وسُلم الدرجات حسب المواد')

@push('styles')
<style>
    .course-card-box {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }
    .course-title-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        padding-bottom: 0.85rem;
        border-bottom: 2px solid var(--border-color);
    }
    .category-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }
    .cat-card {
        background: var(--bg-primary);
        border-radius: 1.25rem;
        padding: 1.25rem;
        border: 2px solid var(--border-color);
        cursor: pointer;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }
    .cat-card:hover {
        border-color: var(--accent-color);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .cat-card.active {
        border-color: var(--accent-color);
        background: rgba(242, 242, 13, 0.05);
    }
    .cat-icon {
        width: 44px; height: 44px;
        border-radius: 0.85rem;
        background: var(--accent-color);
        color: #1a1a1a;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .accordion-panel {
        display: none;
        margin-top: 1rem;
        background: var(--bg-primary);
        border-radius: 1.25rem;
        padding: 1.25rem;
        border: 1px dashed var(--accent-color);
        animation: fadeIn 0.3s ease;
    }
    .accordion-panel.active {
        display: block;
    }
    .grade-item-row {
        background: var(--bg-secondary);
        border-radius: 0.875rem;
        padding: 0.85rem 1.1rem;
        margin-bottom: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .grade-item-row:last-child { margin-bottom: 0; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')

{{-- Overview Card --}}
<div style="background: linear-gradient(135deg, #1a2633, #243447); border-radius: 1.5rem; padding: 1.75rem 2rem; margin-bottom: 2rem; color: white; display: flex; align-items: center; gap: 1.5rem; box-shadow: var(--shadow);">
    <div style="width: 76px; height: 76px; border-radius: 50%; background: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; font-weight: 900; color: #1a1a1a; flex-shrink: 0;">
        {{ $avgGrade > 0 ? $avgGrade . '%' : '--' }}
    </div>
    <div>
        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--accent-color); margin: 0;">متوسط الدرجات الكلي</h3>
        <p style="font-size: 0.88rem; opacity: 0.8; margin-top: 0.35rem;">موزع على {{ count($courseGradesData) }} مواد دراسية مسجلة</p>
    </div>
</div>

{{-- Course Cards Grid --}}
@forelse($courseGradesData as $c)
<div class="course-card-box">
    <div class="course-title-header">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 12px; height: 12px; border-radius: 50%; background: var(--accent-color);"></div>
            <h3 style="font-weight: 800; font-size: 1.15rem; margin: 0; color: var(--text-primary);">{{ $c['course_title'] }}</h3>
        </div>
        <span style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 700; background: var(--bg-primary); padding: 0.35rem 0.85rem; border-radius: 2rem; border: 1px solid var(--border-color);">
            {{ $c['total_items'] }} تقييم مسجل
        </span>
    </div>

    {{-- Category Cards (3 Cards) --}}
    <div class="category-cards-grid">

        {{-- 1. الواجبات --}}
        <div>
            <div class="cat-card" onclick="toggleAccordion('assign-{{ $c['course_id'] }}', this)">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="cat-icon"><i class="fa-solid fa-file-pen"></i></div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;">الواجبات والمشاريع</div>
                        <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.15rem;">{{ $c['assignments']->count() }} تقييم</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down" style="color: var(--text-secondary); font-size: 0.85rem; transition: transform 0.2s;"></i>
            </div>

            {{-- Dropdown Panel for Assignments --}}
            <div id="assign-{{ $c['course_id'] }}" class="accordion-panel">
                @forelse($c['assignments'] as $item)
                    @php
                        $max = ($item->max_score ?? 0) > 0 ? $item->max_score : 100;
                        $pct = round(($item->score / $max) * 100, 1);
                        $isPass = $pct >= 50;
                        $badgeBg = $isPass ? 'hsl(120,70%,90%)' : 'hsl(0,70%,90%)';
                        $badgeColor = $isPass ? 'hsl(120,50%,30%)' : 'hsl(0,50%,30%)';
                    @endphp
                    <div class="grade-item-row">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem;">{{ $item->name }}</div>
                            <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.15rem;">
                                {{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '' }}
                                @if($item->feedback)
                                    &nbsp;·&nbsp; <i class="fa-solid fa-comment-dots" style="color: var(--accent-color);"></i> {{ $item->feedback }}
                                @endif
                            </div>
                        </div>
                        <div style="text-align: left;">
                            <span style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; padding: 0.2rem 0.65rem; border-radius: 2rem; font-weight: 800; font-size: 0.85rem;">
                                {{ $item->score }} / {{ $max }}
                            </span>
                            <div style="font-size: 0.75rem; font-weight: 800; color: {{ $badgeColor }}; margin-top: 0.2rem; text-align: center;">{{ $pct }}%</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.85rem; padding: 0.75rem;">لا توجد درجات واجبات لهذه المادة بعد</div>
                @endforelse
            </div>
        </div>

        {{-- 2. المذاكرات --}}
        <div>
            <div class="cat-card" onclick="toggleAccordion('quiz-{{ $c['course_id'] }}', this)">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="cat-icon" style="background: #3b82f6; color: #ffffff;"><i class="fa-solid fa-pen-ruler"></i></div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;">المذاكرات والتقييمات</div>
                        <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.15rem;">{{ $c['quizzes']->count() }} تقييم</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down" style="color: var(--text-secondary); font-size: 0.85rem; transition: transform 0.2s;"></i>
            </div>

            {{-- Dropdown Panel for Quizzes --}}
            <div id="quiz-{{ $c['course_id'] }}" class="accordion-panel">
                @forelse($c['quizzes'] as $item)
                    @php
                        $max = ($item->max_score ?? 0) > 0 ? $item->max_score : 100;
                        $pct = round(($item->score / $max) * 100, 1);
                        $isPass = $pct >= 50;
                        $badgeBg = $isPass ? 'hsl(120,70%,90%)' : 'hsl(0,70%,90%)';
                        $badgeColor = $isPass ? 'hsl(120,50%,30%)' : 'hsl(0,50%,30%)';
                    @endphp
                    <div class="grade-item-row">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem;">{{ $item->name }}</div>
                            <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.15rem;">
                                {{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '' }}
                            </div>
                        </div>
                        <div style="text-align: left;">
                            <span style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; padding: 0.2rem 0.65rem; border-radius: 2rem; font-weight: 800; font-size: 0.85rem;">
                                {{ $item->score }} / {{ $max }}
                            </span>
                            <div style="font-size: 0.75rem; font-weight: 800; color: {{ $badgeColor }}; margin-top: 0.2rem; text-align: center;">{{ $pct }}%</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.85rem; padding: 0.75rem;">لا توجد درجات مذاكرات لهذه المادة بعد</div>
                @endforelse
            </div>
        </div>

        {{-- 3. الامتحانات --}}
        <div>
            <div class="cat-card" onclick="toggleAccordion('exam-{{ $c['course_id'] }}', this)">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="cat-icon" style="background: #a855f7; color: #ffffff;"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;">الامتحانات الرسمية</div>
                        <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.15rem;">{{ $c['exams']->count() }} تقييم</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down" style="color: var(--text-secondary); font-size: 0.85rem; transition: transform 0.2s;"></i>
            </div>

            {{-- Dropdown Panel for Exams --}}
            <div id="exam-{{ $c['course_id'] }}" class="accordion-panel">
                @forelse($c['exams'] as $item)
                    @php
                        $max = ($item->max_score ?? 0) > 0 ? $item->max_score : 100;
                        $pct = round(($item->score / $max) * 100, 1);
                        $isPass = $pct >= 50;
                        $badgeBg = $isPass ? 'hsl(120,70%,90%)' : 'hsl(0,70%,90%)';
                        $badgeColor = $isPass ? 'hsl(120,50%,30%)' : 'hsl(0,50%,30%)';
                    @endphp
                    <div class="grade-item-row">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem;">{{ $item->name }}</div>
                            <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.15rem;">
                                {{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '' }}
                            </div>
                        </div>
                        <div style="text-align: left;">
                            <span style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; padding: 0.2rem 0.65rem; border-radius: 2rem; font-weight: 800; font-size: 0.85rem;">
                                {{ $item->score }} / {{ $max }}
                            </span>
                            <div style="font-size: 0.75rem; font-weight: 800; color: {{ $badgeColor }}; margin-top: 0.2rem; text-align: center;">{{ $pct }}%</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; color: var(--text-secondary); font-size: 0.85rem; padding: 0.75rem;">لا توجد درجات امتحانات لهذه المادة بعد</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@empty
<div style="text-align: center; padding: 4rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary);">
    <i class="fa-solid fa-graduation-cap" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
    <p style="font-size: 1.05rem; font-weight: 600;">لا توجد مواد أو درجات مسجلة لك حالياً</p>
</div>
@endforelse

@endsection

@push('scripts')
<script>
function toggleAccordion(id, cardElem) {
    const panel = document.getElementById(id);
    if (!panel) return;
    
    const isActive = panel.classList.contains('active');
    
    if (isActive) {
        panel.classList.remove('active');
        cardElem.classList.remove('active');
        const icon = cardElem.querySelector('.fa-chevron-down');
        if (icon) icon.style.transform = 'rotate(0deg)';
    } else {
        panel.classList.add('active');
        cardElem.classList.add('active');
        const icon = cardElem.querySelector('.fa-chevron-down');
        if (icon) icon.style.transform = 'rotate(180deg)';
    }
}
</script>
@endpush
