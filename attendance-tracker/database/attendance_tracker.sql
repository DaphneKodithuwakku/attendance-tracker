-- Create database
CREATE DATABASE IF NOT EXISTS attendance_tracker;
USE attendance_tracker;

-- Create subjects table
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(10) NOT NULL UNIQUE,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    year_level ENUM('1', '2', '3', '4') DEFAULT '1',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_registration_active (registration_number, is_active),
    INDEX idx_name_search (first_name, last_name),
    INDEX idx_email (email)
);

-- Create student_subject table (many-to-many relationship)
CREATE TABLE student_subject (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    enrolled_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_id),
    INDEX idx_subject_active (subject_id, is_active),
    INDEX idx_student_active (student_id, is_active)
);

-- Create attendance table with optimized structure for million records
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    present BOOLEAN NOT NULL DEFAULT TRUE,
    remarks TEXT,
    marked_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate attendance
    UNIQUE KEY unique_attendance (student_id, subject_id, attendance_date),
    
    -- Performance indexes for million records
    INDEX idx_student_subject (student_id, subject_id),
    INDEX idx_date_subject (attendance_date, subject_id),
    INDEX idx_date_range (attendance_date),
    INDEX idx_present_status (present),
    INDEX idx_marked_by (marked_by)
);

-- Insert sample subjects (5 subjects as per requirement)
INSERT INTO subjects (subject_code, subject_name, description) VALUES
('CS101', 'Introduction to Computer Science', 'Fundamentals of programming and computer systems'),
('MATH101', 'Calculus I', 'Differential and integral calculus'),
('ENG101', 'English Composition', 'Academic writing and communication skills'),
('PHYS101', 'Physics I', 'Mechanics and thermodynamics'),
('CHEM101', 'Chemistry I', 'General chemistry principles and laboratory');

-- Insert sample students (First year students)
INSERT INTO students (registration_number, first_name, last_name, email, year_level) VALUES
('2024001', 'John', 'Doe', 'john.doe@deakin.edu.au', '1'),
('2024002', 'Jane', 'Smith', 'jane.smith@deakin.edu.au', '1'),
('2024003', 'Mike', 'Johnson', 'mike.johnson@deakin.edu.au', '1'),
('2024004', 'Sarah', 'Wilson', 'sarah.wilson@deakin.edu.au', '1'),
('2024005', 'David', 'Brown', 'david.brown@deakin.edu.au', '1'),
('2024006', 'Emily', 'Davis', 'emily.davis@deakin.edu.au', '1'),
('2024007', 'Chris', 'Miller', 'chris.miller@deakin.edu.au', '1'),
('2024008', 'Lisa', 'Garcia', 'lisa.garcia@deakin.edu.au', '1'),
('2024009', 'Tom', 'Anderson', 'tom.anderson@deakin.edu.au', '1'),
('2024010', 'Anna', 'Taylor', 'anna.taylor@deakin.edu.au', '1'),
('2024011', 'Robert', 'Martinez', 'robert.martinez@deakin.edu.au', '1'),
('2024012', 'Jessica', 'Lee', 'jessica.lee@deakin.edu.au', '1'),
('2024013', 'Michael', 'White', 'michael.white@deakin.edu.au', '1'),
('2024014', 'Ashley', 'Thompson', 'ashley.thompson@deakin.edu.au', '1'),
('2024015', 'Daniel', 'Harris', 'daniel.harris@deakin.edu.au', '1');

-- Enroll students in subjects (MINIMUM 3 subjects each, some have 4-5)
-- Each student MUST be enrolled in at least 3 subjects out of 5

-- Student 1 (John Doe) - 4 subjects: CS, MATH, ENG, PHYS
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(1, 1, '2024-01-15'), (1, 2, '2024-01-15'), (1, 3, '2024-01-15'), (1, 4, '2024-01-15');

-- Student 2 (Jane Smith) - 5 subjects: ALL subjects
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(2, 1, '2024-01-15'), (2, 2, '2024-01-15'), (2, 3, '2024-01-15'), (2, 4, '2024-01-15'), (2, 5, '2024-01-15');

-- Student 3 (Mike Johnson) - 3 subjects: CS, ENG, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(3, 1, '2024-01-15'), (3, 3, '2024-01-15'), (3, 5, '2024-01-15');

-- Student 4 (Sarah Wilson) - 4 subjects: MATH, ENG, PHYS, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(4, 2, '2024-01-15'), (4, 3, '2024-01-15'), (4, 4, '2024-01-15'), (4, 5, '2024-01-15');

-- Student 5 (David Brown) - 3 subjects: CS, MATH, PHYS
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(5, 1, '2024-01-15'), (5, 2, '2024-01-15'), (5, 4, '2024-01-15');

-- Student 6 (Emily Davis) - 4 subjects: CS, MATH, ENG, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(6, 1, '2024-01-15'), (6, 2, '2024-01-15'), (6, 3, '2024-01-15'), (6, 5, '2024-01-15');

-- Student 7 (Chris Miller) - 3 subjects: MATH, PHYS, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(7, 2, '2024-01-15'), (7, 4, '2024-01-15'), (7, 5, '2024-01-15');

-- Student 8 (Lisa Garcia) - 4 subjects: CS, ENG, PHYS, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(8, 1, '2024-01-15'), (8, 3, '2024-01-15'), (8, 4, '2024-01-15'), (8, 5, '2024-01-15');

-- Student 9 (Tom Anderson) - 3 subjects: CS, MATH, ENG
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(9, 1, '2024-01-15'), (9, 2, '2024-01-15'), (9, 3, '2024-01-15');

-- Student 10 (Anna Taylor) - 4 subjects: MATH, ENG, PHYS, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(10, 2, '2024-01-15'), (10, 3, '2024-01-15'), (10, 4, '2024-01-15'), (10, 5, '2024-01-15');

-- Student 11 (Robert Martinez) - 3 subjects: CS, PHYS, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(11, 1, '2024-01-15'), (11, 4, '2024-01-15'), (11, 5, '2024-01-15');

-- Student 12 (Jessica Lee) - 5 subjects: ALL subjects
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(12, 1, '2024-01-15'), (12, 2, '2024-01-15'), (12, 3, '2024-01-15'), (12, 4, '2024-01-15'), (12, 5, '2024-01-15');

-- Student 13 (Michael White) - 3 subjects: MATH, ENG, PHYS
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(13, 2, '2024-01-15'), (13, 3, '2024-01-15'), (13, 4, '2024-01-15');

-- Student 14 (Ashley Thompson) - 4 subjects: CS, MATH, ENG, CHEM
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(14, 1, '2024-01-15'), (14, 2, '2024-01-15'), (14, 3, '2024-01-15'), (14, 5, '2024-01-15');

-- Student 15 (Daniel Harris) - 3 subjects: CS, ENG, PHYS
INSERT INTO student_subject (student_id, subject_id, enrolled_date) VALUES
(15, 1, '2024-01-15'), (15, 3, '2024-01-15'), (15, 4, '2024-01-15');

-- Insert comprehensive sample attendance data for testing (last 3 weeks)
-- This ensures we have realistic attendance patterns for dashboard testing

-- Week 1 (3 weeks ago): January 8-12, 2024
INSERT INTO attendance (student_id, subject_id, attendance_date, present, marked_by) VALUES
-- CS101 - Monday, Wednesday, Friday
(1, 1, '2024-01-08', TRUE, 'Prof. Smith'), (2, 1, '2024-01-08', TRUE, 'Prof. Smith'), (3, 1, '2024-01-08', FALSE, 'Prof. Smith'),
(5, 1, '2024-01-08', TRUE, 'Prof. Smith'), (6, 1, '2024-01-08', TRUE, 'Prof. Smith'), (8, 1, '2024-01-08', TRUE, 'Prof. Smith'),
(9, 1, '2024-01-08', FALSE, 'Prof. Smith'), (11, 1, '2024-01-08', TRUE, 'Prof. Smith'), (12, 1, '2024-01-08', TRUE, 'Prof. Smith'),
(14, 1, '2024-01-08', TRUE, 'Prof. Smith'), (15, 1, '2024-01-08', TRUE, 'Prof. Smith'),

(1, 1, '2024-01-10', TRUE, 'Prof. Smith'), (2, 1, '2024-01-10', TRUE, 'Prof. Smith'), (3, 1, '2024-01-10', TRUE, 'Prof. Smith'),
(5, 1, '2024-01-10', FALSE, 'Prof. Smith'), (6, 1, '2024-01-10', TRUE, 'Prof. Smith'), (8, 1, '2024-01-10', TRUE, 'Prof. Smith'),
(9, 1, '2024-01-10', TRUE, 'Prof. Smith'), (11, 1, '2024-01-10', TRUE, 'Prof. Smith'), (12, 1, '2024-01-10', FALSE, 'Prof. Smith'),
(14, 1, '2024-01-10', TRUE, 'Prof. Smith'), (15, 1, '2024-01-10', TRUE, 'Prof. Smith'),

-- MATH101 - Tuesday, Thursday
(1, 2, '2024-01-09', TRUE, 'Dr. Johnson'), (2, 2, '2024-01-09', TRUE, 'Dr. Johnson'), (4, 2, '2024-01-09', FALSE, 'Dr. Johnson'),
(5, 2, '2024-01-09', TRUE, 'Dr. Johnson'), (6, 2, '2024-01-09', TRUE, 'Dr. Johnson'), (7, 2, '2024-01-09', TRUE, 'Dr. Johnson'),
(9, 2, '2024-01-09', TRUE, 'Dr. Johnson'), (10, 2, '2024-01-09', FALSE, 'Dr. Johnson'), (12, 2, '2024-01-09', TRUE, 'Dr. Johnson'),
(13, 2, '2024-01-09', TRUE, 'Dr. Johnson'), (14, 2, '2024-01-09', TRUE, 'Dr. Johnson'),

(1, 2, '2024-01-11', FALSE, 'Dr. Johnson'), (2, 2, '2024-01-11', TRUE, 'Dr. Johnson'), (4, 2, '2024-01-11', TRUE, 'Dr. Johnson'),
(5, 2, '2024-01-11', TRUE, 'Dr. Johnson'), (6, 2, '2024-01-11', TRUE, 'Dr. Johnson'), (7, 2, '2024-01-11', FALSE, 'Dr. Johnson'),
(9, 2, '2024-01-11', TRUE, 'Dr. Johnson'), (10, 2, '2024-01-11', TRUE, 'Dr. Johnson'), (12, 2, '2024-01-11', TRUE, 'Dr. Johnson'),
(13, 2, '2024-01-11', FALSE, 'Dr. Johnson'), (14, 2, '2024-01-11', TRUE, 'Dr. Johnson');

-- Week 2 (2 weeks ago): January 15-19, 2024
INSERT INTO attendance (student_id, subject_id, attendance_date, present, marked_by) VALUES
-- ENG101 - Monday, Wednesday, Friday
(1, 3, '2024-01-15', TRUE, 'Prof. Williams'), (2, 3, '2024-01-15', TRUE, 'Prof. Williams'), (3, 3, '2024-01-15', TRUE, 'Prof. Williams'),
(4, 3, '2024-01-15', FALSE, 'Prof. Williams'), (6, 3, '2024-01-15', TRUE, 'Prof. Williams'), (8, 3, '2024-01-15', TRUE, 'Prof. Williams'),
(9, 3, '2024-01-15', TRUE, 'Prof. Williams'), (10, 3, '2024-01-15', TRUE, 'Prof. Williams'), (12, 3, '2024-01-15', FALSE, 'Prof. Williams'),
(13, 3, '2024-01-15', TRUE, 'Prof. Williams'), (14, 3, '2024-01-15', TRUE, 'Prof. Williams'), (15, 3, '2024-01-15', TRUE, 'Prof. Williams'),

-- PHYS101 - Tuesday, Thursday
(1, 4, '2024-01-16', TRUE, 'Dr. Brown'), (2, 4, '2024-01-16', TRUE, 'Dr. Brown'), (4, 4, '2024-01-16', TRUE, 'Dr. Brown'),
(5, 4, '2024-01-16', FALSE, 'Dr. Brown'), (8, 4, '2024-01-16', TRUE, 'Dr. Brown'), (10, 4, '2024-01-16', TRUE, 'Dr. Brown'),
(11, 4, '2024-01-16', TRUE, 'Dr. Brown'), (12, 4, '2024-01-16', TRUE, 'Dr. Brown'), (13, 4, '2024-01-16', FALSE, 'Dr. Brown'),
(15, 4, '2024-01-16', TRUE, 'Dr. Brown'),

-- CHEM101 - Monday, Wednesday, Friday
(2, 5, '2024-01-17', TRUE, 'Prof. Davis'), (3, 5, '2024-01-17', FALSE, 'Prof. Davis'), (4, 5, '2024-01-17', TRUE, 'Prof. Davis'),
(6, 5, '2024-01-17', TRUE, 'Prof. Davis'), (7, 5, '2024-01-17', TRUE, 'Prof. Davis'), (8, 5, '2024-01-17', TRUE, 'Prof. Davis'),
(10, 5, '2024-01-17', FALSE, 'Prof. Davis'), (11, 5, '2024-01-17', TRUE, 'Prof. Davis'), (12, 5, '2024-01-17', TRUE, 'Prof. Davis'),
(14, 5, '2024-01-17', TRUE, 'Prof. Davis');

-- Week 3 (Last week): January 22-26, 2024
INSERT INTO attendance (student_id, subject_id, attendance_date, present, marked_by) VALUES
-- More recent attendance data for better dashboard testing
(1, 1, '2024-01-22', TRUE, 'Prof. Smith'), (2, 1, '2024-01-22', TRUE, 'Prof. Smith'), (3, 1, '2024-01-22', TRUE, 'Prof. Smith'),
(5, 1, '2024-01-22', TRUE, 'Prof. Smith'), (6, 1, '2024-01-22', FALSE, 'Prof. Smith'), (8, 1, '2024-01-22', TRUE, 'Prof. Smith'),
(9, 1, '2024-01-22', TRUE, 'Prof. Smith'), (11, 1, '2024-01-22', TRUE, 'Prof. Smith'), (12, 1, '2024-01-22', TRUE, 'Prof. Smith'),
(14, 1, '2024-01-22', FALSE, 'Prof. Smith'), (15, 1, '2024-01-22', TRUE, 'Prof. Smith'),

(1, 2, '2024-01-23', TRUE, 'Dr. Johnson'), (2, 2, '2024-01-23', TRUE, 'Dr. Johnson'), (4, 2, '2024-01-23', TRUE, 'Dr. Johnson'),
(5, 2, '2024-01-23', TRUE, 'Dr. Johnson'), (6, 2, '2024-01-23', TRUE, 'Dr. Johnson'), (7, 2, '2024-01-23', TRUE, 'Dr. Johnson'),
(9, 2, '2024-01-23', FALSE, 'Dr. Johnson'), (10, 2, '2024-01-23', TRUE, 'Dr. Johnson'), (12, 2, '2024-01-23', TRUE, 'Dr. Johnson'),
(13, 2, '2024-01-23', TRUE, 'Dr. Johnson'), (14, 2, '2024-01-23', TRUE, 'Dr. Johnson');

-- Verification query to check enrollment counts per student
-- This should show each student enrolled in 3-5 subjects
/*
SELECT 
    s.registration_number,
    s.first_name,
    s.last_name,
    COUNT(ss.subject_id) as enrolled_subjects,
    GROUP_CONCAT(sub.subject_code ORDER BY sub.subject_code) as subjects
FROM students s
JOIN student_subject ss ON s.id = ss.student_id
JOIN subjects sub ON ss.subject_id = sub.id
WHERE s.is_active = 1 AND ss.is_active = 1
GROUP BY s.id, s.registration_number, s.first_name, s.last_name
ORDER BY s.registration_number;
*/
