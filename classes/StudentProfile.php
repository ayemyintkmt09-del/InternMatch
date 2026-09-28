<?php
class StudentProfile {
    private $conn;
    private $table_name = "student_profiles";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getProfileByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updateProfile($user_id, $data) {
        // Check if profile exists, if not insert, else update
        $existing = $this->getProfileByUserId($user_id);

        if (!$existing) {
            $query = "INSERT INTO " . $this->table_name . " (user_id, university, degree, academic_year, bio, location, availability, career_interest) 
                      VALUES (:user_id, :university, :degree, :academic_year, :bio, :location, :availability, :career_interest)";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET university = :university, degree = :degree, academic_year = :academic_year, 
                          bio = :bio, location = :location, availability = :availability, career_interest = :career_interest 
                      WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
        }

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":university", $data['university']);
        $stmt->bindParam(":degree", $data['degree']);
        $stmt->bindParam(":academic_year", $data['academic_year']);
        $stmt->bindParam(":bio", $data['bio']);
        $stmt->bindParam(":location", $data['location']);
        $stmt->bindParam(":availability", $data['availability']);
        $stmt->bindParam(":career_interest", $data['career_interest']);

        return $stmt->execute();
    }
}
?>