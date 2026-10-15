-- =========================================================
-- Library Management and Book Borrowing System
-- ระบบจัดการห้องสมุดและการยืม-คืนหนังสือ
-- MySQL / MariaDB (สำหรับ XAMPP + phpMyAdmin)
-- ออกแบบตามหลัก 3NF
-- =========================================================

CREATE DATABASE IF NOT EXISTS library_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE library_db;

-- ---------------------------------------------------------
-- 1. Roles
-- ---------------------------------------------------------
CREATE TABLE Roles (
    RoleID   INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 2. Users (Members + Admins)
-- ---------------------------------------------------------
CREATE TABLE Users (
    UserID       INT AUTO_INCREMENT PRIMARY KEY,
    Username     VARCHAR(50)  NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    FullName     VARCHAR(100) NOT NULL,
    Email        VARCHAR(100) UNIQUE,
    Phone        VARCHAR(20),
    Address      VARCHAR(255),
    RegisterDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RoleID       INT NOT NULL,
    Status       VARCHAR(20) NOT NULL DEFAULT 'Active',
    CONSTRAINT FK_Users_Roles
        FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 3. Categories
-- ---------------------------------------------------------
CREATE TABLE Categories (
    CategoryID   INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL UNIQUE,
    Description  VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 4. Books  (ข้อมูล "ชื่อเรื่อง" ของหนังสือ)
-- ---------------------------------------------------------
CREATE TABLE Books (
    BookID      INT AUTO_INCREMENT PRIMARY KEY,
    ISBN        VARCHAR(20) UNIQUE,
    Title       VARCHAR(255) NOT NULL,
    Publisher   VARCHAR(150),
    PublishYear INT,
    Description TEXT,
    Price       DECIMAL(10,2) NOT NULL,
    CategoryID  INT NOT NULL,
    CONSTRAINT FK_Books_Categories
        FOREIGN KEY (CategoryID) REFERENCES Categories(CategoryID),
    CONSTRAINT CK_Books_Price CHECK (Price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 5. Authors
-- ---------------------------------------------------------
CREATE TABLE Authors (
    AuthorID   INT AUTO_INCREMENT PRIMARY KEY,
    AuthorName VARCHAR(150) NOT NULL,
    Biography  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 6. BookAuthors (M:N ระหว่าง Books และ Authors)
-- ---------------------------------------------------------
CREATE TABLE BookAuthors (
    BookID   INT NOT NULL,
    AuthorID INT NOT NULL,
    PRIMARY KEY (BookID, AuthorID),
    CONSTRAINT FK_BookAuthors_Books
        FOREIGN KEY (BookID) REFERENCES Books(BookID) ON DELETE CASCADE,
    CONSTRAINT FK_BookAuthors_Authors
        FOREIGN KEY (AuthorID) REFERENCES Authors(AuthorID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 7. BookCopies (หนังสือแต่ละ "เล่ม" จริงบนชั้น)
-- ---------------------------------------------------------
CREATE TABLE BookCopies (
    CopyID        INT AUTO_INCREMENT PRIMARY KEY,
    BookID        INT NOT NULL,
    Barcode       VARCHAR(50) NOT NULL UNIQUE,
    BookCondition VARCHAR(20) NOT NULL DEFAULT 'Good',
    Status        VARCHAR(20) NOT NULL DEFAULT 'Available',
    ShelfLocation VARCHAR(50),
    CONSTRAINT FK_BookCopies_Books
        FOREIGN KEY (BookID) REFERENCES Books(BookID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 8. Borrowings (หัวรายการยืม 1 ครั้ง)
-- ---------------------------------------------------------
CREATE TABLE Borrowings (
    BorrowID   INT AUTO_INCREMENT PRIMARY KEY,
    UserID     INT NOT NULL,
    BorrowDate DATE NOT NULL,
    DueDate    DATE NOT NULL,
    Status     VARCHAR(20) NOT NULL DEFAULT 'Borrowed',
    CONSTRAINT FK_Borrowings_Users
        FOREIGN KEY (UserID) REFERENCES Users(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 9. BorrowingDetails (รายการย่อย: เล่มไหนถูกยืมในครั้งนั้น)
-- ---------------------------------------------------------
CREATE TABLE BorrowingDetails (
    BorrowDetailID INT AUTO_INCREMENT PRIMARY KEY,
    BorrowID       INT NOT NULL,
    CopyID         INT NOT NULL,
    CONSTRAINT FK_BorrowingDetails_Borrowings
        FOREIGN KEY (BorrowID) REFERENCES Borrowings(BorrowID),
    CONSTRAINT FK_BorrowingDetails_BookCopies
        FOREIGN KEY (CopyID) REFERENCES BookCopies(CopyID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 10. Returns
-- ---------------------------------------------------------
CREATE TABLE Returns (
    ReturnID          INT AUTO_INCREMENT PRIMARY KEY,
    BorrowDetailID    INT NOT NULL,
    ReturnDate        DATE NOT NULL,
    BookCondition     VARCHAR(20) NOT NULL,
    DamageDescription VARCHAR(500),
    CONSTRAINT FK_Returns_BorrowingDetails
        FOREIGN KEY (BorrowDetailID) REFERENCES BorrowingDetails(BorrowDetailID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 11. Fines
-- ---------------------------------------------------------
CREATE TABLE Fines (
    FineID      INT AUTO_INCREMENT PRIMARY KEY,
    ReturnID    INT NOT NULL,
    FineType    VARCHAR(20) NOT NULL,   -- Late / Damage / Lost
    Amount      DECIMAL(10,2) NOT NULL,
    Description VARCHAR(500),
    FineStatus  VARCHAR(20) NOT NULL DEFAULT 'Unpaid',
    CreatedDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT FK_Fines_Returns
        FOREIGN KEY (ReturnID) REFERENCES Returns(ReturnID),
    CONSTRAINT CK_Fines_Amount CHECK (Amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 12. Payments
-- ---------------------------------------------------------
CREATE TABLE Payments (
    PaymentID     INT AUTO_INCREMENT PRIMARY KEY,
    FineID        INT NOT NULL,
    UserID        INT NOT NULL,
    PaymentDate   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Amount        DECIMAL(10,2) NOT NULL,
    PaymentMethod VARCHAR(30) NOT NULL,
    Status        VARCHAR(20) NOT NULL DEFAULT 'Completed',
    CONSTRAINT FK_Payments_Fines
        FOREIGN KEY (FineID) REFERENCES Fines(FineID),
    CONSTRAINT FK_Payments_Users
        FOREIGN KEY (UserID) REFERENCES Users(UserID),
    CONSTRAINT CK_Payments_Amount CHECK (Amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 13. Reservations
-- ---------------------------------------------------------
CREATE TABLE Reservations (
    ReservationID   INT AUTO_INCREMENT PRIMARY KEY,
    UserID          INT NOT NULL,
    BookID          INT NOT NULL,
    ReservationDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Status          VARCHAR(20) NOT NULL DEFAULT 'Waiting',
    CONSTRAINT FK_Reservations_Users
        FOREIGN KEY (UserID) REFERENCES Users(UserID),
    CONSTRAINT FK_Reservations_Books
        FOREIGN KEY (BookID) REFERENCES Books(BookID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- ข้อมูลตัวอย่าง (Sample Data)
-- =========================================================

INSERT INTO Roles (RoleName) VALUES ('Admin'), ('Member');

-- รหัสผ่านของทั้งสองบัญชีคือ  123456
-- (hash ด้านล่างคือ password_hash('123456', PASSWORD_DEFAULT) — ใช้ล็อกอินทดสอบได้ทันที)
INSERT INTO Users (Username, PasswordHash, FullName, Email, Phone, Address, RoleID, Status) VALUES
('admin',  '$2b$10$D3PaH/ZpkqFnrnM9EAdTcuDnXR5ZPOVBIHjBRh9fEq5yhxCUBMB7.', 'ผู้ดูแลระบบ', 'admin@library.local', '0800000000', 'Library Office', 1, 'Active'),
('member1','$2b$10$D3PaH/ZpkqFnrnM9EAdTcuDnXR5ZPOVBIHjBRh9fEq5yhxCUBMB7.', 'สมชาย ใจดี', 'member1@library.local', '0811111111', '123 ถ.สุขุมวิท', 2, 'Active');

INSERT INTO Categories (CategoryName, Description) VALUES
('Database', 'Database System'),
('Programming', 'Programming Languages'),
('Computer', 'Computer Science'),
('Mathematics', 'Mathematics');

INSERT INTO Authors (AuthorName) VALUES
('Abraham Silberschatz'),
('Ramez Elmasri'),
('Herbert Schildt'),
('Robert C. Martin');

INSERT INTO Books (ISBN, Title, Publisher, PublishYear, Description, Price, CategoryID) VALUES
('9780078022159', 'Database System Concepts', 'McGraw-Hill', 2019, 'ตำราแนวคิดระบบฐานข้อมูล', 650, 1),
('9780131103627', 'The C Programming Language', 'Prentice Hall', 1988, 'ตำราภาษา C ฉบับคลาสสิก', 450, 2),
('9780132350884', 'Clean Code', 'Prentice Hall', 2008, 'แนวทางเขียนโค้ดให้สะอาด', 550, 2);

INSERT INTO BookAuthors (BookID, AuthorID) VALUES
(1, 1), (1, 2), (2, 3), (3, 4);

INSERT INTO BookCopies (BookID, Barcode, BookCondition, Status, ShelfLocation) VALUES
(1, 'BC-0001', 'Good', 'Available', 'A1-01'),
(1, 'BC-0002', 'Good', 'Available', 'A1-02'),
(2, 'BC-0003', 'Good', 'Available', 'B2-01'),
(3, 'BC-0004', 'Good', 'Available', 'B2-05');
