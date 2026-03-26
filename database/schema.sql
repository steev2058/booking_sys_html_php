CREATE DATABASE IF NOT EXISTS booking_sys_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE booking_sys_php;

CREATE TABLE IF NOT EXISTS branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  name VARCHAR(255) NOT NULL,
  location VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS remittance_companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS business_days (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  day_name VARCHAR(50) NOT NULL,
  start_time VARCHAR(10) NOT NULL,
  end_time VARCHAR(10) NOT NULL,
  interval_minutes INT NOT NULL DEFAULT 30,
  active TINYINT(1) NOT NULL DEFAULT 1,
  INDEX(branch_id)
);
CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL UNIQUE,
  name VARCHAR(190) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transfer_number VARCHAR(80) NOT NULL,
  branch_id INT NOT NULL,
  company_id INT NOT NULL,
  day_name VARCHAR(50) NOT NULL,
  booking_date DATE NULL,
  slot_time VARCHAR(10) NOT NULL,
  slot_to VARCHAR(10) NULL,
  phone VARCHAR(30) NOT NULL,
  full_name VARCHAR(160) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'booked',
  created_at DATETIME NULL,
  INDEX(branch_id, booking_date, slot_time)
);
CREATE TABLE IF NOT EXISTS dashboard_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  employee_no VARCHAR(20) NULL UNIQUE,
  full_name VARCHAR(160) NULL,
  report_email VARCHAR(190) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL,
  branch_id INT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS otp_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(30) NOT NULL,
  full_name VARCHAR(160) NULL,
  code VARCHAR(10) NOT NULL,
  transfer_number VARCHAR(80) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL
);
CREATE TABLE IF NOT EXISTS otp_security (
  phone VARCHAR(30) PRIMARY KEY,
  send_count INT NOT NULL DEFAULT 0,
  window_start DATETIME NULL,
  verify_fail_count INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL
);

INSERT INTO branches (code,name,location,active) VALUES ('DAM01','فرع دمشق','دمشق',1)
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO remittance_companies (name,description,active) VALUES ('Western Union','',1),('MoneyGram','',1);
INSERT INTO business_days (branch_id,day_name,start_time,end_time,interval_minutes,active) VALUES
(1,'Saturday','10:00','14:00',30,1),(1,'Sunday','10:00','14:00',30,1),(1,'Monday','10:00','14:00',30,1),(1,'Tuesday','10:00','14:00',30,1),(1,'Wednesday','10:00','14:00',30,1),(1,'Thursday','10:00','14:00',30,1),(1,'Friday','10:00','14:00',30,0);
INSERT INTO dashboard_users (username,employee_no,full_name,report_email,password_hash,role,branch_id,active)
VALUES ('admin','BBSY0001','System Admin',NULL,'$2y$10$1d6QFv61t9aY7u2FhHFj8uD2Md2fAZO3vR3PDJrP6R0tA6ZzE2q4q','admin',NULL,1)
ON DUPLICATE KEY UPDATE username=VALUES(username);
