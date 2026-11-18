-- Run as the MySQL root user, then put these details in includes/config.php (DB_USER / DB_PASS).
-- Change the password below to a long random one!
CREATE USER IF NOT EXISTS 'learnhub'@'localhost' IDENTIFIED BY 'CHANGE-THIS-STRONG-PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON telesom_learnhub.* TO 'learnhub'@'localhost';
FLUSH PRIVILEGES;
