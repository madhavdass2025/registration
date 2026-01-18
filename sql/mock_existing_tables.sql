-- Mock Existing Tables for Simulation

CREATE TABLE IF NOT EXISTS registration (
    RegNo VARCHAR(50) PRIMARY KEY,
    PetName VARCHAR(100),
    OwnerName VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS consultation (
    consult_id INT AUTO_INCREMENT PRIMARY KEY,
    RegNo VARCHAR(50),
    doctor_id INT,
    consultation_date DATETIME,
    status ENUM('Scheduled', 'In-Progress', 'Completed') DEFAULT 'Scheduled',
    FOREIGN KEY (RegNo) REFERENCES registration(RegNo)
);

CREATE TABLE IF NOT EXISTS medicines (
    Mid INT AUTO_INCREMENT PRIMARY KEY,
    MedicineName VARCHAR(255),
    SalePrice DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS laboratory (
    Lid INT AUTO_INCREMENT PRIMARY KEY,
    LabTest VARCHAR(255),
    Amount DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS vaccination (
    VId INT AUTO_INCREMENT PRIMARY KEY,
    VaccName VARCHAR(255),
    Amount DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS scan (
    sID INT AUTO_INCREMENT PRIMARY KEY,
    ScanName VARCHAR(255),
    Amount DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS billnew (
    billId INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT,
    RegNo VARCHAR(50),
    netAmount DECIMAL(10,2),
    date DATETIME
);

CREATE TABLE IF NOT EXISTS billmedicine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billId INT,
    medId INT,
    amount DECIMAL(10,2),
    payment_status TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS billlaboratory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billId INT,
    testId INT,
    amount DECIMAL(10,2),
    payment_status TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS billvaccination (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billId INT,
    vaccId INT,
    amount DECIMAL(10,2),
    payment_status TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS billscan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billId INT,
    scanId INT,
    amount DECIMAL(10,2),
    payment_status TINYINT(1) DEFAULT 0
);
