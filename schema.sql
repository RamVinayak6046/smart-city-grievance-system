CREATE DATABASE IF NOT EXISTS smart_city_grievance;
USE smart_city_grievance;

CREATE TABLE Users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  mobile VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Admin (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE Complaints (
  complaint_id VARCHAR(32) PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  category ENUM(
    'Road Damage', 'Potholes', 'Garbage', 'Water Leakage', 'Drainage',
    'Street Light', 'Traffic Signal', 'Public Sanitation', 'Other'
  ) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  image_path VARCHAR(255),
  priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
  status ENUM('Pending', 'Assigned', 'In Progress', 'Resolved') DEFAULT 'Pending',
  assigned_to VARCHAR(120) DEFAULT 'Unassigned',
  admin_remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE INDEX idx_complaints_status ON Complaints(status);
CREATE INDEX idx_complaints_category ON Complaints(category);
CREATE INDEX idx_complaints_created_at ON Complaints(created_at);
