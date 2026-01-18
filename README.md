# Medical Multi-Section Billing Dashboard

This project is a post-consultation checkout dashboard for medical clinics. It allows patients or staff to view categorized bills, pay for them, and print receipts for paid items.

## Features
- **Categorized Billing**: Bills are grouped by Registration, Consultation, Medicines, Laboratory, Scanning, Vaccination, and Injection.
- **Dynamic UI**: Uses Bootstrap 5 for a responsive, professional look.
- **Payment Logic**: "Pay Now" buttons for unpaid items; "Paid" status for completed payments.
- **Totals Calculation**: Automatically calculates "Grand Total" and "Total Due".
- **Print Functionality**: "Print Receipt" button that triggers a browser print. Only "Paid" items are included in the print view.
- **Secure**: Uses PHP Prepared Statements to prevent SQL injection.

## Project Structure
- `index.php`: Main dashboard.
- `pay.php`: Handler for updating payment status.
- `includes/db.php`: Database connection configuration.
- `sql/schema.sql`: Database schema definition.
- `sql/seed.sql`: Sample data for testing.

## Setup Instructions
1. **Database Setup**:
   - Create a MySQL database named `medical_billing`.
   - Run the SQL commands in `sql/schema.sql`.
   - (Optional) Run `sql/seed.sql` to populate sample data.

2. **Configuration**:
   - Edit `includes/db.php` and update the database credentials (`host`, `user`, `pass`, `db`).

3. **Usage**:
   - Access the dashboard via `index.php?cid=101` (replace `101` with a valid `consultation_id` from your database).

## Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL (MySQLi)
- **Frontend**: Bootstrap 5, Font Awesome
