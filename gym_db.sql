CREATE DATABASE IF NOT EXISTS oxygym;
USE oxygym;

-- ----------------------------
-- 1. MEMBERS table
-- ----------------------------
CREATE TABLE Members (
    Member_ID INT AUTO_INCREMENT PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Gender ENUM('Male', 'Female', 'Other'),
    Birthdate DATE,
    Join_Date DATE,
    Membership_ID INT,
    Expiry_Date DATE,
    Status ENUM('Active', 'Expired', 'Pending') DEFAULT 'Pending'
);

-- ----------------------------
-- 2. USERS (Login Credentials)
-- ----------------------------
CREATE TABLE Users (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT NULL,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Staff', 'Member') NOT NULL DEFAULT 'Member',

    FOREIGN KEY (Member_ID) REFERENCES Members(Member_ID)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- ----------------------------
-- 3. MEMBERSHIP TYPES
-- ----------------------------
CREATE TABLE Membership_Types (
    Membership_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100),
    Duration_Days INT NOT NULL,
    Price DECIMAL(10,2) NOT NULL,
    Description TEXT
);

-- ----------------------------
-- 4. TRANSACTIONS
-- ----------------------------
CREATE TABLE Transactions (
    Transaction_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT NOT NULL,
    Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Payment_Method ENUM('GCash','Cash','Card') NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Reference_No VARCHAR(100),
    Status ENUM('Paid','Pending') DEFAULT 'Pending',

    FOREIGN KEY (Member_ID) REFERENCES Members(Member_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ----------------------------
-- 5. ATTENDANCE
-- ----------------------------
CREATE TABLE Attendance (
    Attendance_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT NOT NULL,
    Check_In DATETIME DEFAULT CURRENT_TIMESTAMP,
    Check_Out DATETIME NULL,

    FOREIGN KEY (Member_ID) REFERENCES Members(Member_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ----------------------------
-- 6. REVIEWS
-- ----------------------------
CREATE TABLE Reviews (
    Review_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT NOT NULL,
    Rating INT CHECK (Rating BETWEEN 1 AND 5),
    Title VARCHAR(255),
    Body TEXT,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (Member_ID) REFERENCES Members(Member_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- ----------------------------
-- 7. SERVICES
-- ----------------------------
CREATE TABLE Services (
    Service_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100),
    Description TEXT
);

-- ----------------------------
-- 8. SUBSCRIPTION HISTORY (Renewal Records)
-- ----------------------------
CREATE TABLE Subscription_History (
    Subscription_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT NOT NULL,
    Membership_ID INT NOT NULL,
    Start_Date DATE NOT NULL,
    End_Date DATE NOT NULL,
    Status ENUM('Active','Expired') DEFAULT 'Active',
    Transaction_ID INT NULL,

    FOREIGN KEY (Member_ID) REFERENCES Members(Member_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Membership_ID) REFERENCES Membership_Types(Membership_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Transaction_ID) REFERENCES Transactions(Transaction_ID)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);
