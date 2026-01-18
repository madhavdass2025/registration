# Pet Clinic Management System - Doctor & Billing Modules

This project contains the Doctor Consultation Module and the Integrated Billing Dashboard for a Pet Clinic.

## Modules

### 1. Doctor Module (`doctor/`)
- **Dashboard**: View today's schedule for the logged-in doctor. Filter by status (Scheduled, In-Progress, Completed).
- **Consultation Interface**:
    - **History**: View past visit timeline including vitals, notes, and prescriptions.
    - **Vitals**: Capture temperature, heart rate, respiratory rate, and weight. View weight history via Chart.js.
    - **Diagnosis**: Record clinical notes and next review date.
    - **Prescriptions**: Dynamic AJAX search for medicines and dosage instructions.
    - **Orders**: Request Lab tests, Vaccinations, and Scans.
- **Automatic Billing**: Saving a consultation automatically generates bill entries and updates the total bill amount.

### 2. Billing Dashboard (`index.php`)
- View categorized bills for a specific consultation.
- Real-time calculation of Grand Total, Total Paid, and Total Due.
- "Pay Now" functionality for individual items.
- Print-ready receipts optimized to show only paid items.

## Database Setup

1. Create a database named `jeoczvkk_thecochinpetshop`.
2. Run `sql/mock_existing_tables.sql` to create the core tables.
3. Run `sql/doctor_module.sql` to extend the database with consultation-specific tables.
4. Run `sql/mock_data.sql` to populate sample data for testing.

## Configuration

Edit `includes/db_petclinic.php` to set your database credentials.

## Usage

1. Access the Doctor Dashboard at `/doctor/dashboard.php`.
2. Click "Start Consultation" to begin a session.
3. Save the consultation to generate billing data.
4. View and pay bills at `/index.php?cid=[CONSULT_ID]`.

## Security & Tech Stack
- **PHP 8.x** with MySQLi Prepared Statements.
- **Bootstrap 5** for responsive UI.
- **Select2** for AJAX-powered searchable dropdowns.
- **SweetAlert2** for user notifications.
- **Chart.js** for data visualization.
