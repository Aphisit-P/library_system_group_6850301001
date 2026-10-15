<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- ค่าปรับ (ตั้งค่าตามเอกสารออกแบบ ข้อ 1.6 — Admin ปรับได้ภายหลัง) ----------
define('FINE_LATE_PER_DAY', 10);      // บาท/วัน
define('FINE_DAMAGE_MINOR', 50);
define('FINE_DAMAGE_MODERATE', 150);
define('FINE_DAMAGE_SEVERE', 300);
define('FINE_LOST_EXTRA', 50);        // ค่าปรับหนังสือหาย = ราคาหนังสือ + ค่านี้

// ---------- Auth helpers ----------
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'UserID'   => $_SESSION['user_id'],
        'Username' => $_SESSION['username'],
        'FullName' => $_SESSION['full_name'],
        'RoleName' => $_SESSION['role_name'],
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role_name'] !== $role) {
        redirect('/index.php');
    }
}

function basePath(): string {
    // path ของโฟลเดอร์โปรเจคบน server เช่น /library_system
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // ถ้าอยู่ในโฟลเดอร์ member/ หรือ admin/ ให้ตัดออกหนึ่งระดับ
        if (basename($script) === 'member' || basename($script) === 'admin') {
            $script = dirname($script);
        }
        $base = rtrim($script, '/');
    }
    return $base;
}

function redirect(string $path): void {
    header('Location: ' . basePath() . $path);
    exit;
}

function flash(string $key, ?string $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float $n): string {
    return number_format($n, 2);
}

/** คำนวณค่าปรับคืนช้า (บาท) จากจำนวนวันที่เกินกำหนด */
function calcLateFine(int $lateDays): float {
    return $lateDays > 0 ? $lateDays * FINE_LATE_PER_DAY : 0;
}

/** คำนวณค่าปรับความเสียหายตามระดับ */
function calcDamageFine(string $condition): float {
    return match ($condition) {
        'Minor'    => FINE_DAMAGE_MINOR,
        'Moderate' => FINE_DAMAGE_MODERATE,
        'Severe'   => FINE_DAMAGE_SEVERE,
        default    => 0,
    };
}
