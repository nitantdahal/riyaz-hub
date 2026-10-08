CREATE DATABASE IF NOT EXISTS riyaz_hub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE riyaz_hub;


-- Table: instruments

CREATE TABLE instruments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  icon VARCHAR(10) DEFAULT '🎵',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: users  (admin / instructor / student)

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',
  instrument_id INT DEFAULT NULL,
  instructor_id INT DEFAULT NULL,          -- only used when role = student
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  avatar_color VARCHAR(20) DEFAULT '#7c3aed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_instrument FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_instructor FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: practice_sessions

CREATE TABLE practice_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  instrument_id INT DEFAULT NULL,
  session_date DATE NOT NULL,
  duration_minutes INT NOT NULL,
  focus_area VARCHAR(150) DEFAULT NULL,
  notes TEXT,
  mood ENUM('great','good','okay','tough') DEFAULT 'good',
  video_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sessions_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sessions_instrument FOREIGN KEY (instrument_id) REFERENCES instruments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: goals

CREATE TABLE goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  target_minutes INT NOT NULL DEFAULT 0,
  deadline DATE DEFAULT NULL,
  status ENUM('in_progress','completed','missed') NOT NULL DEFAULT 'in_progress',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_goals_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: streaks (one row per student)

CREATE TABLE streaks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL UNIQUE,
  current_streak INT NOT NULL DEFAULT 0,
  longest_streak INT NOT NULL DEFAULT 0,
  last_practice_date DATE DEFAULT NULL,
  CONSTRAINT fk_streaks_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: feedback (instructor -> student)
CREATE TABLE feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  instructor_id INT NOT NULL,
  student_id INT NOT NULL,
  session_id INT DEFAULT NULL,
  message TEXT NOT NULL,
  rating TINYINT DEFAULT NULL COMMENT '1-5 star rating given alongside feedback, used in leaderboard scoring',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_feedback_instructor FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_feedback_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_feedback_session FOREIGN KEY (session_id) REFERENCES practice_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: assignments (instructor -> student)
CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,

  instructor_id INT NOT NULL,
  student_id INT NOT NULL,

  title VARCHAR(150) NOT NULL,
  description TEXT,
  due_date DATE DEFAULT NULL,

  -- Assignment progress
  status ENUM(
    'pending',
    'submitted',
    'accepted',
    'rejected'
  ) NOT NULL DEFAULT 'pending',

  -- Student action
  completed_at TIMESTAMP NULL DEFAULT NULL 
  COMMENT 'When student submitted completion',

  -- Instructor review
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  instructor_feedback TEXT DEFAULT NULL,

  -- Points
  points INT DEFAULT 0,
  points_awarded BOOLEAN DEFAULT FALSE,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_assign_instructor 
  FOREIGN KEY (instructor_id) REFERENCES users(id) 
  ON DELETE CASCADE,

  CONSTRAINT fk_assign_student 
  FOREIGN KEY (student_id) REFERENCES users(id) 
  ON DELETE CASCADE

) ENGINE=InnoDB;

-- Table: achievements
CREATE TABLE achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  icon VARCHAR(10) DEFAULT '🏆',
  earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ach_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: otp_verifications
-- Holds a pending registration + one-time code until the student
-- verifies their email address. Row is deleted once verified.

CREATE TABLE otp_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  otp_code VARCHAR(10) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  instrument_id INT DEFAULT NULL,
  instructor_id INT DEFAULT NULL,
  attempts INT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_email (email)
) ENGINE=InnoDB;

-- Table: password_resets
-- Holds a pending "forgot password" OTP until the user verifies it.
CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  otp_code VARCHAR(10) NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reset_email (email)
) ENGINE=InnoDB;

-- SEED DATA

INSERT INTO instruments (name, description, icon) VALUES
('Piano', 'Acoustic & digital keyboard practice', '🎹'),
('Guitar', 'Acoustic and electric guitar', '🎸'),
('Violin', 'Classical string instrument', '🎻'),
('Drums', 'Percussion and rhythm training', '🥁'),
('Flute', 'Woodwind instrument', '🎼'),
('Vocals', 'Voice training and singing', '🎤');

-- Default password for ALL seed accounts below is:  Password123
-- (hash generated with PHP password_hash, bcrypt)
INSERT INTO users (full_name, email, password, role, instrument_id, instructor_id, avatar_color) VALUES
('Alex Morgan (Admin)',      'admin@musictrack.com',      '$2b$10$lOVbkUtfh6xUAcHCjb0o1uGwJ4sWXD6dm/XK95brqAPtaJtOF3JUG', 'admin',      NULL, NULL, '#f59e0b'),
('Sarah Bennett',            'sarah.instructor@musictrack.com', '$2b$10$lOVbkUtfh6xUAcHCjb0o1uGwJ4sWXD6dm/XK95brqAPtaJtOF3JUG', 'instructor', 1,    NULL, '#06b6d4'),
('Daniel Reyes',             'daniel.instructor@musictrack.com', '$2b$10$lOVbkUtfh6xUAcHCjb0o1uGwJ4sWXD6dm/XK95brqAPtaJtOF3JUG', 'instructor', 2,    NULL, '#ec4899'),
('Emma Clarke',              'emma.student@musictrack.com', '$2b$10$lOVbkUtfh6xUAcHCjb0o1uGwJ4sWXD6dm/XK95brqAPtaJtOF3JUG', 'student',    1,    2, '#8b5cf6'),
('Liam Turner',              'liam.student@musictrack.com', '$2b$10$lOVbkUtfh6xUAcHCjb0o1uGwJ4sWXD6dm/XK95brqAPtaJtOF3JUG', 'student',    2,    3, '#10b981');

INSERT INTO streaks (student_id, current_streak, longest_streak, last_practice_date) VALUES
(4, 3, 7, CURDATE()),
(5, 1, 4, CURDATE());

INSERT INTO practice_sessions (student_id, instrument_id, session_date, duration_minutes, focus_area, notes, mood) VALUES
(4, 1, CURDATE() - INTERVAL 2 DAY, 45, 'Scales & Arpeggios', 'Worked on C major and G major scales.', 'good'),
(4, 1, CURDATE() - INTERVAL 1 DAY, 30, 'Sight Reading', 'Practiced grade 3 sight reading pieces.', 'great'),
(4, 1, CURDATE(), 40, 'Chopin Nocturne', 'Slow practice of the opening phrase.', 'okay'),
(5, 2, CURDATE() - INTERVAL 3 DAY, 60, 'Chord Transitions', 'G, C, D, Em transitions.', 'good'),
(5, 2, CURDATE(), 35, 'Fingerpicking', 'Travis picking pattern practice.', 'great');

INSERT INTO goals (student_id, title, target_minutes, deadline, status) VALUES
(4, 'Practice 300 minutes this week', 300, CURDATE() + INTERVAL 4 DAY, 'in_progress'),
(5, 'Master barre chords', 240, CURDATE() + INTERVAL 10 DAY, 'in_progress');

INSERT INTO feedback (instructor_id, student_id, session_id, message, rating) VALUES
(2, 4, 2, 'Great improvement on sight reading speed this week — keep it up!', 5),
(3, 5, 4, 'Nice work on chord transitions. Focus on keeping strum rhythm steady next time.', 4);

INSERT INTO assignments (instructor_id, student_id, title, description, due_date, status, completed_at) VALUES
(2, 4, 'Learn Chopin Nocturne Op.9 No.2 (first page)', 'Focus on dynamics and pedaling.', CURDATE() + INTERVAL 7 DAY, 'pending', NULL),
(2, 4, 'Practice C major scale, 2 octaves', 'Metronome at 80 BPM.', CURDATE() - INTERVAL 3 DAY, 'completed', DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(3, 5, 'Practice barre chord exercise sheet', 'Complete all 5 exercises, record yourself.', CURDATE() + INTERVAL 5 DAY, 'pending', NULL);

INSERT INTO achievements (student_id, title, description, icon) VALUES
(4, 'First Steps', 'Logged your very first practice session', '🎵'),
(5, 'First Steps', 'Logged your very first practice session', '🎵');
