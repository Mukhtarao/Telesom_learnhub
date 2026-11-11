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

-- ===== Demo data (all demo passwords: password) =====
INSERT INTO users (id,name,email,password,role,org_id,country,bio) VALUES
(1,'System Administrator','admin@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,'Somaliland','Platform administrator.'),
(2,'Ahmed Warsame','instructor@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','instructor',NULL,'Somaliland','Senior software engineer and trainer.'),
(3,'Hodan Abdillahi','hodan@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','instructor',NULL,'Somaliland','Business and customer experience coach.'),
(4,'Abdi Mohamed','learner@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','learner',NULL,'Somaliland',NULL),
(5,'Ifrah Yusuf','ifrah@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','learner',NULL,'Somaliland',NULL),
(6,'Telesom Staff Training','org@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','organization',NULL,'Somaliland','Internal training department.'),
(7,'Mustafe Ali','mustafe@telesom.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','learner',6,'Somaliland',NULL);

INSERT INTO categories (id,name) VALUES (1,'Programming'),(2,'Business'),(3,'IT & Networking'),(4,'Languages'),(5,'Design'),(6,'Marketing');

INSERT INTO courses (id,title,description,category_id,instructor_id,org_id,level,duration,objectives,prerequisites,enroll_type,price,status) VALUES
(1,'Web Development Fundamentals','Learn how websites work and build your first responsive pages using HTML, CSS and JavaScript.',1,2,NULL,'Beginner','6 hours','Understand the web|Write semantic HTML|Style pages with CSS|Add interactivity with JavaScript','Basic computer skills','free',0,'approved'),
(2,'Customer Service Excellence','Deliver outstanding service in a telecom environment: communication, empathy and complaint handling.',2,3,NULL,'Beginner','4 hours','Communicate clearly|Handle complaints|Build customer loyalty','None','free',0,'approved'),
(3,'Network Fundamentals','Understand IP addressing, switching, routing and the basics of mobile and fibre networks.',3,2,NULL,'Intermediate','8 hours','Explain the OSI model|Plan IP addressing|Troubleshoot connectivity','Basic IT knowledge','approval',0,'approved'),
(4,'Digital Marketing Essentials','Promote products online with social media, content and analytics.',6,3,NULL,'Beginner','5 hours','Plan a campaign|Use social media|Measure results','None','paid',25,'approved'),
(5,'Cybersecurity Awareness','Protect yourself and your organization from phishing, weak passwords and social engineering.',3,2,NULL,'Beginner','3 hours','Spot phishing|Use strong passwords|Report incidents','None','free',0,'approved'),
(6,'Telesom Employee Onboarding','Private course for new Telesom staff: company values, services and workplace policies.',2,6,6,'Beginner','2 hours','Know our services|Understand company values','None','private',0,'approved'),
(7,'Advanced JavaScript (Draft)','Submitted by an instructor — waiting for administrator approval.',1,2,NULL,'Advanced','10 hours','Master async code','JavaScript basics','free',0,'pending');

INSERT INTO lessons (course_id,title,type,content,url,position) VALUES
(1,'How the Web Works','text','The web is a network of computers. A browser sends a request to a server, and the server replies with HTML, CSS and JavaScript files.\n\nHTML gives a page its structure, CSS gives it style, and JavaScript makes it interactive.',NULL,1),
(1,'HTML Basics','text','HTML uses tags such as <h1>, <p>, <a> and <img> to describe content.\n\nEvery page starts with <!DOCTYPE html> and contains a <head> and a <body>.',NULL,2),
(1,'Styling with CSS','text','CSS selects elements and sets properties: colour, spacing, fonts and layout. Flexbox and Grid are the modern tools for page layout.',NULL,3),
(1,'Learn more: MDN Web Docs','link','Open the official MDN Web Docs to continue practising.','https://developer.mozilla.org/en-US/docs/Learn',4),
(2,'The Customer Journey','text','Every contact is a chance to build trust. Greet the customer, listen fully, confirm the problem, resolve it, and follow up.',NULL,1),
(2,'Handling Complaints','text','Use the LAST method: Listen, Apologise, Solve, Thank. Stay calm and never take complaints personally.',NULL,2),
(3,'The OSI Model','text','The OSI model has seven layers: Physical, Data Link, Network, Transport, Session, Presentation and Application.',NULL,1),
(3,'IP Addressing','text','An IPv4 address has 32 bits written as four numbers, e.g. 192.168.1.10. A subnet mask splits the network part from the host part.',NULL,2),
(4,'Marketing Basics','text','Marketing connects the right message with the right audience. Define your goal, audience and channel before you start.',NULL,1),
(4,'Social Media Strategy','text','Choose platforms where your audience spends time, post consistently, and measure engagement weekly.',NULL,2),
(5,'Recognising Phishing','text','Phishing messages create urgency and ask for passwords or payments. Check the sender, hover over links and never share OTP codes.',NULL,1),
(5,'Strong Passwords','text','Use long passphrases, a different one for every account, and enable two-step verification wherever possible.',NULL,2),
(6,'Welcome to Telesom','text','Telesom provides mobile, internet and digital financial services to customers across Somaliland. Our values: customer first, integrity, teamwork and innovation.',NULL,1),
(6,'Workplace Policies','text','Respect confidentiality, protect customer data, arrive on time and report incidents to your supervisor immediately.',NULL,2);

INSERT INTO lessons (course_id,title,type,content,url,position) VALUES
(1,'Watch: Course Introduction','video','Watch this short video, then continue to the next lesson.','https://www.w3schools.com/html/mov_bbb.mp4',5),
(2,'Watch: Service in Action','video','A short demo video lesson.','https://www.w3schools.com/html/movie.mp4',3),
(5,'Watch: Stay Safe Online','video','A short demo video lesson.','https://www.w3schools.com/html/mov_bbb.mp4',3);

INSERT INTO quizzes (id,course_id,title,type,pass_marks,time_limit,max_attempts) VALUES
(1,1,'HTML & CSS Quiz','quiz',50,10,0),(2,1,'Final Examination','exam',60,15,3),
(3,2,'Customer Service Quiz','quiz',50,0,0),(4,3,'Networking Quiz','quiz',50,0,0),
(5,5,'Security Awareness Quiz','quiz',50,0,0),(6,6,'Onboarding Check','quiz',50,0,0),(7,4,'Marketing Quiz','quiz',50,0,0);

INSERT INTO questions (quiz_id,question,type,opt_a,opt_b,opt_c,opt_d,answer,marks) VALUES
(1,'Which tag creates a hyperlink?','mcq','<link>','<a>','<href>','<p>','b',1),
(1,'CSS stands for Cascading Style Sheets.','tf',NULL,NULL,NULL,NULL,'true',1),
(1,'Which property changes text colour?','mcq','font-style','text-align','color','background','c',1),
(2,'JavaScript runs in the browser.','tf',NULL,NULL,NULL,NULL,'true',1),
(2,'Which layout system is one-dimensional?','mcq','Grid','Flexbox','Table','Float','b',2),
(2,'What does HTML stand for? (short answer)','short',NULL,NULL,NULL,NULL,'HyperText Markup Language',2),
(3,'What does the LAST method start with?','mcq','Listen','Leave','Learn','Laugh','a',1),
(3,'You should take complaints personally.','tf',NULL,NULL,NULL,NULL,'false',1),
(4,'How many layers does the OSI model have?','mcq','4','5','7','9','c',1),
(4,'192.168.1.10 is an IPv4 address.','tf',NULL,NULL,NULL,NULL,'true',1),
(5,'Phishing messages often create a sense of…','mcq','Calm','Urgency','Humour','Boredom','b',1),
(5,'It is safe to share your OTP code with support staff.','tf',NULL,NULL,NULL,NULL,'false',1),
(6,'Which is one of our company values?','mcq','Customer first','Secrecy','Delay','Competition with colleagues','a',1),
(6,'Customer data must be protected.','tf',NULL,NULL,NULL,NULL,'true',1),
(7,'Define your goal and audience before starting a campaign.','tf',NULL,NULL,NULL,NULL,'true',1);

INSERT INTO assignments (course_id,title,instructions,deadline,max_marks) VALUES
(1,'Build a Personal Page','Create a one-page personal website using HTML and CSS. Submit a short description or upload a ZIP of your files.',DATE_ADD(NOW(), INTERVAL 14 DAY),100),
(2,'Complaint Role-play Reflection','Describe a complaint you handled (or observed) and how you applied the LAST method.',DATE_ADD(NOW(), INTERVAL 10 DAY),50);

INSERT INTO enrollments (user_id,course_id,status) VALUES (4,1,'active'),(4,2,'active'),(5,1,'active'),(7,6,'active');
INSERT INTO progress (user_id,lesson_id) VALUES (4,1),(4,2),(5,1);
INSERT INTO reviews (course_id,user_id,rating,body) VALUES (1,5,5,'Clear and practical — perfect for beginners.'),(1,4,4,'Great start, I would like more exercises.');
INSERT INTO comments (course_id,user_id,body) VALUES (1,4,'Is there a recommended code editor?'),(1,2,'Yes — Visual Studio Code is free and works great.');
INSERT INTO announcements (title,body) VALUES ('Welcome to Telesom LearnHub','Our new learning platform is live. Browse courses, learn at your own pace and earn verified certificates.');
INSERT INTO notifications (user_id,message,link) VALUES (4,'Welcome to Telesom LearnHub!','dashboard.php'),(2,'A new course is waiting for approval.','admin.php?tab=courses');

INSERT INTO enrollments (user_id,course_id,status,paid) VALUES (5,4,'active',1);
INSERT INTO payments (invoice,user_id,course_id,amount,method,phone,reference,status,paid_at) VALUES ('INV-DEMO-0001',5,4,25.00,'zaad','0634000000','DEMO-ZAAD-1001','paid',NOW());

INSERT INTO live_classes (course_id,title,description,start_at,duration_min,meeting_url,provider,status) VALUES
(1,'Live Q&A: Build your first web page','Bring your questions about HTML and CSS. We will build a small page together.',DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 1 DAY), INTERVAL 10 HOUR),60,'https://meet.jit.si/TelesomLearnHub-WebDev-Demo','jitsi','scheduled'),
(2,'Live workshop: Handling difficult customers','Role-play and discussion with real examples.',DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 14 HOUR),60,'https://meet.jit.si/TelesomLearnHub-CustomerService-Demo','jitsi','ended');
INSERT INTO live_attendance (live_id,user_id) VALUES (2,4);

INSERT INTO plans (name,billing,price,max_learners,max_courses,features,sort_order) VALUES
('Starter','monthly',49,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',1),
('Business','monthly',129,100,20,'Everything in Starter|Custom certificate signatory|Priority support',2),
('Enterprise','monthly',299,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',3),
('Starter','yearly',490,25,5,'Assignments, quizzes & exams|Certificates with QR verification|Live classes & reports',4),
('Business','yearly',1290,100,20,'Everything in Starter|Custom certificate signatory|Priority support',5),
('Enterprise','yearly',2990,500,0,'Everything in Business|Dedicated onboarding|Custom contract & invoicing',6);
INSERT INTO org_subscriptions (org_id,plan_id,plan_name,billing,amount,max_learners,max_courses,status,sponsored,method,invoice,start_at,end_at,confirmed_at) VALUES (6,NULL,'Founding member (free)','yearly',0,100,0,'active',1,'none','SUB-DEMO-0001',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 1 YEAR),NOW());
