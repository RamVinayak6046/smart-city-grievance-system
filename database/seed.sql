-- ============================================================
-- CivicFix Pro — Seed Data (Development Only)
-- Run AFTER schema.sql
-- ============================================================
USE `civicfix`;

-- ============================================================
-- WARDS (30 sample wards)
-- ============================================================
INSERT INTO `wards` (`ward_name`, `ward_code`, `zone`, `status`) VALUES
    ('Ward 1 - Rajendra Nagar',  'W01', 'North', 'active'),
    ('Ward 2 - Gandhi Chowk',    'W02', 'North', 'active'),
    ('Ward 3 - Lal Bagh',        'W03', 'Central', 'active'),
    ('Ward 4 - Shastri Nagar',   'W04', 'Central', 'active'),
    ('Ward 5 - Civil Lines',     'W05', 'Central', 'active'),
    ('Ward 6 - Nehru Park',      'W06', 'South', 'active'),
    ('Ward 7 - Sector 12',       'W07', 'South', 'active'),
    ('Ward 8 - MG Road',         'W08', 'East', 'active'),
    ('Ward 9 - Patel Nagar',     'W09', 'East', 'active'),
    ('Ward 10 - Subhash Nagar',  'W10', 'West', 'active'),
    ('Ward 11 - Indira Colony',  'W11', 'West', 'active'),
    ('Ward 12 - Vikas Vihar',    'W12', 'North', 'active');

-- ============================================================
-- DEPARTMENTS
-- ============================================================
INSERT INTO `departments` (`department_name`, `description`, `status`) VALUES
    ('Roads & Infrastructure',    'Maintenance of roads, bridges, and public infrastructure', 'active'),
    ('Sanitation & Waste',        'Garbage collection and waste management services', 'active'),
    ('Water Supply',              'Water distribution and leakage management', 'active'),
    ('Electrical & Lighting',     'Street lighting and electrical infrastructure', 'active'),
    ('Water & Drainage',          'Sewage systems and drainage maintenance', 'active'),
    ('Parks & Environment',       'Parks, green spaces, and tree management', 'active'),
    ('Public Health',             'Noise, pollution, and public health enforcement', 'active'),
    ('General Municipal Services','Miscellaneous civic services', 'active');

-- ============================================================
-- CATEGORIES (mapped to departments)
-- ============================================================
INSERT INTO `categories` (`category_name`, `description`, `department_id`, `default_priority`, `verification_required`, `status`) VALUES
    ('Roads and Potholes',    'Damaged roads, potholes, road cave-ins',          1, 'high',   1, 'active'),
    ('Garbage Collection',    'Uncollected garbage, overflowing bins',            2, 'medium', 1, 'active'),
    ('Water Leakage',         'Pipe burst, water wastage, supply issues',         3, 'high',   1, 'active'),
    ('Streetlights',          'Broken, flickering or non-functional streetlights',4, 'medium', 1, 'active'),
    ('Sewage and Drainage',   'Blocked drains, sewage overflow, flooding',        5, 'high',   1, 'active'),
    ('Parks and Fallen Trees','Fallen trees, damaged park infrastructure',        6, 'medium', 1, 'active'),
    ('Noise Pollution',       'Excessive noise from construction or events',      7, 'low',    0, 'active'),
    ('Other Issues',          'Any civic issue not covered by other categories',  8, 'low',    1, 'active');

-- ============================================================
-- SUPER ADMIN (password: Admin@123 — CHANGE BEFORE DEPLOYMENT)
-- ============================================================
INSERT INTO `staff` (`name`, `email`, `phone`, `password_hash`, `role_id`, `department_id`, `account_status`, `availability_status`, `max_active_workload`) VALUES
    ('Super Admin', 'admin@civicfix.local', '9999999999',
     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: Admin@123
     4, NULL, 'active', 'available', 99);

-- ============================================================
-- SAMPLE DEPARTMENT ADMINS (password: Admin@123)
-- ============================================================
INSERT INTO `staff` (`name`, `email`, `phone`, `password_hash`, `role_id`, `department_id`, `account_status`, `availability_status`, `max_active_workload`) VALUES
    ('Ravi Kumar',   'ravi.roads@civicfix.local',    '9876500001', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 'active', 'available', 99),
    ('Priya Sharma', 'priya.sanitation@civicfix.local','9876500002','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 2, 'active', 'available', 99),
    ('Arjun Singh',  'arjun.water@civicfix.local',   '9876500003', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 3, 'active', 'available', 99),
    ('Meera Nair',   'meera.electric@civicfix.local', '9876500004','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 4, 'active', 'available', 99);

-- ============================================================
-- SAMPLE WORK OFFICIALS (password: Admin@123)
-- ============================================================
INSERT INTO `staff` (`name`, `email`, `phone`, `password_hash`, `role_id`, `department_id`, `account_status`, `availability_status`, `max_active_workload`) VALUES
    ('Suresh Patil',   'suresh.roads@civicfix.local',    '9876501001','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 'active', 'available', 5),
    ('Kavita Rao',     'kavita.roads@civicfix.local',    '9876501002','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 'active', 'available', 5),
    ('Manoj Verma',    'manoj.sanit@civicfix.local',     '9876501003','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 2, 'active', 'available', 5),
    ('Sunita Joshi',   'sunita.water@civicfix.local',    '9876501004','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 3, 'active', 'available', 5),
    ('Ramesh Gupta',   'ramesh.electric@civicfix.local', '9876501005','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 4, 'active', 'available', 5),
    ('Deepa Menon',    'deepa.electric@civicfix.local',  '9876501006','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 4, 'active', 'available', 5);

-- ============================================================
-- STAFF WARD ASSIGNMENTS (officials serve multiple wards)
-- ============================================================
-- Suresh Patil (Roads) → Wards 1,2,3
INSERT INTO `staff_wards` (`staff_id`, `ward_id`) VALUES (6,1),(6,2),(6,3);
-- Kavita Rao (Roads) → Wards 4,5,6
INSERT INTO `staff_wards` (`staff_id`, `ward_id`) VALUES (7,4),(7,5),(7,6);
-- Manoj Verma (Sanitation) → Wards 1-6
INSERT INTO `staff_wards` (`staff_id`, `ward_id`) VALUES (8,1),(8,2),(8,3),(8,4),(8,5),(8,6);
-- Sunita Joshi (Water) → Wards 1-12
INSERT INTO `staff_wards` (`staff_id`, `ward_id`) VALUES (9,1),(9,2),(9,3),(9,4),(9,5),(9,6),(9,7),(9,8),(9,9),(9,10),(9,11),(9,12);
-- Ramesh Gupta (Electrical) → Wards 1-6
INSERT INTO `staff_wards` (`staff_id`, `ward_id`) VALUES (10,1),(10,2),(10,3),(10,4),(10,5),(10,6);
-- Deepa Menon (Electrical) → Wards 7-12
INSERT INTO `staff_wards` (`staff_id`, `ward_id`) VALUES (11,7),(11,8),(11,9),(11,10),(11,11),(11,12);
