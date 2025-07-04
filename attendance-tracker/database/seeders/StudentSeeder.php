<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    public function run()
    {
        // Sample students (6 per department)
        $students = [
            // Business Department
            ['registration_number' => 'STU001', 'first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice.smith@example.com', 'department' => 'Business'],
            ['registration_number' => 'STU002', 'first_name' => 'Bob', 'last_name' => 'Johnson', 'email' => 'bob.johnson@example.com', 'department' => 'Business'],
            ['registration_number' => 'STU003', 'first_name' => 'Charlie', 'last_name' => 'Williams', 'email' => 'charlie.williams@example.com', 'department' => 'Business'],
            ['registration_number' => 'STU004', 'first_name' => 'David', 'last_name' => 'Brown', 'email' => 'david.brown@example.com', 'department' => 'Business'],
            ['registration_number' => 'STU005', 'first_name' => 'Eve', 'last_name' => 'Davis', 'email' => 'eve.davis@example.com', 'department' => 'Business'],
            ['registration_number' => 'STU006', 'first_name' => 'Frank', 'last_name' => 'Miller', 'email' => 'frank.miller@example.com', 'department' => 'Business'],
            
            // IT Department
            ['registration_number' => 'STU007', 'first_name' => 'Grace', 'last_name' => 'Wilson', 'email' => 'grace.wilson@example.com', 'department' => 'IT'],
            ['registration_number' => 'STU008', 'first_name' => 'Hank', 'last_name' => 'Moore', 'email' => 'hank.moore@example.com', 'department' => 'IT'],
            ['registration_number' => 'STU009', 'first_name' => 'Ivy', 'last_name' => 'Taylor', 'email' => 'ivy.taylor@example.com', 'department' => 'IT'],
            ['registration_number' => 'STU010', 'first_name' => 'Jack', 'last_name' => 'Anderson', 'email' => 'jack.anderson@example.com', 'department' => 'IT'],
            ['registration_number' => 'STU011', 'first_name' => 'Kelly', 'last_name' => 'Thomas', 'email' => 'kelly.thomas@example.com', 'department' => 'IT'],
            ['registration_number' => 'STU012', 'first_name' => 'Liam', 'last_name' => 'Jackson', 'email' => 'liam.jackson@example.com', 'department' => 'IT'],
            
            // Science Department
            ['registration_number' => 'STU013', 'first_name' => 'Mia', 'last_name' => 'White', 'email' => 'mia.white@example.com', 'department' => 'Science'],
            ['registration_number' => 'STU014', 'first_name' => 'Noah', 'last_name' => 'Harris', 'email' => 'noah.harris@example.com', 'department' => 'Science'],
            ['registration_number' => 'STU015', 'first_name' => 'Olivia', 'last_name' => 'Martin', 'email' => 'olivia.martin@example.com', 'department' => 'Science'],
            ['registration_number' => 'STU016', 'first_name' => 'Peter', 'last_name' => 'Lee', 'email' => 'peter.lee@example.com', 'department' => 'Science'],
            ['registration_number' => 'STU017', 'first_name' => 'Quinn', 'last_name' => 'Clark', 'email' => 'quinn.clark@example.com', 'department' => 'Science'],
            ['registration_number' => 'STU018', 'first_name' => 'Rose', 'last_name' => 'Walker', 'email' => 'rose.walker@example.com', 'department' => 'Science'],
        ];

        foreach ($students as $student) {
            Student::firstOrCreate(
                ['registration_number' => $student['registration_number']],
                $student
            );
        }

        // Get all subjects grouped by department
        $subjects = Subject::where('is_active', true)->get()->groupBy('department');
        $businessSubjects = $subjects->get('Business')->pluck('id')->all();
        $itSubjects = $subjects->get('IT')->pluck('id')->all();
        $scienceSubjects = $subjects->get('Science')->pluck('id')->all();

        // Verify we have subjects for each department
        if (empty($businessSubjects) || empty($itSubjects) || empty($scienceSubjects)) {
            throw new \Exception('Not enough subjects found for one or more departments. Please run SubjectSeeder first.');
        }

        // Enroll students in at least 3 subjects (random selection)
        $enrollments = [
            // Business students
            ['student_id' => 1, 'subject_ids' => array_rand(array_flip($businessSubjects), min(3, count($businessSubjects)))], // Alice
            ['student_id' => 2, 'subject_ids' => array_rand(array_flip($businessSubjects), min(3, count($businessSubjects)))], // Bob
            ['student_id' => 3, 'subject_ids' => array_rand(array_flip($businessSubjects), min(3, count($businessSubjects)))], // Charlie
            ['student_id' => 4, 'subject_ids' => array_rand(array_flip($businessSubjects), min(3, count($businessSubjects)))], // David
            ['student_id' => 5, 'subject_ids' => array_rand(array_flip($businessSubjects), min(3, count($businessSubjects)))], // Eve
            ['student_id' => 6, 'subject_ids' => array_rand(array_flip($businessSubjects), min(3, count($businessSubjects)))], // Frank
            
            // IT students
            ['student_id' => 7, 'subject_ids' => array_rand(array_flip($itSubjects), min(3, count($itSubjects)))],       // Grace
            ['student_id' => 8, 'subject_ids' => array_rand(array_flip($itSubjects), min(3, count($itSubjects)))],       // Hank
            ['student_id' => 9, 'subject_ids' => array_rand(array_flip($itSubjects), min(3, count($itSubjects)))],       // Ivy
            ['student_id' => 10, 'subject_ids' => array_rand(array_flip($itSubjects), min(3, count($itSubjects)))],      // Jack
            ['student_id' => 11, 'subject_ids' => array_rand(array_flip($itSubjects), min(3, count($itSubjects)))],      // Kelly
            ['student_id' => 12, 'subject_ids' => array_rand(array_flip($itSubjects), min(3, count($itSubjects)))],      // Liam
            
            // Science students
            ['student_id' => 13, 'subject_ids' => array_rand(array_flip($scienceSubjects), min(3, count($scienceSubjects)))],  // Mia
            ['student_id' => 14, 'subject_ids' => array_rand(array_flip($scienceSubjects), min(3, count($scienceSubjects)))],  // Noah
            ['student_id' => 15, 'subject_ids' => array_rand(array_flip($scienceSubjects), min(3, count($scienceSubjects)))],  // Olivia
            ['student_id' => 16, 'subject_ids' => array_rand(array_flip($scienceSubjects), min(3, count($scienceSubjects)))],  // Peter
            ['student_id' => 17, 'subject_ids' => array_rand(array_flip($scienceSubjects), min(3, count($scienceSubjects)))],  // Quinn
            ['student_id' => 18, 'subject_ids' => array_rand(array_flip($scienceSubjects), min(3, count($scienceSubjects)))],  // Rose
        ];

        foreach ($enrollments as $enrollment) {
            foreach ((array)$enrollment['subject_ids'] as $subjectId) {
                DB::table('student_subject')->updateOrInsert(
                    [
                        'student_id' => $enrollment['student_id'],
                        'subject_id' => $subjectId,
                    ],
                    [
                        'is_active' => true,
                        'enrolled_date' => now(), // Add enrolled_date with current timestamp
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
    }
}