<?php
/**
 * การตั้งค่าเชื่อมต่อฐานข้อมูล — ค่ามาตรฐานของ XAMPP
 * XAMPP default: host=localhost, user=root, password="" (ว่าง), port=3306
 * ถ้าตั้งรหัสผ่าน root ไว้เอง ให้แก้ DB_PASS ด้านล่าง
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . $e->getMessage() .
                '<br>ตรวจสอบว่าเปิด Apache และ MySQL ใน XAMPP Control Panel แล้ว และได้ import database/library_db.sql เข้า phpMyAdmin แล้ว');
        }
    }
    return $pdo;
}
