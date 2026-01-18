-- Extension for jeoczvkk_thecochinpetshop database

-- Vitals Table: Track temperature, heart rate, respiratory rate, and weight
CREATE TABLE IF NOT EXISTS vitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT NOT NULL,
    RegNo VARCHAR(50) NOT NULL,
    temperature DECIMAL(5,2),
    heart_rate INT,
    respiratory_rate INT,
    weight DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consult_id) REFERENCES consultation(consult_id),
    FOREIGN KEY (RegNo) REFERENCES registration(RegNo)
);

-- Diagnosis Table: Store notes, differential diagnosis, and treatment plans
CREATE TABLE IF NOT EXISTS diagnosis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT NOT NULL,
    RegNo VARCHAR(50) NOT NULL,
    clinical_notes TEXT,
    differential_diagnosis TEXT,
    treatment_plan TEXT,
    next_review_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consult_id) REFERENCES consultation(consult_id)
);

-- Prescriptions Table: Link to existing medicines.Mid
CREATE TABLE IF NOT EXISTS consultation_prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT NOT NULL,
    med_id INT NOT NULL, -- Links to medicines.Mid
    dosage VARCHAR(255),
    frequency VARCHAR(255),
    duration VARCHAR(255),
    instructions TEXT,
    FOREIGN KEY (consult_id) REFERENCES consultation(consult_id),
    FOREIGN KEY (med_id) REFERENCES medicines(Mid)
);

-- Lab Orders Table: Link to existing laboratory.Lid
CREATE TABLE IF NOT EXISTS consultation_lab_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT NOT NULL,
    test_id INT NOT NULL, -- Links to laboratory.Lid
    status TINYINT(1) DEFAULT 0, -- 0: Ordered, 1: Completed
    FOREIGN KEY (consult_id) REFERENCES consultation(consult_id),
    FOREIGN KEY (test_id) REFERENCES laboratory(Lid)
);

-- Vaccination Orders Table: Link to existing vaccination.VId
CREATE TABLE IF NOT EXISTS consultation_vaccination_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT NOT NULL,
    vacc_id INT NOT NULL, -- Links to vaccination.VId
    status TINYINT(1) DEFAULT 0,
    FOREIGN KEY (consult_id) REFERENCES consultation(consult_id),
    FOREIGN KEY (vacc_id) REFERENCES vaccination(VId)
);

-- Imaging Orders Table: Link to existing scan.sID
CREATE TABLE IF NOT EXISTS consultation_imaging_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consult_id INT NOT NULL,
    scan_id INT NOT NULL, -- Links to scan.sID
    notes TEXT,
    status TINYINT(1) DEFAULT 0,
    FOREIGN KEY (consult_id) REFERENCES consultation(consult_id),
    FOREIGN KEY (scan_id) REFERENCES scan(sID)
);
