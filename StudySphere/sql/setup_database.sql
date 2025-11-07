-- ================================================
-- HỆ THỐNG QUẢN LÝ KẾ HOẠCH HỌC TẬP CÁ NHÂN
-- Database Setup Script for MySQL
-- Chạy file này trong MySQL Workbench 8.0 CE
-- ================================================

-- Tạo database
CREATE DATABASE IF NOT EXISTS study_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE study_management;

-- ================================================
-- BẢNG USERS: Quản lý người dùng
-- ================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- BẢNG SUBJECTS: Quản lý môn học
-- ================================================
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20),
    color VARCHAR(7) DEFAULT '#3B82F6',
    credits INT DEFAULT 3,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- BẢNG TASKS: Quản lý công việc học tập
-- ================================================
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT,
    task_name VARCHAR(200) NOT NULL,
    description TEXT,
    deadline DATETIME,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_status (status),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- BẢNG PROGRESS_LOGS: Theo dõi tiến độ
-- ================================================
CREATE TABLE IF NOT EXISTS progress_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    notes TEXT,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- BẢNG SCHEDULE: Lịch học/Thời khóa biểu
-- ================================================
CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- BẢNG GOALS: Mục tiêu học tập dài hạn
-- ================================================
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_name VARCHAR(200) NOT NULL,
    description TEXT,
    target_date DATE,
    status ENUM('not_started', 'in_progress', 'completed', 'cancelled') DEFAULT 'not_started',
    progress_percentage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- DỮ LIỆU MẪU (Tùy chọn - có thể bỏ comment để test)
-- ================================================

-- Tạo user mẫu (password: "123456" đã được hash)
-- INSERT INTO users (username, email, password, full_name) VALUES
-- ('student1', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A');

-- Tạo môn học mẫu
-- INSERT INTO subjects (user_id, subject_name, subject_code, color, credits) VALUES
-- (1, 'Lập Trình Web', 'CS101', '#3B82F6', 3),
-- (1, 'Cơ Sở Dữ Liệu', 'CS102', '#10B981', 4),
-- (1, 'Toán Rời Rạc', 'MATH201', '#F59E0B', 3);

-- Tạo task mẫu
-- INSERT INTO tasks (user_id, subject_id, task_name, description, deadline, priority, status) VALUES
-- (1, 1, 'Hoàn thành bài tập HTML/CSS', 'Làm bài tập tuần 3', '2025-11-10 23:59:00', 'high', 'pending'),
-- (1, 2, 'Đọc chương 5 - Normalization', 'Đọc và tóm tắt kiến thức', '2025-11-08 18:00:00', 'medium', 'pending');

-- ================================================
-- HOÀN TẤT SETUP
-- ================================================
SELECT 'Database setup completed successfully!' AS message;
