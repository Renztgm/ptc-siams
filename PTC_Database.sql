-- PTC Admission System Database
-- Creates tables for managing admissions, exams, and results

-- 1. ADMISSIONS TABLE - Store student application data
CREATE TABLE IF NOT EXISTS admissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  admission_id VARCHAR(20) UNIQUE NOT NULL,
  given_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100),
  full_name VARCHAR(255),
  email VARCHAR(100) UNIQUE NOT NULL,
  contact_number VARCHAR(20),
  address TEXT,
  program VARCHAR(200) NOT NULL,
  submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'pending', -- pending, admitted, rejected, registered
  admission_date DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Email tracking
  email_sent_date DATETIME,
  exam_link_sent BOOLEAN DEFAULT FALSE,
  exam_link_sent_date DATETIME,
  
  -- Additional info
  notes TEXT,
  
  INDEX idx_email (email),
  INDEX idx_program (program),
  INDEX idx_status (status),
  INDEX idx_admission_id (admission_id)
);

-- 2. EXAM SESSIONS TABLE - Track different exam batches/dates
CREATE TABLE IF NOT EXISTS exam_sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  session_name VARCHAR(100) NOT NULL,
  exam_date DATE NOT NULL,
  exam_start_time TIME NOT NULL,
  exam_end_time TIME NOT NULL,
  exam_format VARCHAR(50), -- Online, In-Person, Hybrid
  exam_location VARCHAR(255),
  exam_link VARCHAR(500),
  description TEXT,
  capacity INT, -- How many students can take this session
  status VARCHAR(50) DEFAULT 'scheduled', -- scheduled, ongoing, completed, cancelled
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_exam_date (exam_date),
  INDEX idx_status (status)
);

-- 3. EXAM REGISTRATIONS TABLE - Track which students register for which exam
CREATE TABLE IF NOT EXISTS exam_registrations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  admission_id INT NOT NULL,
  exam_session_id INT NOT NULL,
  registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  -- Attendance tracking
  attendance_status VARCHAR(50), -- attended, absent, late
  attendance_time DATETIME,
  
  -- Result tracking
  score DECIMAL(5, 2), -- Numeric score
  score_percentage DECIMAL(5, 2), -- Percentage score
  passing_score DECIMAL(5, 2),
  result VARCHAR(50), -- passed, failed, pending
  remarks TEXT,
  result_date DATETIME,
  
  -- Flow tracking
  status VARCHAR(50) DEFAULT 'registered', -- registered, completed, no-show, cancelled
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE CASCADE,
  FOREIGN KEY (exam_session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
  UNIQUE KEY unique_registration (admission_id, exam_session_id),
  INDEX idx_admission_id (admission_id),
  INDEX idx_exam_session_id (exam_session_id),
  INDEX idx_result (result),
  INDEX idx_status (status)
);

-- 4. EMAIL LOGS TABLE - Track all emails sent
CREATE TABLE IF NOT EXISTS email_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  recipient_email VARCHAR(100) NOT NULL,
  recipient_name VARCHAR(255),
  admission_id INT,
  email_type VARCHAR(50), -- confirmation, exam_link, result, reminder
  subject VARCHAR(255),
  sent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50), -- sent, failed, bounced
  error_message TEXT,
  
  FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE SET NULL,
  INDEX idx_sent_date (sent_date),
  INDEX idx_email_type (email_type),
  INDEX idx_status (status)
);

-- 5. ADMISSION STATISTICS TABLE - Store aggregated stats for quick access
CREATE TABLE IF NOT EXISTS admission_stats (
  id INT PRIMARY KEY AUTO_INCREMENT,
  stat_date DATE DEFAULT CURDATE(),
  program VARCHAR(200),
  total_applications INT DEFAULT 0,
  admitted INT DEFAULT 0,
  rejected INT DEFAULT 0,
  registered_for_exam INT DEFAULT 0,
  exam_completed INT DEFAULT 0,
  passed INT DEFAULT 0,
  failed INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_stat (stat_date, program),
  INDEX idx_program (program)
);

-- 6. SYSTEM LOGS TABLE - Track admin actions
CREATE TABLE IF NOT EXISTS system_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  action VARCHAR(100) NOT NULL, -- email_sent, exam_scheduled, result_entered, etc.
  actor VARCHAR(100), -- Admin username
  action_details TEXT,
  ip_address VARCHAR(50),
  log_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_action (action),
  INDEX idx_log_date (log_date)
);

-- 7. PROGRAMS TABLE - List of available programs
CREATE TABLE IF NOT EXISTS programs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  program_name VARCHAR(200) UNIQUE NOT NULL,
  program_code VARCHAR(20),
  description TEXT,
  total_slots INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_program_name (program_name)
);

-- Sample data: Insert default programs
INSERT IGNORE INTO programs (program_name, program_code) VALUES
('BS Information Technology', 'BSIT'),
('BS Business Administration', 'BSBA'),
('BS Hospitality Management', 'BSHM'),
('BS Nursing', 'BSN'),
('BS Criminology', 'BSCRIM'),
('Associate in Hotel and Restaurant Management', 'AHRM'),
('Associate in Office Administration', 'AOA'),
('Associate in Education', 'AED');

-- NOTE: Views are not supported on InfinityFree (permission restricted)
-- Instead, use these queries directly in your PHP code:

-- Query 1: Get latest exam session (replaces v_latest_exam_session)
-- SELECT * FROM exam_sessions 
-- WHERE exam_date >= CURDATE()
-- ORDER BY exam_date ASC
-- LIMIT 1;

-- Query 2: Exam takers summary (replaces v_exam_takers_summary)
-- SELECT 
--   es.session_name,
--   es.exam_date,
--   COUNT(er.id) as total_registered,
--   SUM(CASE WHEN er.attended = 1 THEN 1 ELSE 0 END) as attended,
--   SUM(CASE WHEN er.pass_fail = 'pass' THEN 1 ELSE 0 END) as passed,
--   SUM(CASE WHEN er.pass_fail = 'fail' THEN 1 ELSE 0 END) as failed
-- FROM exam_sessions es
-- LEFT JOIN exam_registrations er ON es.id = er.exam_id
-- GROUP BY es.id, es.session_name, es.exam_date;

-- Query 3: Program-wise admission stats (replaces v_program_stats)
-- SELECT 
--   p.program_name,
--   COUNT(a.id) as total_applications,
--   SUM(CASE WHEN a.status = 'admitted' THEN 1 ELSE 0 END) as admitted,
--   SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
--   COUNT(DISTINCT er.id) as registered_for_exam
-- FROM programs p
-- LEFT JOIN admissions a ON p.program_name = a.program
-- LEFT JOIN exam_registrations er ON a.id = er.admission_id
-- GROUP BY p.id, p.program_name;
