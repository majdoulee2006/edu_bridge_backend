<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FilteredAttendanceExport implements WithMultipleSheets
{
    use Exportable;

    protected $sessions;
    protected $allStudents;
    protected $matrix;
    protected $isAdvisor;

    public function __construct($sessions, $allStudents, $matrix, $isAdvisor)
    {
        $this->sessions = $sessions;
        $this->allStudents = $allStudents;
        $this->matrix = $matrix;
        $this->isAdvisor = $isAdvisor;
    }

    public function sheets(): array
    {
        $sheets = [];

        // 1. Daily Summary Sheet
        $sheets[] = new DailyAttendanceSheet($this->sessions, $this->allStudents, $this->matrix);

        // 2. Sheets per Course
        $courseSessions = [];
        foreach ($this->sessions as $session) {
            $courseSessions[$session->course_id][] = $session;
        }

        foreach ($courseSessions as $courseId => $sessionsList) {
            $courseTitle = $sessionsList[0]->course_title;
            // Sheet titles must be unique and max 31 chars
            $sheetTitle = mb_substr($courseTitle, 0, 25) . " ($courseId)";
            $sheets[] = new CourseAttendanceSheet($sheetTitle, $sessionsList, $this->allStudents, $this->matrix);
        }

        return $sheets;
    }
}
