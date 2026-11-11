-- Optional: the site upgrades itself automatically; use this only if your MySQL user cannot ALTER/CREATE.
USE telesom_learnhub;
ALTER TABLE users ADD COLUMN IF NOT EXISTS signatory VARCHAR(120) NULL, ADD COLUMN IF NOT EXISTS signatory_title VARCHAR(120) NULL;
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
