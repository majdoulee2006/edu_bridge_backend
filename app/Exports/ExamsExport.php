<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class ExamsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $exams;

    // نستقبل البيانات من الكنترولر
    public function __construct($exams)
    {
        $this->exams = $exams;
    }

    public function collection()
    {
        return $this->exams;
    }

    // 💡 ترتيب أسماء الأعمدة في ملف الإكسل (الصف الأول)
    public function headings(): array
    {
        return [
            'المادة',
            'التاريخ',
            'الوقت',
            'المدة',
            'نوع الامتحان'
        ];
    }

    // 💡 ترتيب البيانات جوا الأعمدة
    public function map($exam): array
    {
        if (is_array($exam)) {
            $exam = (object)$exam;
        }

        $dateStr = $exam->date ?? $exam->exam_date ?? now();
        $date = Carbon::parse($dateStr);

        $title = $exam->title ?? $exam->exam_name ?? null;
        $courseTitle = $exam->course_title ?? ($exam->course->title ?? null);
        
        $subject = $courseTitle;
        if ($title && $courseTitle) {
            $subject = $courseTitle . ' (' . $title . ')';
        } elseif ($title) {
            $subject = $title;
        } else {
            $subject = 'مادة غير معروفة';
        }

        $type = 'نهائي';
        if (isset($exam->event_type)) {
            $type = $exam->event_type === 'exam' ? 'امتحان' : 'مذاكرة';
        }

        $duration = 'ساعتان';
        if (isset($exam->event_type) && $exam->event_type === 'quiz') {
            $duration = 'ساعة';
        }

        return [
            $subject,
            $date->format('Y-m-d'),
            $date->format('h:i A'),
            $duration,
            $type
        ];
    }
}