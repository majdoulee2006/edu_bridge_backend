<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseAttendanceSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected $title;
    protected $sessions;
    protected $allStudents;
    protected $matrix;

    public function __construct($title, $sessions, $allStudents, $matrix)
    {
        $cleanTitle = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $title);
        $this->title = mb_substr($cleanTitle, 0, 31);
        
        $this->sessions = $sessions;
        $this->allStudents = $allStudents;
        $this->matrix = $matrix;
    }

    public function array(): array
    {
        $enrolledStudents = [];
        foreach ($this->allStudents as $studentId => $info) {
            $isEnrolled = false;
            foreach ($this->sessions as $session) {
                if (isset($this->matrix[$studentId][$session->lesson_id])) {
                    $isEnrolled = true;
                    break;
                }
            }
            if ($isEnrolled) {
                $enrolledStudents[$studentId] = $info;
            }
        }

        uasort($enrolledStudents, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $rows = [];
        foreach ($enrolledStudents as $studentId => $info) {
            $row = [
                $info['name'],
                $info['branch'] ?? 'عام',
                $info['year'] ?? '-',
            ];

            $presentCount = 0;
            $absentCount = 0;
            $sessionCells = [];

            foreach ($this->sessions as $session) {
                $status = $this->matrix[$studentId][$session->lesson_id] ?? null;
                
                if ($status === 'present') {
                    $presentCount++;
                    $sessionCells[] = 'حاضر';
                } elseif ($status === 'absent') {
                    $absentCount++;
                    $sessionCells[] = 'غائب';
                } else {
                    $sessionCells[] = '-';
                }
            }

            $row[] = $presentCount;
            $row[] = $absentCount;

            $rows[] = array_merge($row, $sessionCells);
        }

        return $rows;
    }

    public function headings(): array
    {
        $headers = ['اسم الطالب', 'الفرع', 'السنة', 'إجمالي الحضور', 'إجمالي الغياب'];
        
        foreach ($this->sessions as $session) {
            $dateObj = \Carbon\Carbon::parse($session->created_at)->locale('ar');
            $dateStr = $dateObj->format('Y-m-d');
            $dayName = $dateObj->translatedFormat('l');
            $headers[] = $dayName . "\n" . $dateStr;
        }

        return $headers;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:Z1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FF1F3A93']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
        ]);
        
        $sheet->setRightToLeft(true);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FFDDDDDD'],
                        ],
                    ],
                ];
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray($styleArray);

                // Highlight summary columns
                $sheet->getStyle("D2:D{$highestRow}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8F5E9']], // light green for present count
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF1B5E20']]
                ]);
                $sheet->getStyle("E2:E{$highestRow}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFFFEBEE']], // light red for absent count
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFB71C1C']]
                ]);

                // Conditional formatting for session columns
                for ($row = 2; $row <= $highestRow; $row++) {
                    for ($col = 'F'; $col <= $highestCol; $col++) { // F is where dates start
                        $cellValue = $sheet->getCell($col . $row)->getValue();
                        if ($cellValue === 'حاضر') {
                            $sheet->getStyle($col . $row)->applyFromArray([
                                'font' => ['color' => ['argb' => 'FF006400'], 'bold' => true],
                                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFD4EDDA']],
                            ]);
                        } elseif ($cellValue === 'غائب') {
                            $sheet->getStyle($col . $row)->applyFromArray([
                                'font' => ['color' => ['argb' => 'FF8B0000'], 'bold' => true],
                                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFF8D7DA']],
                            ]);
                        } elseif ($cellValue === '-') {
                            $sheet->getStyle($col . $row)->applyFromArray([
                                'font' => ['color' => ['argb' => 'FF999999']],
                                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFF0F0F0']],
                            ]);
                        }
                    }
                }
            },
        ];
    }
}
