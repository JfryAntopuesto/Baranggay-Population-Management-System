-- ============================================
-- Barangay Population Management System
-- Database Schema
-- ============================================
-- Database: baranggay_population_management
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS baranggay_population_management;
USE baranggay_population_management;

-- ============================================
-- Core User Tables
-- ============================================

-- User table
CREATE TABLE IF NOT EXISTS user (
    userID INT(11) AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    middlename VARCHAR(255),
    birthdate DATE NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    UNIQUE KEY idx_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Moderators table (Admin and Staff)
CREATE TABLE IF NOT EXISTS moderators (
    modID INT(11) AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff details table
CREATE TABLE IF NOT EXISTS staff_details (
    modID INT(11) PRIMARY KEY,
    firstname VARCHAR(255) NOT NULL,
    middlename VARCHAR(255),
    lastname VARCHAR(255) NOT NULL,
    birthdate DATE NOT NULL,
    age INT(11) NOT NULL,
    gender VARCHAR(50) NOT NULL,
    FOREIGN KEY (modID) REFERENCES moderators(modID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User profile pictures
CREATE TABLE IF NOT EXISTS user_pfp (
    userID INT(11) PRIMARY KEY,
    path VARCHAR(500) NOT NULL,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Purok and Household Tables
-- ============================================

-- Puroks table
CREATE TABLE IF NOT EXISTS puroks (
    purokID INT(11) AUTO_INCREMENT PRIMARY KEY,
    purok_name VARCHAR(255) NOT NULL UNIQUE,
    araw DATE NOT NULL,
    purok_pres VARCHAR(255) NOT NULL,
    purok_code VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Household table
CREATE TABLE IF NOT EXISTS household (
    householdID INT(11) AUTO_INCREMENT PRIMARY KEY,
    userID INT(11) NOT NULL,
    purokID INT(11) NOT NULL,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE,
    FOREIGN KEY (purokID) REFERENCES puroks(purokID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Household members table
CREATE TABLE IF NOT EXISTS members (
    memberID INT(11) AUTO_INCREMENT PRIMARY KEY,
    householdID INT(11) NOT NULL,
    firstname VARCHAR(255) NOT NULL,
    middlename VARCHAR(255),
    lastname VARCHAR(255) NOT NULL,
    age INT(11) NOT NULL,
    sex VARCHAR(50) NOT NULL,
    birthdate DATE NOT NULL,
    relationship VARCHAR(255) NOT NULL,
    FOREIGN KEY (householdID) REFERENCES household(householdID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Barangay profile table
CREATE TABLE IF NOT EXISTS baranggay_profile (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    baranggay_name VARCHAR(255) NOT NULL,
    baranggay_capital VARCHAR(255),
    city VARCHAR(255),
    araw_ng_barangay DATE,
    current_captain VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Request Tables
-- ============================================

-- Requests table (pending)
CREATE TABLE IF NOT EXISTS requests (
    requestID VARCHAR(50) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    userID INT(11) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    staff_comment TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Complaint Tables
-- ============================================

-- Complaints table (pending)
CREATE TABLE IF NOT EXISTS complaints (
    complaintID VARCHAR(50) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    userID INT(11) NOT NULL,
    complained_person VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    staff_comment TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Ticket Tables
-- ============================================

-- ============================================
-- Appointment Tables
-- ============================================

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    userID INT(11) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    purpose TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    staff_comment TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Announcement Tables
-- ============================================

-- Announcements table
CREATE TABLE IF NOT EXISTS announcement (
    annID INT(11) AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Notification Tables
-- ============================================

-- Unread notifications table
CREATE TABLE IF NOT EXISTS unread_notifications (
    notifID INT(11) AUTO_INCREMENT PRIMARY KEY,
    userID INT(11) NOT NULL,
    content TEXT NOT NULL,
    staff_comment TEXT,
    datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Read notifications table
CREATE TABLE IF NOT EXISTS read_notifications (
    notifID INT(11) PRIMARY KEY,
    userID INT(11) NOT NULL,
    content TEXT NOT NULL,
    staff_comment TEXT,
    datetime TIMESTAMP NOT NULL,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Indexes for Performance
-- ============================================

-- Indexes for user table
CREATE INDEX idx_user_username ON user(username);

-- Indexes for household table
CREATE INDEX idx_household_purok ON household(purokID);
CREATE INDEX idx_household_user ON household(userID);

-- Indexes for members table
CREATE INDEX idx_members_household ON members(householdID);

-- Indexes for appointments table
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_user ON appointments(userID);
CREATE INDEX idx_appointments_status ON appointments(status);

-- Indexes for requests table
CREATE INDEX idx_requests_user ON requests(userID);
CREATE INDEX idx_requests_status ON requests(status);

-- Indexes for complaints table
CREATE INDEX idx_complaints_user ON complaints(userID);
CREATE INDEX idx_complaints_status ON complaints(status);

-- Indexes for notifications
CREATE INDEX idx_unread_notifications_user ON unread_notifications(userID);
CREATE INDEX idx_read_notifications_user ON read_notifications(userID);

-- ============================================
-- End of Schema
-- ============================================

