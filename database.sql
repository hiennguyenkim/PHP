-- ============================================================
-- HCMUE Library - Database Schema
-- Database: library_db
-- Run: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

-- -------------------------------------------------------
-- 1. Bảng users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    role        ENUM('admin','student') NOT NULL DEFAULT 'student',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 2. Bảng books
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    author      VARCHAR(150) NOT NULL,
    isbn        VARCHAR(30)  DEFAULT NULL,
    category    VARCHAR(100) NOT NULL DEFAULT 'Khác',
    quantity    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status      ENUM('available','borrowed','unavailable') NOT NULL DEFAULT 'available',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 3. Bảng borrow_records
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS borrow_records (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    borrow_date DATE         NOT NULL,
    return_date DATE         DEFAULT NULL,
    status      ENUM('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_borrow_book FOREIGN KEY (book_id) REFERENCES books(id)  ON DELETE CASCADE,
    CONSTRAINT fk_borrow_user FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Dữ liệu mẫu
-- -------------------------------------------------------
-- Admin: admin / Admin@123
INSERT INTO users (username, password, full_name, role) VALUES
('admin',   '$2y$10$yFpGq4323AOg24smPvUYN.w25VXTBBYjhBMboCAaCfZwrbcvwSQja', 'Quản trị viên', 'admin'),
('student1','$2y$10$yFpGq4323AOg24smPvUYN.w25VXTBBYjhBMboCAaCfZwrbcvwSQja', 'Nguyễn Văn An',  'student');

INSERT INTO books (title, author, isbn, category, quantity, status) VALUES
('Lập trình PHP hiện đại',     'Nguyen Van A',  '978-604-0001-01-1', 'Công nghệ',  5, 'available'),
('Kiến trúc phần mềm MVC',     'Tran Thi B',    '978-604-0001-02-8', 'Công nghệ',  3, 'available'),
('Cơ sở dữ liệu nâng cao',     'Le Van C',      '978-604-0001-03-5', 'Công nghệ',  4, 'available'),
('Toán rời rạc ứng dụng',      'Pham Thi D',    '978-604-0002-01-7', 'Toán học',   6, 'available'),
('Giải tích 1',                'Do Van E',      '978-604-0002-02-4', 'Toán học',   8, 'available'),
('Triết học Mác-Lênin',        'Nguyen Thi F',  NULL,                'Lý luận',    10,'available');
