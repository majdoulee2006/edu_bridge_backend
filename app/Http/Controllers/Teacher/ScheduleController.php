<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        // التصحيح: استعملي 'day' بدلاً من 'day_of_week'
        $schedules = Schedule::with('course')
            ->where('teacher_id', Auth::id()) 
            ->get()
            ->groupBy('day'); 

        return view('teacher.schedule', compact('schedules'));
    }
}