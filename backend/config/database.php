<?php
class Database {
    private $host = "localhost";
    private $db_name = "kantin_sekolah";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "message" => "Connection error: " . $exception->getMessage()
            ]);
            exit();
        }
        return $this->conn;
    }
}