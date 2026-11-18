-- Optional: the site upgrades itself automatically. Use this only if your MySQL user cannot ALTER/CREATE.
USE telesom_learnhub;
ALTER TABLE users ADD COLUMN org_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved';
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
INSERT INTO plans (name,billing,price,max_learners,max_courses,features,sort_order) VALUES
('Starter','monthly',49,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',1),
('Business','monthly',129,100,20,'Everything in Starter|Custom certificate signatory|Priority support',2),
('Enterprise','monthly',299,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',3),
('Starter','yearly',490,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',4),
('Business','yearly',1290,100,20,'Everything in Starter|Custom certificate signatory|Priority support',5),
('Enterprise','yearly',2990,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',6);
