CREATE DATABASE IF NOT EXISTS medical_billing;
USE medical_billing;

CREATE TABLE IF NOT EXISTS patient_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    category ENUM('Registration', 'Consultation', 'Medicines', 'Laboratory', 'Scanning', 'Vaccination', 'Injection') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status TINYINT(1) DEFAULT 0, -- 0 for unpaid, 1 for paid
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
