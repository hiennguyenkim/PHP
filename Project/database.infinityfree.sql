DROP TABLE IF EXISTS `phieu_muon`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `books` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `author` VARCHAR(150) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `publisher` VARCHAR(150) DEFAULT NULL,
    `published_year` SMALLINT DEFAULT NULL,
    `isbn` VARCHAR(30) DEFAULT NULL,
    `language` VARCHAR(50) NOT NULL DEFAULT 'Tieng Viet',
    `description` TEXT DEFAULT NULL,
    `cover_image` VARCHAR(255) DEFAULT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `available_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('available', 'low_stock', 'out_of_stock') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phieu_muon` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `book_id` INT NOT NULL,
    `borrow_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `returned_date` DATE DEFAULT NULL,
    `status` ENUM('pending', 'borrowed', 'returned', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending',
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_phieu_muon_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_phieu_muon_book`
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES
(1, 'admin',      '$2y$10$dagF5czhwN66M1aCFJEJoO6MPp4DUzWDauo.G7w.Xv4OxA1LhZNhO', 'Thu thu Nguyen Anh',   'admin@library.local',   'admin',  'active',   '2026-04-01 08:00:00'),
(2, 'nguyenvana', '$2y$10$CNx15zbZPHDGUGzxxTvJ4ewTtcJdxx9FjBJv8KNsLwERcGd1ND1da', 'Nguyen Van An',        'vana@example.com',     'member', 'active',   '2026-04-02 08:00:00'),
(3, 'tranthib',   '$2y$10$CNx15zbZPHDGUGzxxTvJ4ewTtcJdxx9FjBJv8KNsLwERcGd1ND1da', 'Tran Thi Bich',        'bich@example.com',     'member', 'active',   '2026-04-03 08:00:00'),
(4, 'leminhc',    '$2y$10$CNx15zbZPHDGUGzxxTvJ4ewTtcJdxx9FjBJv8KNsLwERcGd1ND1da', 'Le Minh Chau',         'chau@example.com',     'member', 'active',   '2026-04-04 08:00:00'),
(5, 'phamthud',   '$2y$10$CNx15zbZPHDGUGzxxTvJ4ewTtcJdxx9FjBJv8KNsLwERcGd1ND1da', 'Pham Thu Duong',       'duong@example.com',    'member', 'active',   '2026-04-05 08:00:00'),
(6, 'hoangson',   '$2y$10$CNx15zbZPHDGUGzxxTvJ4ewTtcJdxx9FjBJv8KNsLwERcGd1ND1da', 'Hoang Son',            'son@example.com',      'member', 'active',   '2026-04-06 08:00:00'),
(7, 'buitrang',   '$2y$10$CNx15zbZPHDGUGzxxTvJ4ewTtcJdxx9FjBJv8KNsLwERcGd1ND1da', 'Bui Trang',            'trang@example.com',    'member', 'inactive', '2026-04-07 08:00:00');

INSERT INTO `books` (`id`, `title`, `author`, `category`, `publisher`, `published_year`, `isbn`, `language`, `description`, `cover_image`, `quantity`, `available_quantity`, `status`, `created_at`) VALUES
(1, 'Thu Vien Nua Dem',        'Matt Haig',            'Tieu thuyet', 'NXB Tre',           2021, '9786041234501', 'Tieng Viet', 'Tieu thuyet ve su lua chon va nhung cuoc doi co the da xay ra.', 'https://covers.openlibrary.org/b/id/12593135-M.jpg', 8, 6, 'available',    '2026-04-01 09:00:00'),
(2, 'Sapiens',                 'Yuval Noah Harari',    'Lich su',     'Nha Nam',           2022, '9786041234502', 'Tieng Viet', 'Lich su phat trien cua loai nguoi tu qua khu den hien dai.',     'https://covers.openlibrary.org/b/id/8739161-M.jpg',  5, 5, 'available',    '2026-04-01 09:10:00'),
(3, 'Thoi Quen Nguyen Tu',     'James Clear',          'Ky nang',     'NXB Lao Dong',      2023, '9786041234503', 'Tieng Viet', 'Sach ky nang thiet lap he thong thoi quen nho nhung hieu qua.',  'https://covers.openlibrary.org/b/id/10432021-M.jpg', 6, 6, 'available',    '2026-04-01 09:20:00'),
(4, 'Nha Gia Kim',             'Paulo Coelho',         'Tieu thuyet', 'NXB Van Hoc',       2020, '9786041234504', 'Tieng Viet', 'Hanh trinh tim kho bau va y nghia cua giac mo.',                 'https://covers.openlibrary.org/b/id/8234272-M.jpg',  4, 2, 'low_stock',    '2026-04-01 09:30:00'),
(5, 'Lam Viec Sau',            'Cal Newport',          'Nang suat',   'Alpha Books',       2024, '9786041234505', 'Tieng Viet', 'Phuong phap tap trung sau de nang cao hieu suat hoc tap va lam viec.', 'https://covers.openlibrary.org/b/id/12606525-M.jpg', 3, 1, 'low_stock', '2026-04-01 09:40:00'),
(6, '1984',                    'George Orwell',        'Tieu thuyet', 'NXB Hoi Nha Van',   2019, '9786041234506', 'Tieng Viet', 'Tac pham kinh dien ve mot xa hoi kiem soat va mat tu do.',       'https://covers.openlibrary.org/b/id/153541-M.jpg',   0, 0, 'out_of_stock', '2026-04-01 09:50:00');

INSERT INTO `phieu_muon` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `returned_date`, `status`, `notes`, `created_at`) VALUES
(1, 2, 1, '2026-04-10', '2026-04-24', NULL,          'borrowed', 'Muon tai quay thu thu.',               '2026-04-10 10:00:00'),
(2, 3, 2, '2026-04-01', '2026-04-15', '2026-04-14', 'returned', 'Da tra dung han.',                     '2026-04-01 10:15:00'),
(3, 4, 4, '2026-04-05', '2026-04-12', NULL,          'overdue',  'Can nhac thanh vien gia han hoac tra.', '2026-04-05 10:30:00'),
(4, 5, 3, '2026-04-16', '2026-04-30', NULL,          'pending',  'Cho duyet tu thu thu.',                '2026-04-16 08:30:00'),
(5, 6, 4, '2026-04-11', '2026-04-25', NULL,          'borrowed', 'Sach dang duoc su dung cho bai tap lon.', '2026-04-11 09:00:00'),
(6, 2, 5, '2026-04-13', '2026-04-27', NULL,          'borrowed', 'Muon sach ky nang tu hoc.',            '2026-04-13 11:00:00'),
(7, 3, 5, '2026-04-14', '2026-04-28', NULL,          'borrowed', 'Muon them ban sao de hoc nhom.',      '2026-04-14 13:00:00'),
(8, 4, 1, '2026-04-15', '2026-04-29', NULL,          'borrowed', 'Phuc vu cau lac bo doc sach.',         '2026-04-15 14:00:00');
