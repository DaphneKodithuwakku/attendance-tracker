<?php
require_once '../models/Student.php';
require_once '../models/Subject.php';
require_once '../models/Attendance.php';

class AttendanceController {
    private $student;
    private $subject;
    private $attendance;

    public function __construct() {
        $this->student = new Student();
        $this->subject = new Subject();
        $this->attendance = new Attendance();
    }

    public function getStudentsBySubject() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['subject_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject ID is required']);
            return;
        }

        $subject_id = $_GET['subject_id'];
        $students = $this->student->getStudentsBySubject($subject_id);
        
        echo json_encode(['success' => true, 'data' => $students]);
    }

    public function markAttendance() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['subject_id']) || !isset($input['date']) || !isset($input['attendance'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $subject_id = $input['subject_id'];
        $date = $input['date'];
        $attendance_data = $input['attendance'];
        $marked_by = $input['marked_by'] ?? 'Teacher';

        try {
            foreach ($attendance_data as $record) {
                $this->attendance->markAttendance(
                    $record['student_id'],
                    $subject_id,
                    $date,
                    $record['status'],
                    $marked_by
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to mark attendance: ' . $e->getMessage()]);
        }
    }

    public function getAttendanceStats() {
        header('Content-Type: application/json');
        
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 week'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        $subject_id = $_GET['subject_id'] ?? null;

        $stats = $this->student->getStudentAttendanceStats($date_from, $date_to, $subject_id);
        
        echo json_encode(['success' => true, 'data' => $stats]);
    }

    public function getAllSubjects() {
        header('Content-Type: application/json');
        
        $subjects = $this->subject->getAllSubjects();
        echo json_encode(['success' => true, 'data' => $subjects]);
    }
}

// Handle API requests
$controller = new AttendanceController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'students':
                $controller->getStudentsBySubject();
                break;
            case 'stats':
                $controller->getAttendanceStats();
                break;
            case 'subjects':
                $controller->getAllSubjects();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Action not found']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action']) && $_GET['action'] === 'mark') {
        $controller->markAttendance();
    }
}
?>
