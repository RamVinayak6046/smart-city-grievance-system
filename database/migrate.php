<?php
/**
 * Database Migration Script
 */
require_once __DIR__ . '/../config/database.php';

$pdo = db();

echo "Running migrations...\n";

// 1. Roles
$pdo->exec("CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_name VARCHAR(30) UNIQUE NOT NULL
);");

$pdo->exec("INSERT OR IGNORE INTO roles (id, role_name) VALUES 
    (1, 'citizen'),
    (2, 'work_official'),
    (3, 'department_admin'),
    (4, 'super_admin');");

// 2. Wards
$pdo->exec("CREATE TABLE IF NOT EXISTS wards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ward_name VARCHAR(100) NOT NULL,
    ward_code VARCHAR(20) UNIQUE NOT NULL,
    zone VARCHAR(60),
    status VARCHAR(20) DEFAULT 'active'
);");

// 3. Departments
$pdo->exec("CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    department_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// 4. Users (Citizens)
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    ward_id INTEGER,
    address TEXT,
    account_status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// 5. Staff
$pdo->exec("CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role_id INTEGER NOT NULL,
    department_id INTEGER,
    account_status VARCHAR(20) DEFAULT 'active',
    availability_status VARCHAR(20) DEFAULT 'available',
    max_active_workload INTEGER DEFAULT 5,
    last_assigned_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// 6. Staff Wards
$pdo->exec("CREATE TABLE IF NOT EXISTS staff_wards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    ward_id INTEGER NOT NULL
);");

// 7. Categories
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    department_id INTEGER,
    default_priority VARCHAR(20) DEFAULT 'medium',
    verification_required INTEGER DEFAULT 1,
    status VARCHAR(20) DEFAULT 'active'
);");

// 8. Complaints
$pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_code VARCHAR(25) UNIQUE NOT NULL,
    citizen_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    department_id INTEGER,
    ward_id INTEGER,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    address VARCHAR(500),
    latitude REAL,
    longitude REAL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(30) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME
);");

// 9. Complaint Assignments
$pdo->exec("CREATE TABLE IF NOT EXISTS complaint_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_id INTEGER NOT NULL,
    official_id INTEGER NOT NULL,
    assigned_by INTEGER,
    assignment_type VARCHAR(20) DEFAULT 'automatic',
    assignment_status VARCHAR(20) DEFAULT 'active',
    assignment_reason TEXT,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME,
    completed_at DATETIME,
    ended_at DATETIME
);");

// 10. Complaint Attachments
$pdo->exec("CREATE TABLE IF NOT EXISTS complaint_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_id INTEGER NOT NULL,
    uploaded_by INTEGER NOT NULL,
    uploaded_by_role VARCHAR(30) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INTEGER NOT NULL,
    attachment_type VARCHAR(30) DEFAULT 'complaint_photo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// 11. Complaint History
$pdo->exec("CREATE TABLE IF NOT EXISTS complaint_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_id INTEGER NOT NULL,
    actor_id INTEGER NOT NULL,
    actor_role VARCHAR(30) NOT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30) NOT NULL,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// 12. Notifications
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipient_type VARCHAR(30) NOT NULL,
    recipient_id INTEGER NOT NULL,
    complaint_id INTEGER,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME
);");

// 13. Audit Logs
$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_id INTEGER,
    actor_role VARCHAR(30) NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// 14. System Settings
$pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_by INTEGER,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

// Seed basic data
$pdo->exec("INSERT OR IGNORE INTO system_settings (setting_key, setting_value) VALUES
    ('complaint_id_prefix',      'CFX'),
    ('max_upload_size_mb',       '5'),
    ('max_photos_per_complaint', '3'),
    ('default_max_workload',     '5'),
    ('app_name',                 'CivicFix Pro'),
    ('app_timezone',             'Asia/Kolkata'),
    ('sla_low_days',             '7'),
    ('sla_medium_days',          '5'),
    ('sla_high_days',            '3'),
    ('sla_critical_days',        '1');");

// Wards
$pdo->exec("INSERT OR IGNORE INTO wards (id, ward_name, ward_code, zone, status) VALUES
    (1, 'Ward 1 - Rajendra Nagar',  'W01', 'North', 'active'),
    (2, 'Ward 2 - Gandhi Chowk',    'W02', 'North', 'active'),
    (3, 'Ward 3 - Lal Bagh',        'W03', 'Central', 'active'),
    (4, 'Ward 4 - Shastri Nagar',   'W04', 'Central', 'active'),
    (5, 'Ward 5 - Civil Lines',     'W05', 'Central', 'active'),
    (6, 'Ward 6 - Nehru Park',      'W06', 'South', 'active'),
    (7, 'Ward 7 - Sector 12',       'W07', 'South', 'active'),
    (8, 'Ward 8 - MG Road',         'W08', 'East', 'active'),
    (9, 'Ward 9 - Patel Nagar',     'W09', 'East', 'active'),
    (10, 'Ward 10 - Subhash Nagar', 'W10', 'West', 'active'),
    (11, 'Ward 11 - Indira Colony', 'W11', 'West', 'active'),
    (12, 'Ward 12 - Vikas Vihar',   'W12', 'North', 'active');");

// Departments
$pdo->exec("INSERT OR IGNORE INTO departments (id, department_name, description, status) VALUES
    (1, 'Roads & Infrastructure',    'Maintenance of roads, bridges, and public infrastructure', 'active'),
    (2, 'Sanitation & Waste',        'Garbage collection and waste management services', 'active'),
    (3, 'Water Supply',              'Water distribution and leakage management', 'active'),
    (4, 'Electrical & Lighting',     'Street lighting and electrical infrastructure', 'active'),
    (5, 'Water & Drainage',          'Sewage systems and drainage maintenance', 'active'),
    (6, 'Parks & Environment',       'Parks, green spaces, and tree management', 'active'),
    (7, 'Public Health',             'Noise, pollution, and public health enforcement', 'active'),
    (8, 'General Municipal Services','Miscellaneous civic services', 'active');");

// Categories
$pdo->exec("INSERT OR IGNORE INTO categories (id, category_name, description, department_id, default_priority, verification_required, status) VALUES
    (1, 'Roads and Potholes',    'Damaged roads, potholes, road cave-ins',          1, 'high',   1, 'active'),
    (2, 'Garbage Collection',    'Uncollected garbage, overflowing bins',            2, 'medium', 1, 'active'),
    (3, 'Water Leakage',         'Pipe burst, water wastage, supply issues',         3, 'high',   1, 'active'),
    (4, 'Streetlights',          'Broken, flickering or non-functional streetlights',4, 'medium', 1, 'active'),
    (5, 'Sewage and Drainage',   'Blocked drains, sewage overflow, flooding',        5, 'high',   1, 'active'),
    (6, 'Parks and Fallen Trees','Fallen trees, damaged park infrastructure',        6, 'medium', 1, 'active'),
    (7, 'Noise Pollution',       'Excessive noise from construction or events',      7, 'low',    0, 'active'),
    (8, 'Other Issues',          'Any civic issue not covered by other categories',  8, 'low',    1, 'active');");

// Staff (Password: Admin@123)
$passHash = password_hash('Admin@123', PASSWORD_BCRYPT);

$pdo->exec("INSERT OR IGNORE INTO staff (id, name, email, phone, password_hash, role_id, department_id, account_status, availability_status, max_active_workload) VALUES
    (1, 'Super Admin', 'admin@civicfix.local', '9999999999', '$passHash', 4, NULL, 'active', 'available', 99),
    (2, 'Ravi Kumar',   'ravi.roads@civicfix.local',    '9876500001', '$passHash', 3, 1, 'active', 'available', 99),
    (3, 'Priya Sharma', 'priya.sanitation@civicfix.local','9876500002','$passHash', 3, 2, 'active', 'available', 99),
    (4, 'Arjun Singh',  'arjun.water@civicfix.local',   '9876500003', '$passHash', 3, 3, 'active', 'available', 99),
    (5, 'Meera Nair',   'meera.electric@civicfix.local', '9876500004','$passHash', 3, 4, 'active', 'available', 99),
    (6, 'Suresh Patil',   'suresh.roads@civicfix.local',    '9876501001','$passHash', 2, 1, 'active', 'available', 5),
    (7, 'Kavita Rao',     'kavita.roads@civicfix.local',    '9876501002','$passHash', 2, 1, 'active', 'available', 5),
    (8, 'Manoj Verma',    'manoj.sanit@civicfix.local',     '9876501003','$passHash', 2, 2, 'active', 'available', 5),
    (9, 'Sunita Joshi',   'sunita.water@civicfix.local',    '9876501004','$passHash', 2, 3, 'active', 'available', 5),
    (10, 'Ramesh Gupta',   'ramesh.electric@civicfix.local', '9876501005','$passHash', 2, 4, 'active', 'available', 5),
    (11, 'Deepa Menon',    'deepa.electric@civicfix.local',  '9876501006','$passHash', 2, 4, 'active', 'available', 5);");

// Staff Wards
$pdo->exec("INSERT OR IGNORE INTO staff_wards (staff_id, ward_id) VALUES
    (6,1),(6,2),(6,3),
    (7,4),(7,5),(7,6),
    (8,1),(8,2),(8,3),(8,4),(8,5),(8,6),
    (9,1),(9,2),(9,3),(9,4),(9,5),(9,6),(9,7),(9,8),(9,9),(9,10),(9,11),(9,12),
    (10,1),(10,2),(10,3),(10,4),(10,5),(10,6),
    (11,7),(11,8),(11,9),(11,10),(11,11),(11,12);");

echo "Migrations and seeds complete.\n";
