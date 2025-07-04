<?php
require_once '../config/database.php';

class Student {
    private $conn;
    private $table_name = "students";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getStudentsBySubject($subject_id) {
        $query = "SELECT s.id, s.registration_number, s.first_name, s.last_name, s.email 
                  FROM " . $this->table_name . " s
                  INNER JOIN student_subjects ss ON s.id = ss.student_id
                  WHERE ss.subject_id = :subject_id
                  ORDER BY s.registration_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getStudentAttendanceStats($date_from, $date_to, $subject_id = null) {
        $subject_condition = $subject_id ? "AND a.subject_id = :subject_id" : "";
        
        $query = "SELECT 
                    s.id,
                    s.registration_number,
                    s.first_name,
                    s.last_name,
                    sub.subject_name,
                    sub.subject_code,
                    COUNT(a.id) as total_classes,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    ROUND(
                        (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2
                    ) as attendance_percentage
                  FROM students s
                  INNER JOIN student_subjects ss ON s.id = ss.student_id
                  INNER JOIN subjects sub ON ss.subject_id = sub.id
                  LEFT JOIN attendance a ON s.id = a.student_id 
                    AND a.subject_id = ss.subject_id 
                    AND a.attendance_date BETWEEN :date_from AND :date_to
                  WHERE 1=1 $subject_condition
                  GROUP BY s.id, sub.id
                  HAVING total_classes > 0
                  ORDER BY s.registration_number, sub.subject_code";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_from', $date_from);
        $stmt->bindParam(':date_to', $date_to);
        
        if ($subject_id) {
            $stmt->bindParam(':subject_id', $subject_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
