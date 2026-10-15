# Library Management and Book Borrowing System
ระบบจัดการห้องสมุดและการยืม-คืนหนังสือ — PHP + MySQL (XAMPP)

ออกแบบฐานข้อมูลตามเอกสารต้นฉบับ ครบ 13 ตาราง อยู่ในรูปแบบ **3NF**
(1NF: แยก BookAuthors ออกจาก Books เพราะผู้แต่งมีได้หลายคน / 2NF: ไม่มี Partial Dependency ใน BookAuthors / 3NF: แยก Roles ออกจาก Users เพื่อไม่ให้มี Transitive Dependency ผ่าน RoleName)

ระบบผ่านการทดสอบจริงแล้ว: ล็อกอิน, ค้นหาหนังสือ, ยืม, คืน (พร้อมคำนวณค่าปรับคืนช้า+เสียหายอัตโนมัติ), ชำระค่าปรับ, จอง, และหน้ารายงาน

---

## 1. วิธีติดตั้งบน XAMPP

### ขั้นตอนที่ 1 — วางไฟล์โปรเจค
คัดลอกโฟลเดอร์ `library_system` ทั้งหมดไปไว้ที่โฟลเดอร์ `htdocs` ของ XAMPP
- Windows: `C:\xampp\htdocs\library_system`
- macOS: `/Applications/XAMPP/htdocs/library_system`

### ขั้นตอนที่ 2 — เปิด Apache และ MySQL
เปิดโปรแกรม **XAMPP Control Panel** แล้วกด **Start** ที่ Apache และ MySQL

### ขั้นตอนที่ 3 — สร้างฐานข้อมูล
1. เปิดเบราว์เซอร์ไปที่ `http://localhost/phpmyadmin`
2. คลิกแท็บ **Import** (นำเข้า)
3. เลือกไฟล์ `database/library_db.sql` แล้วกด **Go** (ไป)
   - ไฟล์นี้จะสร้างฐานข้อมูลชื่อ `library_db`, สร้างตารางทั้ง 13 ตารางพร้อม Foreign Key ครบ, และใส่ข้อมูลตัวอย่างให้ (สมาชิกทดสอบ + หนังสือ 3 เล่ม)

### ขั้นตอนที่ 4 — ตรวจสอบการตั้งค่าเชื่อมต่อฐานข้อมูล
เปิดไฟล์ `config/database.php` — ค่าเริ่มต้นตั้งไว้ตามมาตรฐานของ XAMPP อยู่แล้ว:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db');
define('DB_USER', 'root');
define('DB_PASS', '');   // XAMPP ปกติไม่มีรหัสผ่าน root
```
ถ้าเครื่องคุณตั้งรหัสผ่าน root ไว้เอง ให้แก้ `DB_PASS` ให้ตรงกัน

### ขั้นตอนที่ 5 — เข้าใช้งาน
เปิดเบราว์เซอร์ไปที่:
```
http://localhost/library_system/
```

**บัญชีทดสอบ** (รหัสผ่านทั้งคู่คือ `123456`):
| Username | Role | ใช้ทดสอบ |
|---|---|---|
| `admin` | Admin | จัดการหนังสือ/สมาชิก/การยืม-คืน/ค่าปรับ/รายงาน |
| `member1` | Member | ค้นหา/ยืม/คืน/จ่ายค่าปรับ/จองหนังสือ |

---

## 2. โครงสร้างโปรเจค

```
library_system/
├── database/
│   └── library_db.sql        ← Import เข้า phpMyAdmin ไฟล์นี้ไฟล์เดียว
├── config/
│   └── database.php          ← ตั้งค่าเชื่อมต่อฐานข้อมูล (PDO)
├── includes/
│   ├── functions.php         ← session, auth guard, คำนวณค่าปรับ
│   ├── header.php / footer.php
├── assets/
│   └── style.css
├── index.php / login.php / register.php / logout.php
├── member/                   ← หน้าสำหรับสมาชิก
│   ├── dashboard.php, books.php, book_detail.php
│   ├── my_borrowings.php, my_fines.php, my_reservations.php
│   └── profile.php
└── admin/                    ← หน้าสำหรับผู้ดูแลระบบ
    ├── dashboard.php, books.php, book_form.php, copies.php
    ├── authors.php, categories.php, members.php
    ├── borrowings.php, returns.php, fines.php, reports.php
```

## 3. ฟีเจอร์ตามที่ออกแบบไว้ในเอกสาร

- **Member**: สมัครสมาชิก/Login/แก้ไขข้อมูล/ค้นหาหนังสือ/ดูรายละเอียด/ยืม/ดูประวัติการยืม/ดูค่าปรับ/ชำระค่าปรับ/จองหนังสือที่ถูกยืมหมด
- **Admin**: จัดการสมาชิก/หนังสือ/ผู้แต่ง/หมวดหมู่/เล่มหนังสือ (BookCopies)/ตรวจสอบการยืม-คืน/จัดการค่าปรับ/ตรวจสอบการชำระเงิน/ดูรายงาน
- **ค่าปรับ** (แก้ไขค่าได้ในไฟล์ `includes/functions.php`):
  - คืนช้า = 10 บาท/วัน
  - เสียหาย Minor = 50 / Moderate = 150 / Severe = 300 บาท
  - หนังสือหาย = ราคาหนังสือ + 50 บาท
- **Transaction**: การยืม/คืนใช้ PDO Transaction (`beginTransaction/commit/rollBack`) ป้องกันข้อมูลไม่สอดคล้องกัน เช่นกัน 2 คนยืมเล่มเดียวกันพร้อมกัน (ใช้ `SELECT ... FOR UPDATE` ล็อกแถวระหว่างตรวจสอบสถานะ)
- **รายงาน SQL**: หนังสือที่ถูกยืมมากที่สุด, สมาชิกที่ยืมมากที่สุด, หมวดหนังสือยอดนิยม, ค่าปรับทั้งหมด/ค้างชำระ, หนังสือที่เสียหาย

## 4. หมายเหตุด้านความปลอดภัย
- รหัสผ่านเก็บด้วย `password_hash()` (bcrypt) และตรวจสอบด้วย `password_verify()`
- ทุก query ใช้ PDO Prepared Statement ป้องกัน SQL Injection
- ข้อความที่แสดงผลบนหน้าเว็บผ่านฟังก์ชัน `e()` (htmlspecialchars) ป้องกัน XSS
- ก่อนนำไปใช้งานจริง (production) ควรเปลี่ยนรหัสผ่านบัญชีทดสอบ และตั้งรหัสผ่านให้ MySQL root ด้วย
