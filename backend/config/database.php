<?php
/**
 * Koneksi database menggunakan PDO.
 * Sesuaikan nilai di bawah dengan lingkungan lokal masing-masing.
 */
class Database
{
    private $host = 'localhost';
    private $db = 'kantin_sekolah';
    private $user = 'root';
    private $pass = 'rpl12345';
    private $charset = 'utf8mb4';

    public function connect(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal',
                'data' => null,
            ]);
            exit;
        }
    }

    public function getConnection(): PDO
    {
        return $this->connect();
    }
}