<?php
require_once '../config/database.php';

class Attendance {
    private $conn;
    private $table_name = "attendance";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function markAttendance($student_id, $subject_id, $date, $status, $marked_by) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (student_id, subject_id, attendance_date, status, marked_by) 
                  VALUES (:student_id, :subject_id, :attendance_date, :status, :marked_by)
                  ON DUPLICATE KEY UPDATE 
                  status = :status_update, marked_by = :marked_by_update";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':attendance_date', $date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':marked_by', $marked_by);
        $stmt->bindParam(':status_update', $status);
        $stmt->bindParam(':marked_by_update', $marked_by);
        
        return $stmt->execute();
    }

    public function getAttendanceByDate($subject_id, $date) {
        $query = "SELECT a.*, s.registration_number, s.first_name, s.last_name
                  FROM " . $this->table_name . " a
                  INNER JOIN students s ON a.student_id = s.id
                  WHERE a.subject_id = :subject_id AND a.attendance_date = :date
                  ORDER BY s.registration_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>
