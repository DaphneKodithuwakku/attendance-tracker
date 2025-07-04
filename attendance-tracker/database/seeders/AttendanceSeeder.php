<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        echo "Generating attendance data...\n";
        
        $students = Student::with('subjects')->get();
        $startDate = Carbon::now()->subDays(21); // 3 weeks ago (today is 06:37 PM +0530, July 04, 2025)
        $endDate = Carbon::now();

        $totalRecords = 0;

        foreach ($students as $student) {
            foreach ($student->subjects as $subject) {
                // Create attendance for each weekday in the range
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    // Skip weekends (Saturday and Sunday)
                    if ($date->isWeekend()) {
                        continue;
                    }

                    // Use subject_code for attendance rate and teacher
                    $attendanceRate = $this->getAttendanceRate($subject->subject_code);
                    $present = rand(1, 100) <= $attendanceRate;

                    // Get appropriate teacher for the subject
                    $teacher = $this->getTeacherForSubject($subject->subject_code);

                    Attendance::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'subject_id' => $subject->id,
                            'attendance_date' => $date->format('Y-m-d'),
                        ],
                        [
                            'present' => $present,
                            'marked_by' => $teacher,
                            'remarks' => $present ? null : $this->getRandomAbsenceReason(),
                        ]
                    );

                    $totalRecords++;
                }
            }
        }

        echo "Generated {$totalRecords} attendance records\n";
        $this->showAttendanceStats();
    }

    private function getAttendanceRate($subjectCode)
    {
        // Adjusted rates for realism based on subject difficulty and interest
        $rates = [
            'CS101' => 88,    // High interest in Computer Science
            'MATH101' => 82,  // Moderate attendance for Calculus
            'ENG101' => 85,   // Good attendance for English
            'PHYS101' => 79,  // Lower attendance due to challenge
            'CHEM101' => 83,  // Moderate attendance for Chemistry
        ];

        return $rates[$subjectCode] ?? 85; // Default rate if code missing
    }

    private function getTeacherForSubject($subjectCode)
    {
        $teachers = [
            'CS101' => 'Prof. Smith',
            'MATH101' => 'Dr. Johnson',
            'ENG101' => 'Prof. Williams',
            'PHYS101' => 'Dr. Brown',
            'CHEM101' => 'Prof. Davis',
        ];

        return $teachers[$subjectCode] ?? 'Prof. Unknown';
    }

    private function getRandomAbsenceReason()
    {
        $reasons = [
            'Sick',
            'Family emergency',
            'Medical appointment',
            'Late arrival',
            'Transportation issue',
            null, // No reason given
        ];

        return $reasons[array_rand($reasons)];
    }

    private function showAttendanceStats()
    {
        echo "\n=== ATTENDANCE STATISTICS ===\n";
        
        $stats = Attendance::selectRaw('
            subjects.subject_code,
            subjects.subject_name,
            COUNT(*) as total_records,
            SUM(CASE WHEN present = 1 THEN 1 ELSE 0 END) as present_count,
            ROUND(AVG(CASE WHEN present = 1 THEN 100 ELSE 0 END), 2) as attendance_rate
        ')
        ->join('subjects', 'attendance.subject_id', '=', 'subjects.id')
        ->groupBy('subjects.id', 'subjects.subject_code', 'subjects.subject_name')
        ->orderBy('subjects.subject_code')
        ->get();

        foreach ($stats as $stat) {
            echo "{$stat->subject_code}: {$stat->attendance_rate}% attendance ({$stat->present_count}/{$stat->total_records} records)\n";
        }

        $overallStats = Attendance::selectRaw('
            COUNT(*) as total_records,
            SUM(CASE WHEN present = 1 THEN 1 ELSE 0 END) as present_count,
            ROUND(AVG(CASE WHEN present = 1 THEN 100 ELSE 0 END), 2) as overall_rate
        ')->first();

        echo "\nOVERALL: {$overallStats->overall_rate}% attendance ({$overallStats->present_count}/{$overallStats->total_records} total records)\n";
    }
}