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

class DailyAttendanceSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected $sessions;
    protected $allStudents;
    protected $matrix;

    public function __construct($sessions, $allStudents, $matrix)
    {
        $this->sessions = $sessions;
        $this->allStudents = $allStudents;
        $this->matrix = $matrix;
    }

    public function array(): array
    {
        $days = [];
        foreach ($this->sessions as $session) {
            $date = \Carbon\Carbon::parse($session->created_at)->format('Y-m-d');
            $days[$date] = true;
        }
        $days = array_keys($days);
        sort($days);

        $rows = [];
        
        $sortedStudents = $this->allStudents;
        uasort($sortedStudents, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        foreach ($sortedStudents as $studentId => $info) {
            $row = [
                $info['name'],
                $info['branch'] ?? 'عام',
                $info['year'] ?? '-',
            ];

            $studentPresenceByDay = [];
            foreach ($this->sessions as $session) {
                $date = \Carbon\Carbon::parse($session->created_at)->format('Y-m-d');
                $status = $this->matrix[$studentId][$session->lesson_id] ?? null;
                
                if ($status !== null) {
                    if (!isset($studentPresenceByDay[$date])) {
                        $studentPresenceByDay[$date] = 'absent';
                    }
                    if ($status === 'present') {
                        $studentPresenceByDay[$date] = 'present';
                    }
                }
            }

            foreach ($days as $day) {
                if (isset($studentPresenceByDay[$day])) {
                    if ($studentPresenceByDay[$day] === 'present') {
                        $row[] = 'حاضر';
                    } else {
                        $row[] = 'غائب';
                    }
                } else {
                    $row[] = '-';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $daysMap = [];
        foreach ($this->sessions as $session) {
            $dateObj = \Carbon\Carbon::parse($session->created_at)->locale('ar');
            $dateStr = $dateObj->format('Y-m-d');
            $dayName = $dateObj->translatedFormat('l');
            $daysMap[$dateStr] = $dayName . ' ' . $dateStr;
        }

        $dates = array_keys($daysMap);
        sort($dates);

        $headers = ['اسم الطالب', 'الفرع', 'السنة'];
        foreach ($dates as $date) {
            $headers[] = $daysMap[$date];
        }

        return $headers;
    }

    public function title(): string
    {
        return 'الملخص اليومي';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:Z1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FF1F3A93']], // Dark Blue
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
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
                
                // Center everything
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                
                // Add borders
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FFDDDDDD'],
                        ],
                    ],
                ];
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray($styleArray);

                // Conditional formatting
                for ($row = 2; $row <= $highestRow; $row++) {
                    for ($col = 'D'; $col <= $highestCol; $col++) { // D is where dates start
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
