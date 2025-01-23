-- Select the database to use 
USE npa_training;

-- Create the `participants` table
CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    personal_number VARCHAR(50) NOT NULL,
    designation VARCHAR(100),
    location VARCHAR(100),
    venue VARCHAR(255), -- Added Venue field
    training_description TEXT,
    start_date DATE NOT NULL,
    completion_date DATE NOT NULL,
    number_of_days INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    training_type VARCHAR(50) NOT NULL,
    total_cost_of_participation DECIMAL(19, 2),
    oracle_number VARCHAR(255), -- New field: Oracle Number
    consultant_name VARCHAR(255), -- New field: Name of Consultant
    consultation_amount DECIMAL(19, 2), -- New field: Amount for Consultation
    remark TEXT,
    INDEX idx_name (name), -- Index for faster searches by name
    INDEX idx_personal_number (personal_number), -- Index for personal number
    INDEX idx_location (location) -- Index for location
);

-- Create the users table if not already present
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Insert a default user with a pre-hashed password
INSERT INTO users (username, password)
VALUES ('admin', '$2b$12$sZTnTJRCiCDAVaSoinccH.jVwlRmOcepJEdhuZtKH0Jrdvi6uYl2m'); -- 'admin123'

-- Create the `events` table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL
);