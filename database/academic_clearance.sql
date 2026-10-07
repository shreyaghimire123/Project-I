-- Academic Clearance System Database
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
CREATE DATABASE IF NOT EXISTS academic_clearance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academic_clearance;
-- Users table (login & roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM(
        'student',
        'library',
        'dormitory',
        'bursary',
        'cafeteria',
        'sports',
        'admin'
    ) NOT NULL DEFAULT 'student',
    department VARCHAR(50) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    first_login TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
-- Students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_number VARCHAR(50) NOT NULL UNIQUE,
    program VARCHAR(100) NOT NULL,
    batch VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;
-- Departments
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE = InnoDB;
INSERT INTO departments (department_name)
VALUES ('Library'),
('Dormitory'),
('Bursary'),
('Cafeteria'),
('Sports');
-- Clearance Requests
CREATE TABLE clearance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    clearance_type ENUM(
        'Graduation',
        'Transfer',
        'Semester Completion',
        'Other'
    ) NOT NULL,
    status ENUM(
        'in_progress',
        'action_required',
        'ready_for_final',
        'completed',
        'rejected'
    ) NOT NULL DEFAULT 'in_progress',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE = InnoDB;
-- Department Approvals
CREATE TABLE department_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clearance_request_id INT NOT NULL,
    department_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (clearance_request_id) REFERENCES clearance_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE
    SET NULL,
        UNIQUE KEY unique_dept_request (clearance_request_id, department_id)
) ENGINE = InnoDB;
-- Outstanding Dues
CREATE TABLE dues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    department_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('pending', 'cleared') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE = InnoDB;
-- Certificates
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clearance_request_id INT NOT NULL UNIQUE,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    verification_code VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    status ENUM('valid', 'revoked') NOT NULL DEFAULT 'valid',
    FOREIGN KEY (clearance_request_id) REFERENCES clearance_requests(id) ON DELETE CASCADE
) ENGINE = InnoDB;
-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;
-- Audit Logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE
    SET NULL
) ENGINE = InnoDB;
-- Sample Data for Demo
-- Password for all demo accounts: password123
-- Hash generated with password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (
        name,
        email,
        password,
        role,
        department,
        status,
        first_login
    )
VALUES (
        'Shreya Ghimire',
        'shreya@student.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'student',
        NULL,
        'active',
        0
    ),
    (
        'Library Staff',
        'library@college.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'library',
        'Library',
        'active',
        0
    ),
    (
        'Dormitory Staff',
        'dormitory@college.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'dormitory',
        'Dormitory',
        'active',
        0
    ),
    (
        'Bursary Staff',
        'bursary@college.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'bursary',
        'Bursary',
        'active',
        0
    ),
    (
        'Cafeteria Staff',
        'cafeteria@college.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'cafeteria',
        'Cafeteria',
        'active',
        0
    ),
    (
        'Sports Staff',
        'sports@college.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'sports',
        'Sports',
        'active',
        0
    ),
    (
        'Registrar Admin',
        'admin@college.edu',
        '$2y$10$pU25qAr447fLzO7hnNfqXekGLemxE70DnFntl6tKEZf5AfKGyeRWi',
        'admin',
        NULL,
        'active',
        0
    );
INSERT INTO students (user_id, student_number, program, batch)
VALUES (1, '30', 'BCA', '2024');
-- Note: The password hash above is a placeholder. In real use generate with:
-- php -r "echo password_hash('password123', PASSWORD_DEFAULT);"
