-- Merged & cleaned XAMPP-ready SQL for database `oxygym`
-- Generated: Nov 29, 2025
-- MariaDB / phpMyAdmin compatible

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS oxygym;
CREATE DATABASE oxygym DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE oxygym;

/* =========================
   TABLE: membership_types
   (Create first because subscription_history FK references it)
   ========================= */
CREATE TABLE IF NOT EXISTS membership_types (
  Membership_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Name VARCHAR(100),
  Duration_Days INT(11) NOT NULL,
  Price DECIMAL(10,2) NOT NULL,
  Description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- seed membership types (preserve original IDs)
INSERT INTO membership_types (Membership_ID, Name, Duration_Days, Price, Description) VALUES
(1, 'Standard', 30, 999.00, 'Basic gym access'),
(2, 'Prime', 30, 1499.00, '1-on-1 coaching + nutrition'),
(3, 'Premium', 365, 14999.00, 'All prime benefits + exclusive events');

ALTER TABLE membership_types AUTO_INCREMENT = 4;

/* =========================
   TABLE: members
   ========================= */
CREATE TABLE IF NOT EXISTS members (
  Member_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  First_Name VARCHAR(50) NOT NULL,
  Last_Name VARCHAR(50) NOT NULL,
  Email VARCHAR(100) NOT NULL UNIQUE,
  Phone VARCHAR(20) DEFAULT NULL,
  Gender ENUM('Male','Female','Other') DEFAULT NULL,
  Birthdate DATE DEFAULT NULL,
  Join_Date DATE DEFAULT NULL,
  Membership_ID INT(11) DEFAULT NULL,
  Expiry_Date DATE DEFAULT NULL,
  Status ENUM('Active','Expired','Pending') DEFAULT 'Pending',
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_membership_id FOREIGN KEY (Membership_ID) REFERENCES membership_types(Membership_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- seed members (preserve original IDs)
INSERT INTO members (Member_ID, First_Name, Last_Name, Email, Phone, Gender, Birthdate, Join_Date, Membership_ID, Expiry_Date, Status) VALUES
(9, 'Jairus', 'Segovia', 'jai@gmail.com', NULL, 'Male', '2006-01-26', '2025-11-26', NULL, NULL, 'Active'),
(10, 'JD', 'KAlisag', 'JD@gmail.com', NULL, NULL, NULL, '2025-11-27', NULL, NULL, 'Active'),
(11, 'JD', 'KAlisag', 'J_d@gmail.com', NULL, 'Male', '2012-01-27', '2025-11-27', NULL, NULL, 'Active'),
(12, 'yut', 'iyu', 'yut@gmail.com', NULL, NULL, NULL, '2025-11-27', NULL, NULL, 'Active');

ALTER TABLE members AUTO_INCREMENT = 13;

/* =========================
   TABLE: transactions
   (create before subscription_history because of FK)
   ========================= */
CREATE TABLE IF NOT EXISTS transactions (
  Transaction_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Member_ID INT(11) NOT NULL,
  Date DATETIME DEFAULT CURRENT_TIMESTAMP,
  Payment_Method ENUM('GCash','Cash','Card') NOT NULL,
  Amount DECIMAL(10,2) NOT NULL,
  Reference_No VARCHAR(100) DEFAULT NULL,
  Status ENUM('Paid','Pending') DEFAULT 'Pending',
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* =========================
   TABLE: subscription_history
   ========================= */
CREATE TABLE IF NOT EXISTS subscription_history (
  Subscription_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Member_ID INT(11) NOT NULL,
  Membership_ID INT(11) NOT NULL,
  Start_Date DATE NOT NULL,
  End_Date DATE NOT NULL,
  Status ENUM('Active','Expired','Cancelled') DEFAULT 'Active',
  Transaction_ID INT(11) DEFAULT NULL,
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_member FOREIGN KEY (Member_ID) REFERENCES members(Member_ID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sub_membership FOREIGN KEY (Membership_ID) REFERENCES membership_types(Membership_ID) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sub_transaction FOREIGN KEY (Transaction_ID) REFERENCES transactions(Transaction_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- seed subscription_history (preserve original IDs)
INSERT INTO subscription_history (Subscription_ID, Member_ID, Membership_ID, Start_Date, End_Date, Status, Transaction_ID) VALUES
(6, 9, 3, '2025-11-27', '2025-12-27', 'Active', NULL),
(7, 11, 1, '2025-11-27', '2025-12-27', 'Active', NULL);

ALTER TABLE subscription_history AUTO_INCREMENT = 8;

/* =========================
   TABLE: attendance
   ========================= */
CREATE TABLE IF NOT EXISTS attendance (
  Attendance_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Member_ID INT(11) NOT NULL,
  Check_In DATETIME DEFAULT CURRENT_TIMESTAMP,
  Check_Out DATETIME DEFAULT NULL,
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_member FOREIGN KEY (Member_ID) REFERENCES members(Member_ID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* =========================
   TABLE: reviews
   ========================= */
CREATE TABLE IF NOT EXISTS reviews (
  Review_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Member_ID INT(11) NOT NULL,
  Rating INT(11) NOT NULL,
  Title VARCHAR(255) DEFAULT NULL,
  Body TEXT DEFAULT NULL,
  Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rev_member FOREIGN KEY (Member_ID) REFERENCES members(Member_ID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* =========================
   TABLE: services
   ========================= */
CREATE TABLE IF NOT EXISTS services (
  Service_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Name VARCHAR(100) DEFAULT NULL,
  Description TEXT DEFAULT NULL,
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* =========================
   TABLE: users
   (User accounts; one-directional link to members only)
   ========================= */
CREATE TABLE IF NOT EXISTS users (
  User_ID INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  Member_ID INT(11) DEFAULT NULL,
  Username VARCHAR(50) NOT NULL UNIQUE,
  Password_Hash VARCHAR(255) NOT NULL,
  Role ENUM('Admin','Staff','Member') NOT NULL DEFAULT 'Member',
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_member FOREIGN KEY (Member_ID) REFERENCES members(Member_ID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* =========================
   Seed users (preserve original IDs and map to members)
   Note: admin account inserted with NULL Member_ID and placeholder hash.
   Replace admin password hash with a real bcrypt hash before production.
   ========================= */
INSERT INTO users (User_ID, Member_ID, Username, Password_Hash, Role, Created_At) VALUES
(9, 9, 'jai@gmail.com', '$2y$10$fu4VQsWn42Hk7jij/2lwWe.e/svme4BbtwL9IAxP3vWd75gFDn.tK', 'Member', CURRENT_TIMESTAMP),
(10, 10, 'JD', '$2y$10$aIRXrji9FmILDvIqdDjRC.ZnynI5WlbpY3IbgGKtkYt1rzZLQD2Ke', 'Member', CURRENT_TIMESTAMP),
(11, 11, 'J_d', '$2y$10$v57D19zVcIJH5/I3SLUukuMU5yl6izctDCSoIYHqO20VuAznaxqAy', 'Member', CURRENT_TIMESTAMP),
(12, 12, 'yut', '$2y$10$2HhsF0n.D1KRIAIxjeYzYOV/EiHIZ87k/Tq/S5t0R.L7sI2iXyKx.', 'Member', CURRENT_TIMESTAMP),
(13, NULL, 'admin', '$2y$10$8vR8RZ8ZKZfvN7vN7vN7vukqXXXXXXXXXXXXXXXXXXXXXXXXXXX', 'Admin', CURRENT_TIMESTAMP);

ALTER TABLE users AUTO_INCREMENT = 14;

/* =========================
   Indexes (additional helpful indexes)
   ========================= */
CREATE INDEX idx_subscription_member ON subscription_history (Member_ID);
CREATE INDEX idx_subscription_status ON subscription_history (Status);
CREATE INDEX idx_review_member ON reviews (Member_ID);
CREATE INDEX idx_user_member ON users (Member_ID);
CREATE INDEX idx_transaction_member ON transactions (Member_ID);

/* =========================
   Notes & cleanup
   - All foreign keys set; no circular FK between users and members.
   - Replace the admin Password_Hash with a proper bcrypt hash of your chosen admin password before production.
   - If you prefer different ON DELETE behaviors (e.g., RESTRICT instead of SET NULL), edit the constraints accordingly.
   ========================= */

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
