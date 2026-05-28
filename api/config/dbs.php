<?php
// api/config/dbs.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// 1. โหลด Environment Variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        "status" => "error",
        "message" => "Configuration Error: .env file is missing or unreadable."
    ]);
    exit;
}

// 2. สร้าง Class Database ตามโครงสร้างเดิม
class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        // ดึงค่า Database Credentials จาก $_ENV ที่โหลดมา
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'artbids_db';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    // ฟังก์ชันสำหรับเชื่อมต่อฐานข้อมูล
    public function getConnection()
    {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // ป้องกัน SQL Injection ขั้นสูง
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            header('Content-Type: application/json', true, 500);
            // ไม่โชว์ Error ละเอียดของ SQL เพื่อความปลอดภัย
            echo json_encode([
                "status" => "error",
                "message" => "Database Connection Failed"
            ]);
            exit;
        }
        return $this->conn;
    }

    // รองรับกรณีที่ index.php เดิมของคุณเรียกใช้ฟังก์ชันชื่อ connect()
    public function connect()
    {
        return $this->getConnection();
    }
}

// 3. ฟังก์ชันสากลสำหรับให้โมดูลอื่น (เช่น e-KYC, JWT) เรียกใช้คีย์เข้ารหัส
function getJwtSecret()
{
    return $_ENV['JWT_SECRET'] ?? '';
}

function getEncryptionKeys()
{
    return [
        'key' => $_ENV['AES_KEY'] ?? '',
        'iv' => $_ENV['AES_IV'] ?? ''
    ];
}