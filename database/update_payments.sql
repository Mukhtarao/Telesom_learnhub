-- Run this ONLY if you already imported the older database.sql (adds the payments table)
USE telesom_learnhub;
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY, invoice VARCHAR(30), user_id INT NOT NULL, course_id INT NOT NULL, amount DECIMAL(8,2) NOT NULL,
  method ENUM('zaad','edahab','bank') NOT NULL, phone VARCHAR(30), reference VARCHAR(60),
  status ENUM('pending','paid','rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, paid_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
