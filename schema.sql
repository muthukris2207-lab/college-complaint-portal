CREATE DATABASE IF NOT EXISTS smart_complaint_db;
USE smart_complaint_db;

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table (Staff, HOD, Principal)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('staff', 'hod', 'principal') NOT NULL,
    department_id INT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Complaints Table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20) NOT NULL UNIQUE, -- CMP-YYYY-XXXXX
    text_content TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    student_name VARCHAR(100) NULL,
    student_email VARCHAR(100) NULL,
    student_roll VARCHAR(50) NULL,
    category VARCHAR(50) NOT NULL, -- Academic, Infrastructure, Hostel, Transport, Harassment
    priority ENUM('High', 'Medium', 'Low') NOT NULL,
    summary TEXT NOT NULL,
    status ENUM('Submitted', 'In Progress', 'Escalated', 'Resolved') DEFAULT 'Submitted',
    current_handler_role ENUM('staff', 'hod', 'principal') DEFAULT 'staff',
    department_id INT NOT NULL,
    resolution_notes TEXT NULL,
    escalated_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Escalation Log Table
CREATE TABLE IF NOT EXISTS escalation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20) NOT NULL,
    from_role VARCHAR(20) NOT NULL,
    to_role VARCHAR(20) NOT NULL,
    escalated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Departments
INSERT INTO departments (id, name, code) VALUES
(1, 'Academic', 'ACAD'),
(2, 'Infrastructure', 'INFRA'),
(3, 'Hostel', 'HOSTEL'),
(4, 'Transport', 'TRANS'),
(5, 'Harassment', 'HARASS')
ON DUPLICATE KEY UPDATE name=VALUES(name), code=VALUES(code);

-- Seed Users
INSERT INTO users (id, username, password_hash, role, department_id, email) VALUES
(1, 'principal', '$2y$10$jfgdqRs5mV/dFBoiV8McXeg.Ku0PvhgP973c79NnV1X6yBhZ.Djdi', 'principal', NULL, 'principal@college.edu'),
(2, 'staff_academic', '$2y$10$hiIQUduBOoBLrrtBmGK9O.386mu8xbGOT1SkL3KqBHw0NXdm7Ke8e', 'staff', 1, 'staff.acad@college.edu'),
(3, 'hod_academic', '$2y$10$cGBK/GnqxBMkEiJ87W4K9.opBg8ar44NsxmRLOOZJB2u9jjGzRhsS', 'hod', 1, 'hod.acad@college.edu'),
(4, 'staff_infra', '$2y$10$zYL1e45luMqbgCt0oBYoNeBhseTzrs0d8TOXcHT6QoaovM9vKrlv6', 'staff', 2, 'staff.infra@college.edu'),
(5, 'hod_infra', '$2y$10$fOztOci8q9mBxe2GLWJk3.6jERgTX8thdjOE.AMpe.fe/g25M0vK.', 'hod', 2, 'hod.infra@college.edu'),
(6, 'staff_hostel', '$2y$10$XQ/XWLpPSuFximbOvRYm9OnlURTnbUSGfvrUdYb7S/53ekvPGsBre', 'staff', 3, 'staff.hostel@college.edu'),
(7, 'hod_hostel', '$2y$10$cTtFn6slIjPjw2nfqvvrrOHv4yJX5GXA7L687jE.veIwy0TO.N1sm', 'hod', 3, 'hod.hostel@college.edu'),
(8, 'staff_trans', '$2y$10$9G3lj4lmGUVrlZA8owQL2OIZaE9hFanAQgyyOEuyv588Q8DitL4om', 'staff', 4, 'staff.trans@college.edu'),
(9, 'hod_trans', '$2y$10$0u77QJiNDrqVyatEukkfieCzFm6PhDElgwgDoPeiJsJSCt5s4w.te', 'hod', 4, 'hod.trans@college.edu'),
(10, 'staff_harass', '$2y$10$DigGi8D5s8z7jMUDLS3AB.FDHJzWRBteCJThKI2avXvvtVLgrMJjq', 'staff', 5, 'staff.harass@college.edu'),
(11, 'hod_harass', '$2y$10$qvB3MNUeN1zsCl4FsgI7luwgpULK4YB4BUIWFAaCAid8xXVZXZbKy', 'hod', 5, 'hod.harass@college.edu')
ON DUPLICATE KEY UPDATE username=VALUES(username), password_hash=VALUES(password_hash), role=VALUES(role), department_id=VALUES(department_id), email=VALUES(email);
