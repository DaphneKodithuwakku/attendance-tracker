<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Subject;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to attendance form
Route::get('/', function () {
    return redirect()->route('attendance.form');
});

// Attendance Management Routes
Route::prefix('attendance')->group(function () {
    // Form routes
    Route::get('/form', [AttendanceController::class, 'showForm'])->name('attendance.form');
    Route::get('/students', [AttendanceController::class, 'getStudentsBySubject']);
    Route::post('/store', [AttendanceController::class, 'store']);
    Route::get('/subjects-by-department', [AttendanceController::class, 'getSubjectsByDepartment'])->name('attendance.getSubjectsByDepartment');
    
    // Dashboard routes
    Route::get('/dashboard', [AttendanceController::class, 'showDashboard'])->name('attendance.dashboard');
    Route::get('/data', [AttendanceController::class, 'getAttendanceData'])->name('attendance.data');
    Route::get('/export', [AttendanceController::class, 'export'])->name('attendance.export');
    Route::get('/get-email', [AttendanceController::class, 'getEmail'])->name('attendance.get-email');
});

// Test route to verify data
Route::get('/test-data', function() {
    return response()->json([
        'students_count' => \App\Models\Student::count(),
        'subjects_count' => \App\Models\Subject::count(),
        'attendance_count' => \App\Models\Attendance::count(),
        'enrollments_count' => \Illuminate\Support\Facades\DB::table('student_subject')->count()
    ]);
});

// Simple test route for dashboard data
Route::get('/test-dashboard', function() {
    try {
        $startDate = \Carbon\Carbon::now()->subWeek()->format('Y-m-d');
        $endDate = \Carbon\Carbon::now()->format('Y-m-d');
        
        $simpleData = DB::table('students as s')
            ->join('student_subject as ss', 's.id', '=', 'ss.student_id')
            ->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')
            ->where('s.is_active', 1)
            ->where('ss.is_active', 1)
            ->select([
                's.registration_number',
                's.first_name',
                's.last_name',
                'sub.subject_code',
                'sub.subject_name',
                'sub.department' // Include department for testing
            ])
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'date_range' => [$startDate, $endDate],
            'simple_data' => $simpleData,
            'counts' => [
                'students' => Student::count(),
                'subjects' => Subject::count(),
                'enrollments' => DB::table('student_subject')->where('is_active', 1)->count(),
                'attendance_records' => DB::table('attendance')->count()
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
    }
});

// Verification route to check student enrollments
Route::get('/verify-enrollments', function() {
    try {
        $enrollmentData = DB::table('students as s')
            ->join('student_subject as ss', 's.id', '=', 'ss.student_id')
            ->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')
            ->where('s.is_active', 1)
            ->where('ss.is_active', 1)
            ->select([
                's.registration_number',
                's.first_name',
                's.last_name',
                DB::raw('COUNT(ss.subject_id) as enrolled_subjects'),
                DB::raw('GROUP_CONCAT(sub.subject_code ORDER BY sub.subject_code) as subjects'),
                DB::raw('GROUP_CONCAT(sub.department ORDER BY sub.subject_code) as departments') // Include departments
            ])
            ->groupBy('s.id', 's.registration_number', 's.first_name', 's.last_name')
            ->orderBy('s.registration_number')
            ->get();

        $subjectStats = DB::table('subjects as sub')
            ->leftJoin('student_subject as ss', function($join) {
                $join->on('sub.id', '=', 'ss.subject_id')
                     ->where('ss.is_active', 1);
            })
            ->leftJoin('students as s', function($join) {
                $join->on('ss.student_id', '=', 's.id')
                     ->where('s.is_active', 1);
            })
            ->select([
                'sub.subject_code',
                'sub.subject_name',
                'sub.department', // Include department in stats
                DB::raw('COUNT(s.id) as enrolled_students')
            ])
            ->groupBy('sub.id', 'sub.subject_code', 'sub.subject_name', 'sub.department')
            ->orderBy('sub.subject_code')
            ->get();

        $underEnrolled = $enrollmentData->filter(function($student) {
            return $student->enrolled_subjects < 3;
        });

        return response()->json([
            'success' => true,
            'summary' => [
                'total_students' => $enrollmentData->count(),
                'students_with_min_3_subjects' => $enrollmentData->filter(fn($s) => $s->enrolled_subjects >= 3)->count(),
                'under_enrolled_students' => $underEnrolled->count(),
                'average_subjects_per_student' => round($enrollmentData->avg('enrolled_subjects'), 2)
            ],
            'student_enrollments' => $enrollmentData,
            'subject_statistics' => $subjectStats,
            'under_enrolled_students' => $underEnrolled->values(),
            'enrollment_distribution' => [
                '3_subjects' => $enrollmentData->filter(fn($s) => $s->enrolled_subjects == 3)->count(),
                '4_subjects' => $enrollmentData->filter(fn($s) => $s->enrolled_subjects == 4)->count(),
                '5_subjects' => $enrollmentData->filter(fn($s) => $s->enrolled_subjects == 5)->count(),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
    }
});

// Debug route to test data structure
Route::get('/debug-data', function() {
    try {
        $startDate = \Carbon\Carbon::now()->subWeek()->format('Y-m-d');
        $endDate = \Carbon\Carbon::now()->format('Y-m-d');
        
        $data = DB::table('students as s')
            ->join('student_subject as ss', 's.id', '=', 'ss.student_id')
            ->join('subjects as sub', 'ss.subject_id', '=', 'sub.id')
            ->leftJoin('attendance as a', function($join) use ($startDate, $endDate) {
                $join->on('s.id', '=', 'a.student_id')
                     ->on('sub.id', '=', 'a.subject_id')
                     ->whereBetween('a.attendance_date', [$startDate, $endDate]);
            })
            ->where('s.is_active', 1)
            ->where('ss.is_active', 1)
            ->where('sub.is_active', 1)
            ->select([
                's.id as student_id',
                's.registration_number',
                's.first_name',
                's.last_name',
                'sub.id as subject_id',
                'sub.subject_code',
                'sub.subject_name',
                'sub.department', // Include department
                DB::raw('COUNT(a.id) as total_classes'),
                DB::raw('SUM(CASE WHEN a.present = 1 THEN 1 ELSE 0 END) as present_count')
            ])
            ->groupBy([
                's.id',
                's.registration_number', 
                's.first_name',
                's.last_name',
                'sub.id',
                'sub.subject_code',
                'sub.subject_name',
                'sub.department'
            ])
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'date_range' => [$startDate, $endDate],
            'raw_data' => $data,
            'data_types' => $data->map(function($row) {
                return [
                    'student_id' => gettype($row->student_id),
                    'registration_number' => gettype($row->registration_number),
                    'first_name' => gettype($row->first_name),
                    'department' => gettype($row->department), // Check department type
                    'total_classes' => gettype($row->total_classes),
                    'present_count' => gettype($row->present_count)
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
    }
});