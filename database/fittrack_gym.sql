-- Create the project database.
CREATE DATABASE IF NOT EXISTS fittrack_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fittrack_gym;
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS roles(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(30) UNIQUE NOT NULL);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS users(
 id INT AUTO_INCREMENT PRIMARY KEY, role_id INT NOT NULL, full_name VARCHAR(100) NOT NULL,
 email VARCHAR(120) UNIQUE NOT NULL, username VARCHAR(50) UNIQUE NOT NULL,
 password VARCHAR(255) NOT NULL, phone VARCHAR(30), status ENUM('active','inactive') DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(role_id) REFERENCES roles(id)
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS trainers(
 id INT AUTO_INCREMENT PRIMARY KEY,user_id INT UNIQUE NOT NULL,specialization VARCHAR(100),
 experience_years INT DEFAULT 0,joining_date DATE,status ENUM('active','inactive') DEFAULT 'active',
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS members(
 id INT AUTO_INCREMENT PRIMARY KEY,user_id INT UNIQUE NOT NULL,trainer_id INT NULL,
 date_of_birth DATE,gender ENUM('male','female','other'),address VARCHAR(255),
 emergency_contact VARCHAR(100),emergency_phone VARCHAR(30),joined_date DATE NOT NULL,
 height DECIMAL(5,2),weight DECIMAL(6,2),status ENUM('active','inactive') DEFAULT 'active',
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(trainer_id) REFERENCES trainers(id) ON DELETE SET NULL
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS membership_plans(
 id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80) NOT NULL,duration_months INT NOT NULL,
 price DECIMAL(10,2) NOT NULL,description TEXT,status ENUM('active','inactive') DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS memberships(
 id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NOT NULL,plan_id INT NOT NULL,
 start_date DATE NOT NULL,end_date DATE NOT NULL,status ENUM('active','expired','cancelled') DEFAULT 'active',
 FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,
 FOREIGN KEY(plan_id) REFERENCES membership_plans(id)
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS payments(
 id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NOT NULL,membership_id INT NULL,amount DECIMAL(10,2) NOT NULL,
 payment_method ENUM('cash','card','bank','online') DEFAULT 'cash',payment_date DATE NOT NULL,
 reference_no VARCHAR(80),status ENUM('paid','pending','refunded') DEFAULT 'paid',
 FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,
 FOREIGN KEY(membership_id) REFERENCES memberships(id) ON DELETE SET NULL
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS attendance(
 id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NOT NULL,check_in DATETIME NOT NULL,check_out DATETIME NULL,
 status ENUM('present','late') DEFAULT 'present',FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE
);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS workout_plans(id INT AUTO_INCREMENT PRIMARY KEY,trainer_id INT NOT NULL,name VARCHAR(100) NOT NULL,goal VARCHAR(150),description TEXT,FOREIGN KEY(trainer_id) REFERENCES trainers(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS workout_exercises(id INT AUTO_INCREMENT PRIMARY KEY,workout_plan_id INT NOT NULL,exercise_name VARCHAR(100) NOT NULL,sets_count INT DEFAULT 3,reps VARCHAR(50) DEFAULT '10',rest_seconds INT DEFAULT 60,notes VARCHAR(255),FOREIGN KEY(workout_plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS member_workouts(id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NOT NULL,workout_plan_id INT NOT NULL,assigned_date DATE NOT NULL,status ENUM('assigned','completed','paused') DEFAULT 'assigned',FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,FOREIGN KEY(workout_plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS progress_records(id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NOT NULL,record_date DATE NOT NULL,weight DECIMAL(6,2) NOT NULL,height DECIMAL(6,2) NULL,body_fat DECIMAL(5,2) NULL,chest DECIMAL(6,2) NULL,waist DECIMAL(6,2) NULL,arms DECIMAL(6,2) NULL,notes VARCHAR(500),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS diet_plans(id INT AUTO_INCREMENT PRIMARY KEY,trainer_id INT NOT NULL,name VARCHAR(100) NOT NULL,goal VARCHAR(150),calories INT,description TEXT,FOREIGN KEY(trainer_id) REFERENCES trainers(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS member_diets(id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NOT NULL,diet_plan_id INT NOT NULL,assigned_date DATE NOT NULL,status ENUM('assigned','completed','paused') DEFAULT 'assigned',FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,FOREIGN KEY(diet_plan_id) REFERENCES diet_plans(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS classes(id INT AUTO_INCREMENT PRIMARY KEY,trainer_id INT NOT NULL,name VARCHAR(100) NOT NULL,description TEXT,class_date DATE NOT NULL,start_time TIME NOT NULL,end_time TIME NOT NULL,capacity INT DEFAULT 20,status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',FOREIGN KEY(trainer_id) REFERENCES trainers(id) ON DELETE CASCADE);
-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS class_bookings(id INT AUTO_INCREMENT PRIMARY KEY,class_id INT NOT NULL,member_id INT NOT NULL,booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,status ENUM('booked','attended','cancelled') DEFAULT 'booked',UNIQUE KEY unique_booking(class_id,member_id),FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE,FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE);

-- Create this table to store related project data.
CREATE TABLE IF NOT EXISTS contact_messages(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(120) NOT NULL,subject VARCHAR(200) NOT NULL,message TEXT NOT NULL,status ENUM('new','read','replied') DEFAULT 'new',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

INSERT IGNORE INTO roles(name) VALUES('admin'),('trainer'),('member');
