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
        $date = Carbon::parse($exam->exam_date);
        return [
            $exam->exam_name ?? ($exam->course->title ?? 'مادة غير معروفة'),
            $date->format('Y-m-d'),
            $date->format('h:i A'),
            'ساعتان',
            'نهائي'
        ];
    }
}