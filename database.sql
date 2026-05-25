-- University Student Management System Database
-- Run this SQL in phpMyAdmin or MySQL CLI before using the web application

CREATE DATABASE IF NOT EXISTS university_db CHARACTER SET utf8 COLLATE utf8_general_ci;

USE university_db;

-- Admin users table (for login)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Super Admin') NOT NULL DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (full_name, username, email, password, role) VALUES
('System Administrator', 'admin', 'admin@university.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin');

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL UNIQUE,
    duration VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO courses (course_code, course_name, duration) VALUES
('DIT', 'Diploma in Information Technology', '1 Year'),
('DBM', 'Diploma in Business Management', '1 Year'),
('DAC', 'Diploma in Accounting', '1 Year'),
('DEN', 'Diploma in English', '1 Year'),
('DMT', 'Diploma in Marketing', '1 Year'),
('HDIT', 'Higher Diploma in IT', '2 Years'),
('HDBM', 'Higher Diploma in Business Management', '2 Years')
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name), duration = VALUES(duration), is_active = 1;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30) NOT NULL UNIQUE,
    nic VARCHAR(20) NOT NULL UNIQUE COMMENT 'National Identity Card - Primary Key',
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    course VARCHAR(100) NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    enrolled_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_date DATE NOT NULL,
    reference_no VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late') NOT NULL DEFAULT 'Present',
    remarks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Sample student data
INSERT INTO students (student_id, nic, full_name, gender, address, phone, email, course, enrolled_date) VALUES
('STU20260001', '199512345678', 'Kasun Perera', 'Male', 'No. 45, Galle Road, Colombo 03', '0771234567', 'kasun.p@email.com', 'Diploma in Information Technology', '2024-01-15'),
('STU20260002', '199834567890', 'Nimasha Silva', 'Female', 'No. 12, Kandy Road, Kandy', '0712345678', 'nimasha.s@email.com', 'Diploma in Business Management', '2024-02-10'),
('STU20260003', '200012345670', 'Dilshan Fernando', 'Male', 'No. 78, Negombo Road, Negombo', '0751234567', 'dilshan.f@email.com', 'Diploma in Information Technology', '2024-01-20');
