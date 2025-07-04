<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Show attendance marking form
     */
    public function showForm()
    {
        $subjects = Subject::where('is_active', true)->orderBy('subject_code')->get();
        $teachers = $this->getTeachersList();
        $departments = ['Business', 'IT', 'Science']; // Define available departments
        return view('attendance.form', compact('subjects', 'teachers', 'departments'));
    }

    /**
     * Get list of teachers
     */
    private function getTeachersList()
    {
        return [
            'Mr. Smith',
            'Dr. Johnson', 
            'Prof. Williams',
            'Dr. Brown',
            'Prof. Davis',
            'Mrs. Miller',
            'Prof. Wilson',
            'Dr. Moore',
            'Prof. Taylor',
            'Mr. Anderson'
        ];
    }

    /**
     * Get subjects by department
     */
    public function getSubjectsByDepartment(Request $request)
    {
        try {
            $department = $request->get('department');
            if (!$department) {
                return response()->json(['error' => 'Department is required'], 400);
            }

            $subjects = Subject::where('is_active', true)
                             ->where('department', $department)
                             ->orderBy('subject_code')
                             ->get(['id', 'subject_code', 'subject_name']);

            return response()->json(['success' => true, 'subjects' => $subjects]);
        } catch (\Exception $e) {
            Log::error('Error loading subjects: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load subjects'], 500);
        }
    }

    /**
     * Get students enrolled in a specific subject
     */
    public function getStudentsBySubject(Request $request)
    {
        try {
            $subjectId = $request->get('subject_id');
            $date = $request->get('date', now()->format('Y-m-d'));

            if (!$subjectId) {
                return response()->json(['error' => 'Subject ID is required'], 400);
            }

            $students = DB::select("
                SELECT s.id, s.registration_number, s.first_name, s.last_name, s.email
                FROM students s
                INNER JOIN student_subject ss ON s.id = ss.student_id
                WHERE ss.subject_id = ? AND s.is_active = 1 AND ss.is_active = 1
                ORDER BY s.registration_number
            ", [$subjectId]);

            $existingAttendance = DB::select("
                SELECT student_id, present, remarks
                FROM attendance
                WHERE subject_id = ? AND attendance_date = ?
            ", [$subjectId, $date]);

            $attendanceMap = [];
            foreach ($existingAttendance as $att) {
                $attendanceMap[$att->student_id] = $att;
            }

            foreach ($students as $student) {
                $attendance = $attendanceMap[$student->id] ?? null;
                $student->current_attendance = $attendance ? (bool)$attendance->present : null;
                $student->current_remarks = $attendance ? $attendance->remarks : '';
            }

            return response()->json([
                'success' => true,
                'students' => $students,
                'date' => $date,
                'subject_id' => $subjectId
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading students: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load students'], 500);
        }
    }

    /**
     * Store attendance records
     */
    public function store(Request $request)
    {
        try {
            $input = $request->all();
            if ($request->header('Content-Type') === 'application/json') {
                $input = json_decode($request->getContent(), true);
            }
        
            Log::info('Attendance store request:', $input);

            $validator = Validator::make($input, [
                'subject_id' => 'required|integer|exists:subjects,id',
                'attendance_date' => 'required|date',
                'marked_by' => 'required|string|max:100',
                'attendance' => 'required|array|min:1'
            ]);

            if ($validator->fails()) {
                Log::error('Basic validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'error' => 'Basic validation failed',
                    'details' => $validator->errors()->toArray()
                ], 422);
            }

            $attendanceData = $input['attendance'];
            $studentIds = array_column($attendanceData, 'student_id');

            // Check if all students are enrolled in at least 3 subjects
            $enrollmentCheck = DB::table('student_subject')
                ->whereIn('student_id', $studentIds)
                ->where('is_active', 1)
                ->select('student_id', DB::raw('COUNT(*) as subject_count'))
                ->groupBy('student_id')
                ->having('subject_count', '<', 3)
                ->pluck('student_id')
                ->all();

            if (!empty($enrollmentCheck)) {
                $invalidStudents = implode(', ', $enrollmentCheck);
                return response()->json([
                    'error' => "Students with IDs ($invalidStudents) are not enrolled in at least 3 subjects.",
                    'details' => ['min_subjects_required' => 3]
                ], 422);
            }

            foreach ($attendanceData as $index => $record) {
                if (!isset($record['student_id']) || !is_numeric($record['student_id'])) {
                    return response()->json([
                        'error' => "Invalid student_id at index {$index}",
                        'received' => $record
                    ], 422);
                }

                if (!isset($record['present'])) {
                    return response()->json([
                        'error' => "Missing present value at index {$index}",
                        'received' => $record
                    ], 422);
                }

                if (is_string($record['present'])) {
                    $attendanceData[$index]['present'] = $record['present'] === '1' || $record['present'] === 'true';
                } elseif (is_numeric($record['present'])) {
                    $attendanceData[$index]['present'] = (bool)$record['present'];
                } elseif (!is_bool($record['present'])) {
                    return response()->json([
                        'error' => "Invalid present value at index {$index}. Expected boolean, string, or number.",
                        'received' => $record['present'],
                        'type' => gettype($record['present'])
                    ], 422);
                }
            }

            DB::beginTransaction();

            $successCount = 0;
            foreach ($attendanceData as $record) {
                $affected = DB::table('attendance')->updateOrInsert(
                    [
                        'student_id' => (int)$record['student_id'],
                        'subject_id' => (int)$input['subject_id'],
                        'attendance_date' => $input['attendance_date']
                    ],
                    [
                        'present' => $record['present'] ? 1 : 0,
                        'remarks' => $record['remarks'] ?? null,
                        'marked_by' => $input['marked_by'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                $successCount++;
                Log::info("Attendance record {$successCount} processed for student {$record['student_id']} - Present: " . ($record['present'] ? 'Yes' : 'No'));
            }

            DB::commit();

            Log::info("Successfully saved {$successCount} attendance records");

            return response()->json([
                'success' => true,
                'message' => "Attendance saved successfully for {$successCount} students"
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error saving attendance:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to save attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show dashboard
     */
    public function showDashboard()
    {
        $subjects = Subject::where('is_active', true)->orderBy('subject_code')->get();
        return view('attendance.dashboard', compact('subjects'));
    }

    /**
     * Get attendance data for dashboard
     */
    public function getAttendanceData(Request $request)
    {
        if ($request->ajax()) {
            $draw = (int)$request->get('draw', 1);
            $start = (int)$request->get('start', 0);
            $length = (int)$request->get('length', 25);
            $searchValue = $request->get('search')['value'] ?? '';

            $startDate = $request->get('start_date', Carbon::now()->subWeek()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $subjectId = $request->get('subject_id');

            $cacheKey = "attendance_data_{$subjectId}_{$startDate}_{$endDate}_{$start}_{$length}_{$searchValue}";
            return Cache::remember($cacheKey, 3600, function () use ($draw, $start, $length, $searchValue, $startDate, $endDate, $subjectId) {
                Log::info('Dashboard request parameters:', [
                    'draw' => $draw,
                    'start' => $start,
                    'length' => $length,
                    'search' => $searchValue,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'subject_id' => $subjectId
                ]);

                $baseQuery = "
                    SELECT 
                        s.registration_number,
                        CONCAT(s.first_name, ' ', s.last_name) as student_name,
                        sub.subject_code || ' - ' || sub.subject_name as subject,
                        COUNT(a.id) as total_classes,
                        SUM(CASE WHEN a.present = 1 THEN 1 ELSE 0 END) as present_count,
                        ROUND((SUM(CASE WHEN a.present = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100, 2) as attendance_percentage
                    FROM students s
                    INNER JOIN student_subject ss ON s.id = ss.student_id
                    INNER JOIN subjects sub ON ss.subject_id = sub.id
                    LEFT JOIN attendance a ON s.id = a.student_id 
                        AND a.subject_id = sub.id 
                        AND a.attendance_date BETWEEN ? AND ?
                    WHERE s.is_active = 1 AND ss.is_active = 1 AND sub.is_active = 1
                ";

                $params = [$startDate, $endDate];

                if ($subjectId && $subjectId !== '') {
                    $baseQuery .= " AND sub.id = ?";
                    $params[] = $subjectId;
                }

                if ($searchValue && $searchValue !== '') {
                    $baseQuery .= " AND (s.registration_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ?)";
                    $searchParam = "%{$searchValue}%";
                    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
                }

                $baseQuery .= " GROUP BY s.id, s.registration_number, s.first_name, s.last_name, sub.id, sub.subject_code, sub.subject_name";
                $baseQuery .= " HAVING total_classes > 0";

                $totalQuery = "SELECT COUNT(*) as total FROM ({$baseQuery}) as subquery";
                $totalResult = DB::select($totalQuery, $params);
                $totalRecords = $totalResult[0]->total ?? 0;

                $dataQuery = $baseQuery . " ORDER BY s.registration_number LIMIT ? OFFSET ?";
                $dataParams = array_merge($params, [$length, $start]);
                $data = DB::select($dataQuery, $dataParams);

                $formattedData = [];
                foreach ($data as $row) {
                    $percentage = $row->attendance_percentage ?? 0;
                    $status = $this->getAttendanceStatus($percentage);
                    $formattedData[] = [
                        'registration_number' => $row->registration_number,
                        'student_name' => $row->student_name,
                        'subject' => $row->subject,
                        'total_classes' => (int)$row->total_classes,
                        'present_count' => (int)$row->present_count,
                        'attendance_percentage' => $percentage,
                        'status' => $status
                    ];
                }

                Log::info('Query executed successfully', ['total_records' => $totalRecords, 'returned_records' => count($data)]);

                return [
                    'draw' => $draw,
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecords,
                    'data' => $formattedData
                ];
            });
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get student email by registration number
     */
    public function getEmail(Request $request)
    {
        $registrationNumber = $request->get('registration_number');
        if (!$registrationNumber) {
            return response()->json(['error' => 'Registration number is required'], 400);
        }

        $student = Student::where('registration_number', $registrationNumber)
                         ->where('is_active', 1)
                         ->first();

        return response()->json([
            'success' => true,
            'email' => $student ? $student->email : null
        ]);
    }

    /**
     * Export data to CSV
     */
    public function export(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subWeek()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $subjectId = $request->get('subject_id');

            $query = "
                SELECT 
                    s.registration_number,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.email,
                    sub.subject_code || ' - ' || sub.subject_name as subject,
                    COUNT(a.id) as total_classes,
                    SUM(CASE WHEN a.present = 1 THEN 1 ELSE 0 END) as present_count
                FROM students s
                INNER JOIN student_subject ss ON s.id = ss.student_id
                INNER JOIN subjects sub ON ss.subject_id = sub.id
                LEFT JOIN attendance a ON s.id = a.student_id 
                    AND a.subject_id = sub.id 
                    AND a.attendance_date BETWEEN ? AND ?
                WHERE s.is_active = 1 AND ss.is_active = 1 AND sub.is_active = 1
            ";

            $params = [$startDate, $endDate];

            if ($subjectId && $subjectId !== '') {
                $query .= " AND sub.id = ?";
                $params[] = $subjectId;
            }

            $query .= " GROUP BY s.id, s.registration_number, s.first_name, s.last_name, s.email, sub.id, sub.subject_code, sub.subject_name";
            $query .= " HAVING total_classes > 0";
            $query .= " ORDER BY s.registration_number";

            $data = DB::select($query, $params);

            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, [
                    'Registration Number',
                    'Student Name',
                    'Email',
                    'Subject',
                    'Total Classes',
                    'Present Count',
                    'Attendance Percentage'
                ]);

                foreach ($data as $row) {
                    $totalClasses = (int)$row->total_classes;
                    $presentCount = (int)$row->present_count;
                    $percentage = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 2) : 0;

                    fputcsv($file, [
                        $row->registration_number,
                        $row->student_name,
                        $row->email ?? '',
                        $row->subject,
                        $totalClasses,
                        $presentCount,
                        number_format($percentage, 2) . '%'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to export data'], 500);
        }
    }

    /**
     * Get attendance status badge
     */
    private function getAttendanceStatus($percentage): string
    {
        if ($percentage >= 90) return '<span class="badge bg-success">Excellent</span>';
        elseif ($percentage >= 75) return '<span class="badge bg-primary">Good</span>';
        elseif ($percentage >= 60) return '<span class="badge bg-warning">Average</span>';
        else return '<span class="badge bg-danger">Poor</span>';
    }
}