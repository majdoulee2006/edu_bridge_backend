<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\Department;

class AttendanceController extends Controller
{
    /**
     * عرض صفحة الحضور والغياب
     */
    public function index()
    {
        $teacher = Auth::user();
        
        // جلب المواد الخاصة بالمعلم (أو كل المواد إذا ما عنده مواد مخصصة)
        $courses = Course::all();
        $departments = Department::all();

        return view('teacher.attendance', compact('teacher', 'courses', 'departments'));
    }
}
