CREATE DATABASE interior_project_management;
USE interior_project_management;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'designer', 'site_manager', 'site_coordinator', 'site_supervisor') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects Table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100),
    client_phone VARCHAR(20),
    project_type ENUM('residential', 'commercial', 'industrial') NOT NULL,
    budget DECIMAL(15,2),
    start_date DATE,
    end_date DATE,
    status ENUM('planning', 'in_progress', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    progress INT DEFAULT 0,
    manager_id INT,
    designer_id INT,
    site_manager_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (designer_id) REFERENCES users(id),
    FOREIGN KEY (site_manager_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tasks Table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT,
    assigned_by INT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    start_date DATE,
    due_date DATE,
    completion_date DATE,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Project Materials Table
CREATE TABLE project_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    material_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(12,2),
    supplier VARCHAR(100),
    status ENUM('required', 'ordered', 'received', 'used') DEFAULT 'required',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Project Reports Table
CREATE TABLE project_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    report_type ENUM('daily', 'weekly', 'milestone', 'final') NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    attachments JSON,
    created_by INT NOT NULL,
    report_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert Default Admin User
INSERT INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@interior.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');




-- Add to your existing database
CREATE TABLE project_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    category ENUM('design', 'contract', 'invoice', 'report', 'photo', 'other') DEFAULT 'other',
    description TEXT,
    uploaded_by INT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Add indexes for better performance
CREATE INDEX idx_project_documents_project_id ON project_documents(project_id);
CREATE INDEX idx_project_documents_category ON project_documents(category);
CREATE INDEX idx_project_documents_uploaded_by ON project_documents(uploaded_by);



-- Add to your existing database
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_type ENUM('project', 'task', 'user', 'system') NULL,
    related_id INT NULL,
    action_url VARCHAR(500) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_email_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Add email preferences table
CREATE TABLE user_email_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_assigned BOOLEAN DEFAULT TRUE,
    task_completed BOOLEAN DEFAULT TRUE,
    project_created BOOLEAN DEFAULT TRUE,
    project_updated BOOLEAN DEFAULT TRUE,
    deadline_reminder BOOLEAN DEFAULT TRUE,
    system_notifications BOOLEAN DEFAULT TRUE,
    email_frequency ENUM('immediate', 'daily', 'weekly') DEFAULT 'immediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);



-- Calendar and scheduling tables
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    event_type ENUM('meeting', 'deadline', 'milestone', 'task', 'project_start', 'project_end') NOT NULL,
    related_type ENUM('project', 'task', 'user') NULL,
    related_id INT NULL,
    created_by INT NOT NULL,
    attendees JSON,
    location VARCHAR(255),
    is_all_day BOOLEAN DEFAULT FALSE,
    reminder_minutes INT DEFAULT 15,
    recurrence_rule VARCHAR(255) NULL,
    status ENUM('confirmed', 'tentative', 'cancelled') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_start_date (start_date),
    INDEX idx_created_by (created_by),
    INDEX idx_related (related_type, related_id)
);

CREATE TABLE user_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Sunday, 1=Monday, etc.
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_day_time (user_id, day_of_week, start_time)
);

CREATE TABLE meeting_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    location VARCHAR(255),
    equipment JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE room_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    event_id INT NOT NULL,
    booked_by INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES meeting_rooms(id),
    FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(id)
);

-- Designs table
CREATE TABLE designs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    designer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    design_type ENUM('floor_plan', '3d_render', 'elevation', 'section', 'detail') NOT NULL,
    status ENUM('draft', 'in_review', 'approved', 'rejected') DEFAULT 'draft',
    preview_image VARCHAR(500),
    design_files JSON,
    version VARCHAR(10) DEFAULT '1.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (designer_id) REFERENCES users(id)
);

-- Inventory items table
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    unit VARCHAR(50) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    minimum_stock INT DEFAULT 0,
    total_value DECIMAL(12,2) GENERATED ALWAYS AS (unit_price * stock_quantity) STORED,
    supplier_id INT,
    project_id INT,
    location VARCHAR(255),
    status ENUM('available', 'reserved', 'used') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

-- Project schedules table
CREATE TABLE project_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    task_id INT,
    activity_title VARCHAR(255) NOT NULL,
    description TEXT,
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    assigned_to INT,
    location VARCHAR(255),
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    total_hours DECIMAL(4,2),
    project_id INT,
    site_id INT,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    UNIQUE KEY unique_user_date (user_id, date)
);

-- Suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    category VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sites table for attendance
CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    project_id INT,
    manager_id INT,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
);



-- Password resets table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Add additional fields to users table
ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN company VARCHAR(255) NULL;




-- Interior Project Management System - Complete Database Schema
-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS interior_project_management;
CREATE DATABASE interior_project_management;
USE interior_project_management;

-- =============================================
-- CORE TABLES
-- =============================================

-- Users table (must be created first as it's referenced by many tables)
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL,
email VARCHAR(255) UNIQUE NOT NULL,
password VARCHAR(255) NOT NULL,
phone VARCHAR(20),
address TEXT,
profile_image VARCHAR(500),
role ENUM('admin', 'manager', 'designer', 'site_manager', 'site_coordinator', 'site_supervisor') NOT NULL,
status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
verification_token VARCHAR(255) NULL,
email_verified_at TIMESTAMP NULL,
last_activity TIMESTAMP NULL,
company VARCHAR(255) NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
INDEX idx_email (email),
INDEX idx_role (role),
INDEX idx_status (status)
);

-- Projects table
CREATE TABLE projects (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL,
description TEXT,
client_name VARCHAR(255) NOT NULL,
client_email VARCHAR(255),
client_phone VARCHAR(20),
client_address TEXT,
project_type ENUM('residential', 'commercial', 'office', 'retail', 'hospitality') NOT NULL,
budget DECIMAL(12,2),
start_date DATE,
end_date DATE,
status ENUM('planning', 'active', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'planning',
priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
manager_id INT,
designer_id INT,
site_manager_id INT,
site_coordinator_id INT,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (designer_id) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (site_manager_id) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (site_coordinator_id) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (created_by) REFERENCES users(id),
INDEX idx_status (status),
INDEX idx_project_type (project_type),
INDEX idx_created_by (created_by)
);

-- Tasks table
CREATE TABLE tasks (
id INT AUTO_INCREMENT PRIMARY KEY,
project_id INT NOT NULL,
title VARCHAR(255) NOT NULL,
description TEXT,
priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
assigned_to INT,
assigned_by INT NOT NULL,
due_date DATE,
estimated_hours DECIMAL(5,2),
actual_hours DECIMAL(5,2),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (assigned_by) REFERENCES users(id),
INDEX idx_project_id (project_id),
INDEX idx_assigned_to (assigned_to),
INDEX idx_status (status),
INDEX idx_due_date (due_date)
);

-- Suppliers table (needed before inventory_items)
CREATE TABLE suppliers (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL,
contact_person VARCHAR(255),
email VARCHAR(255),
phone VARCHAR(20),
address TEXT,
category VARCHAR(100),
status ENUM('active', 'inactive') DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
INDEX idx_status (status),
INDEX idx_category (category)
);

-- Sites table (needed before attendance and inventory)
CREATE TABLE sites (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL,
address TEXT NOT NULL,
project_id INT,
manager_id INT,
status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
INDEX idx_project_id (project_id),
INDEX idx_status (status)
);

-- =============================================
-- FEATURE MODULES TABLES
-- =============================================

-- Designs table
CREATE TABLE designs (
id INT AUTO_INCREMENT PRIMARY KEY,
project_id INT NOT NULL,
designer_id INT NOT NULL,
title VARCHAR(255) NOT NULL,
description TEXT,
design_type ENUM('floor_plan', '3d_render', 'elevation', 'section', 'detail') NOT NULL,
status ENUM('draft', 'in_review', 'approved', 'rejected') DEFAULT 'draft',
preview_image VARCHAR(500),
design_files JSON,
version VARCHAR(10) DEFAULT '1.0',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
FOREIGN KEY (designer_id) REFERENCES users(id),
INDEX idx_project_id (project_id),
INDEX idx_designer_id (designer_id),
INDEX idx_status (status)
);

-- Inventory items table (with corrected foreign keys)
CREATE TABLE inventory_items (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL,
sku VARCHAR(100) UNIQUE NOT NULL,
category VARCHAR(100) NOT NULL,
description TEXT,
unit VARCHAR(50) NOT NULL,
unit_price DECIMAL(10,2) NOT NULL,
stock_quantity INT NOT NULL DEFAULT 0,
minimum_stock INT DEFAULT 0,
total_value DECIMAL(12,2) GENERATED ALWAYS AS (unit_price \* stock_quantity) STORED,
supplier_id INT,
project_id INT,
location VARCHAR(255),
status ENUM('available', 'reserved', 'used') DEFAULT 'available',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
INDEX idx_sku (sku),
INDEX idx_category (category),
INDEX idx_status (status),
INDEX idx_stock_quantity (stock_quantity)
);

-- Project schedules table
CREATE TABLE project_schedules (
id INT AUTO_INCREMENT PRIMARY KEY,
project_id INT NOT NULL,
task_id INT,
activity_title VARCHAR(255) NOT NULL,
description TEXT,
scheduled_date DATE NOT NULL,
start_time TIME NOT NULL,
end_time TIME NOT NULL,
assigned_to INT,
location VARCHAR(255),
status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
notes TEXT,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (created_by) REFERENCES users(id),
INDEX idx_project_id (project_id),
INDEX idx_scheduled_date (scheduled_date),
INDEX idx_assigned_to (assigned_to)
);

-- Attendance table
CREATE TABLE attendance (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
date DATE NOT NULL,
check_in_time TIME,
check_out_time TIME,
total_hours DECIMAL(4,2),
project_id INT,
site_id INT,
status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
notes TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL,
UNIQUE KEY unique_user_date (user_id, date),
INDEX idx_date (date),
INDEX idx_status (status)
);

-- Reports table
CREATE TABLE reports (
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
description TEXT,
project_id INT,
report_type ENUM('progress', 'financial', 'quality', 'safety', 'completion') NOT NULL,
status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
content TEXT,
attachments JSON,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
FOREIGN KEY (created_by) REFERENCES users(id),
INDEX idx_project_id (project_id),
INDEX idx_report_type (report_type),
INDEX idx_status (status)
);

-- Project documents table
CREATE TABLE project_documents (
id INT AUTO_INCREMENT PRIMARY KEY,
project_id INT NOT NULL,
file_name VARCHAR(255) NOT NULL,
original_name VARCHAR(255) NOT NULL,
file_path VARCHAR(500) NOT NULL,
file_size BIGINT NOT NULL,
file_type VARCHAR(100) NOT NULL,
category ENUM('design', 'contract', 'invoice', 'report', 'photo', 'other') DEFAULT 'other',
description TEXT,
uploaded_by INT NOT NULL,
is_public BOOLEAN DEFAULT FALSE,
download_count INT DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
FOREIGN KEY (uploaded_by) REFERENCES users(id),
INDEX idx_project_id (project_id),
INDEX idx_category (category),
INDEX idx_uploaded_by (uploaded_by)
);

-- =============================================
-- SYSTEM TABLES
-- =============================================

-- Notifications table
CREATE TABLE notifications (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
type VARCHAR(50) NOT NULL,
title VARCHAR(255) NOT NULL,
message TEXT NOT NULL,
related_type ENUM('project', 'task', 'user', 'system') NULL,
related_id INT NULL,
action_url VARCHAR(500) NULL,
is_read BOOLEAN DEFAULT FALSE,
is_email_sent BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
read_at TIMESTAMP NULL,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_user_id (user_id),
INDEX idx_is_read (is_read),
INDEX idx_created_at (created_at)
);

-- Password resets table
CREATE TABLE password_resets (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
token VARCHAR(255) NOT NULL,
expires_at TIMESTAMP NOT NULL,
used BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
UNIQUE KEY unique_user (user_id),
INDEX idx_token (token),
INDEX idx_expires (expires_at)
);

-- Activity logs table
CREATE TABLE activity_logs (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
action VARCHAR(100) NOT NULL,
description TEXT,
ip_address VARCHAR(45),
user_agent TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_user_id (user_id),
INDEX idx_action (action),
INDEX idx_created_at (created_at)
);

-- User email preferences table
CREATE TABLE user_email_preferences (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
task_assigned BOOLEAN DEFAULT TRUE,
task_completed BOOLEAN DEFAULT TRUE,
project_created BOOLEAN DEFAULT TRUE,
project_updated BOOLEAN DEFAULT TRUE,
deadline_reminder BOOLEAN DEFAULT TRUE,
system_notifications BOOLEAN DEFAULT TRUE,
email_frequency ENUM('immediate', 'daily', 'weekly') DEFAULT 'immediate',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
UNIQUE KEY unique_user (user_id)
);

-- Calendar events table
CREATE TABLE calendar_events (
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
description TEXT,
start_date DATETIME NOT NULL,
end_date DATETIME NOT NULL,
event_type ENUM('meeting', 'deadline', 'milestone', 'task', 'project_start', 'project_end') NOT NULL,
related_type ENUM('project', 'task', 'user') NULL,
related_id INT NULL,
created_by INT NOT NULL,
attendees JSON,
location VARCHAR(255),
is_all_day BOOLEAN DEFAULT FALSE,
reminder_minutes INT DEFAULT 15,
recurrence_rule VARCHAR(255) NULL,
status ENUM('confirmed', 'tentative', 'cancelled') DEFAULT 'confirmed',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (created_by) REFERENCES users(id),
INDEX idx_start_date (start_date),
INDEX idx_created_by (created_by),
INDEX idx_event_type (event_type)
);

-- User availability table
CREATE TABLE user_availability (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
day_of_week TINYINT NOT NULL, -- 0=Sunday, 1=Monday, etc.
start_time TIME NOT NULL,
end_time TIME NOT NULL,
is_available BOOLEAN DEFAULT TRUE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
UNIQUE KEY unique_user_day_time (user_id, day_of_week, start_time)
);

-- Meeting rooms table
CREATE TABLE meeting_rooms (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
capacity INT NOT NULL,
location VARCHAR(255),
equipment JSON,
is_active BOOLEAN DEFAULT TRUE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room bookings table
CREATE TABLE room_bookings (
id INT AUTO_INCREMENT PRIMARY KEY,
room_id INT NOT NULL,
event_id INT NOT NULL,
booked_by INT NOT NULL,
start_time DATETIME NOT NULL,
end_time DATETIME NOT NULL,
status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (room_id) REFERENCES meeting_rooms(id),
FOREIGN KEY (event_id) REFERENCES calendar_events(id) ON DELETE CASCADE,
FOREIGN KEY (booked_by) REFERENCES users(id)
);

-- =============================================
-- SAMPLE DATA INSERTION
-- =============================================

-- Insert default admin user
INSERT INTO users (name, email, password, role, status, created_at) VALUES
('System Administrator', 'admin@interior-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW());

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_person, email, phone, address, category, status) VALUES
('BuildMart Supplies', 'John Smith', 'john@buildmart.com', '+91-9876543210', '123 Industrial Area, Mumbai', 'construction', 'active'),
('Design Materials Co.', 'Sarah Johnson', 'sarah@designmat.com', '+91-9876543211', '456 Design District, Delhi', 'interior', 'active'),
('Quality Hardware Ltd.', 'Mike Wilson', 'mike@qualityhw.com', '+91-9876543212', '789 Hardware Street, Bangalore', 'hardware', 'active');

-- Insert sample meeting rooms
INSERT INTO meeting_rooms (name, capacity, location, equipment) VALUES
('Conference Room A', 12, 'Ground Floor', '["Projector", "Whiteboard", "Video Conferencing"]'),
('Meeting Room B', 6, 'First Floor', '["TV Screen", "Whiteboard"]'),
('Board Room', 20, 'Second Floor', '["Smart Board", "Video Conferencing", "Audio System"]');

-- Create necessary directories (for file uploads)
-- Note: These directories need to be created manually or via deployment script
-- mkdir -p uploads/documents/
-- mkdir -p uploads/designs/
-- mkdir -p uploads/profiles/
-- mkdir -p uploads/reports/

-- =============================================
-- STORED PROCEDURES AND FUNCTIONS
-- =============================================

DELIMITER //

-- Function to calculate project progress
CREATE FUNCTION GetProjectProgress(project_id INT)
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
DECLARE total_tasks INT DEFAULT 0;
DECLARE completed_tasks INT DEFAULT 0;
DECLARE progress DECIMAL(5,2) DEFAULT 0;

    SELECT COUNT(*) INTO total_tasks
    FROM tasks WHERE project_id = project_id;

    SELECT COUNT(*) INTO completed_tasks
    FROM tasks WHERE project_id = project_id AND status = 'completed';

    IF total_tasks > 0 THEN
        SET progress = (completed_tasks / total_tasks) * 100;
    END IF;

    RETURN progress;

END //

-- Procedure to update project status based on tasks
CREATE PROCEDURE UpdateProjectStatus(IN project_id INT)
BEGIN
DECLARE task_count INT DEFAULT 0;
DECLARE completed_count INT DEFAULT 0;
DECLARE progress DECIMAL(5,2) DEFAULT 0;

    SELECT COUNT(*),
           COUNT(CASE WHEN status = 'completed' THEN 1 END)
    INTO task_count, completed_count
    FROM tasks WHERE project_id = project_id;

    IF task_count > 0 THEN
        SET progress = (completed_count / task_count) * 100;

        IF progress = 100 THEN
            UPDATE projects SET status = 'completed' WHERE id = project_id;
        ELSEIF progress > 0 THEN
            UPDATE projects SET status = 'in_progress' WHERE id = project_id;
        END IF;
    END IF;

END //

DELIMITER ;

-- =============================================
-- VIEWS FOR REPORTING
-- =============================================

-- Project overview view
CREATE VIEW project_overview AS
SELECT
p.id,
p.name,
p.client_name,
p.project_type,
p.status,
p.budget,
p.start_date,
p.end_date,
COUNT(t.id) as total_tasks,
COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / COUNT(t.id)) \* 100, 2) as progress_percentage,
m.name as manager_name,
d.name as designer_name
FROM projects p
LEFT JOIN tasks t ON p.id = t.project_id
LEFT JOIN users m ON p.manager_id = m.id
LEFT JOIN users d ON p.designer_id = d.id
GROUP BY p.id;

-- User workload view
CREATE VIEW user_workload AS
SELECT
u.id,
u.name,
u.role,
COUNT(CASE WHEN t.status != 'completed' THEN 1 END) as active_tasks,
COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks,
COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_projects
FROM users u
LEFT JOIN tasks t ON u.id = t.assigned_to
LEFT JOIN projects p ON (u.id = p.manager_id OR u.id = p.designer_id OR u.id = p.site_manager_id)
GROUP BY u.id;
