-- Telesom LearnHub database (MySQL / MariaDB)
CREATE DATABASE IF NOT EXISTS telesom_learnhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE telesom_learnhub;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL,
  role ENUM('admin','instructor','learner','organization') NOT NULL DEFAULT 'learner',
  org_id INT NULL, phone VARCHAR(30), country VARCHAR(60), bio TEXT, education VARCHAR(255), skills VARCHAR(255), signatory VARCHAR(120) NULL, signatory_title VARCHAR(120) NULL,
  org_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE) ENGINE=InnoDB;
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, description TEXT, category_id INT,
  instructor_id INT NOT NULL, org_id INT NULL, level ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
  duration VARCHAR(40), objectives TEXT, prerequisites TEXT,
  enroll_type ENUM('free','paid','approval','private') DEFAULT 'free', price DECIMAL(8,2) DEFAULT 0,
  status ENUM('pending','approved','rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE lessons (
  id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, title VARCHAR(160) NOT NULL,
  type ENUM('text','video','audio','pdf','document','image','link') DEFAULT 'text',
  content TEXT, url VARCHAR(500), file VARCHAR(255), position INT DEFAULT 0,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, course_id INT NOT NULL,
  status ENUM('active','pending') DEFAULT 'active', paid TINYINT DEFAULT 0,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, completed_at DATETIME NULL, UNIQUE(user_id, course_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE progress (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, lesson_id INT NOT NULL, done_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(user_id, lesson_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, title VARCHAR(160) NOT NULL, instructions TEXT, file VARCHAR(255),
  deadline DATETIME NULL, max_marks INT DEFAULT 100, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE submissions (
  id INT AUTO_INCREMENT PRIMARY KEY, assignment_id INT NOT NULL, user_id INT NOT NULL, body TEXT, file VARCHAR(255),
  marks INT NULL, feedback TEXT, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(assignment_id, user_id),
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, title VARCHAR(160) NOT NULL, type ENUM('quiz','exam') DEFAULT 'quiz',
  pass_marks INT DEFAULT 50, time_limit INT DEFAULT 0, max_attempts INT DEFAULT 0, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY, quiz_id INT NOT NULL, question TEXT NOT NULL, type ENUM('mcq','tf','short') DEFAULT 'mcq',
  opt_a VARCHAR(255), opt_b VARCHAR(255), opt_c VARCHAR(255), opt_d VARCHAR(255), answer VARCHAR(255) NOT NULL, marks INT DEFAULT 1,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE attempts (
  id INT AUTO_INCREMENT PRIMARY KEY, quiz_id INT NOT NULL, user_id INT NOT NULL, score INT, total INT, passed TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE certificates (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, course_id INT NOT NULL, code VARCHAR(20) NOT NULL UNIQUE, score INT,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, course_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, user_id INT NOT NULL, rating TINYINT NOT NULL, body TEXT, reported TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(course_id, user_id),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, user_id INT NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY, from_id INT NOT NULL, to_id INT NOT NULL, body TEXT NOT NULL, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, link VARCHAR(150), is_read TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, body TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY, invoice VARCHAR(30), user_id INT NOT NULL, course_id INT NOT NULL, amount DECIMAL(8,2) NOT NULL,
  method ENUM('zaad','edahab','bank') NOT NULL, phone VARCHAR(30), reference VARCHAR(60),
  status ENUM('pending','paid','rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, paid_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS live_classes (
  id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, title VARCHAR(160) NOT NULL, description TEXT, start_at DATETIME NOT NULL,
  duration_min INT DEFAULT 60, meeting_url VARCHAR(500) NOT NULL, provider ENUM('jitsi','zoom','meet','teams','other') DEFAULT 'jitsi',
  status ENUM('scheduled','live','ended','cancelled') DEFAULT 'scheduled', recording_url VARCHAR(500) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS live_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY, live_id INT NOT NULL, user_id INT NOT NULL, joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(live_id, user_id),
  FOREIGN KEY (live_id) REFERENCES live_classes(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plans (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL, billing ENUM('monthly','yearly') NOT NULL, price DECIMAL(10,2) NOT NULL,
  max_learners INT NOT NULL DEFAULT 25, max_courses INT NOT NULL DEFAULT 5, features VARCHAR(255) NULL, active TINYINT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS org_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY, org_id INT NOT NULL, plan_id INT NULL, plan_name VARCHAR(80), billing ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0, max_learners INT NOT NULL DEFAULT 25, max_courses INT NOT NULL DEFAULT 5,
  status ENUM('pending','active','rejected','cancelled') NOT NULL DEFAULT 'pending', sponsored TINYINT NOT NULL DEFAULT 0,
  method ENUM('zaad','edahab','bank','none') DEFAULT 'none', phone VARCHAR(30), reference VARCHAR(60), invoice VARCHAR(30),
  start_at DATE NULL, end_at DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, confirmed_at DATETIME NULL,
  FOREIGN KEY (org_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Starting data (no accounts, no sample courses) =====
-- The first administrator is created in the browser at setup.php.
INSERT INTO categories (id,name) VALUES (1,'Programming'),(2,'Business'),(3,'IT & Networking'),(4,'Languages'),(5,'Design'),(6,'Marketing');

-- Starter plan prices: set your real prices in Admin > Finance > Plans & Pricing.
INSERT INTO plans (name,billing,price,max_learners,max_courses,features,sort_order) VALUES
('Starter','monthly',49,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',1),
('Business','monthly',129,100,20,'Everything in Starter|Custom certificate signatory|Priority support',2),
('Enterprise','monthly',299,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',3),
('Starter','yearly',490,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',4),
('Business','yearly',1290,100,20,'Everything in Starter|Custom certificate signatory|Priority support',5),
('Enterprise','yearly',2990,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',6);
