<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run()
    {
        $subjects = [
            // Business Department
            ['subject_code' => 'BUS101', 'subject_name' => 'Principles of Management', 'department' => 'Business'],
            ['subject_code' => 'BUS201', 'subject_name' => 'Financial Accounting', 'department' => 'Business'],
            ['subject_code' => 'BUS301', 'subject_name' => 'Marketing Fundamentals', 'department' => 'Business'],
            ['subject_code' => 'BUS401', 'subject_name' => 'Business Law', 'department' => 'Business'],
            ['subject_code' => 'BUS501', 'subject_name' => 'Economics for Business', 'department' => 'Business'],
            
            // IT Department
            ['subject_code' => 'CS101', 'subject_name' => 'Introduction to Computer Science', 'department' => 'IT'],
            ['subject_code' => 'CS201', 'subject_name' => 'Data Structures', 'department' => 'IT'],
            ['subject_code' => 'CS301', 'subject_name' => 'Algorithms', 'department' => 'IT'],
            ['subject_code' => 'CS401', 'subject_name' => 'Web Development', 'department' => 'IT'],
            ['subject_code' => 'CS501', 'subject_name' => 'Database Systems', 'department' => 'IT'],
            
            // Science Department
            ['subject_code' => 'PHYS101', 'subject_name' => 'Physics I', 'department' => 'Science'],
            ['subject_code' => 'CHEM101', 'subject_name' => 'Chemistry I', 'department' => 'Science'],
            ['subject_code' => 'BIO101', 'subject_name' => 'Biology I', 'department' => 'Science'],
            ['subject_code' => 'MATH101', 'subject_name' => 'Calculus I', 'department' => 'Science'],
            ['subject_code' => 'ENV101', 'subject_name' => 'Environmental Science', 'department' => 'Science'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['subject_code' => $subject['subject_code']],
                $subject
            );
        }
    }
}