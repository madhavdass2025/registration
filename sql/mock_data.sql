INSERT INTO registration (RegNo, PetName, OwnerName) VALUES
('REG001', 'Buddy', 'John Doe'),
('REG002', 'Misty', 'Jane Smith');

INSERT INTO consultation (RegNo, doctor_id, consultation_date, status) VALUES
('REG001', 1, NOW(), 'Scheduled'),
('REG002', 1, NOW(), 'In-Progress');

INSERT INTO medicines (MedicineName, SalePrice) VALUES
('Amoxicillin', 15.50),
('Meloxicam', 22.00),
('Dewormer Tab', 5.00);

INSERT INTO laboratory (LabTest, Amount) VALUES
('Blood Count', 45.00),
('Urine Analysis', 30.00);

INSERT INTO vaccination (VaccName, Amount) VALUES
('Rabies', 25.00),
('DHPP', 35.00);

INSERT INTO scan (ScanName, Amount) VALUES
('X-Ray Chest', 80.00),
('Ultrasound Abdomen', 120.00);

-- Initial Bill for Consultation 1
INSERT INTO billnew (consult_id, RegNo, netAmount, date) VALUES
(1, 'REG001', 0, NOW());
