# Nayab Hostel Management System

A web-based hostel and mess administration system for students, hostel staff,
mess staff, guards, finance reviewers, and administrators. It is built with
PHP, MySQL, HTML, CSS, JavaScript, Bootstrap, and Font Awesome.

The application supports room and mess applications, occupancy management,
student payments, complaints, staff assignments, room-change requests,
pass-out requests, and daily guard movement records.

## Key Features

### Students

- Create an account and sign in with a roll number.
- View hostel, room, mess, payment, and profile information.
- Apply for hostel accommodation and a mess.
- Submit room-change and pass-out requests.
- Submit mess applications and complaints.
- Upload offline payment proof and track payment review status.
- View application and request status updates.

### Administrators

- Review and allocate hostel and mess applications.
- View allocated rooms and mess cards.
- Vacate students from rooms or messes.
- Create and remove Warden, Hostel Manager, Guard, Mess Manager, and
  Finance Reviewer accounts.
- Assign staff to a specific hostel or mess.
- Review and approve or reject payment submissions.
- Configure hostel and mess service fees.
- Monitor hostel, mess, payment, staff, and complaint data.

### Hostel and Mess Staff

- Hostel Managers and Wardens manage the applications and students assigned to
  their hostel.
- Mess Managers review and manage mess applications and allocations for their
  assigned mess.
- Guards view the student roster for their hostel and record hostel-out and
  college-entry events for one student once per event per day.
- Finance Reviewers review uploaded payment proof and payment requests.

## Payment Workflow

Payments are handled offline; the system does not integrate with a bank or
payment gateway. The project currently provides configured fee defaults for:

| Service | Option | Amount |
|---|---|---:|
| Hostel | A | Rs 27,000 |
| Hostel | G | Rs 13,000 |
| Mess | A | Rs 45,000 |
| Mess | G | Rs 25,000 |

Administrators can configure rates for other hostel and mess options from the
payment review screen. Payment proof is stored under `uploads/payment_proofs/`
and should be restricted to authorized staff in a deployed environment.

## Status Codes

### Hostel and Mess Applications

| Value | Meaning |
|---:|---|
| `1` | Pending |
| `0` | Approved |
| `-1` | Rejected |

### Payments

| Value | Meaning |
|---:|---|
| `0` | Unpaid |
| `2` | Pending review |
| `1` | Approved |
| `-1` | Rejected |

## Requirements

- XAMPP or a compatible Apache, PHP, and MySQL/MariaDB environment
- PHP 7.4 or newer
- PHP MySQLi extension
- MySQL 5.7+ or compatible MariaDB
- A modern web browser

## Installation

1. Clone or copy the project into the XAMPP web root:

   ```powershell
   git clone https://github.com/induscreatix-png/Hostel_Management_System.git C:\xampp\htdocs\Hostel_Management_System
   ```

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open `http://localhost/phpmyadmin`.

4. Import `database/hostel_management_system.sql` first. It creates the
   `hostel_management_system` database and the base tables.

5. Import the migration files in this order:

   ```text
   database/add_hostels_g_h.sql
   database/student_application_workflow.sql
   database/feature_expansion.sql
   database/payment_review_workflow.sql
   database/room_capacity_expansion.sql
   database/staff_role_assignments.sql
   database/guard_movement_tracking.sql
   ```

   The migration scripts use `CREATE TABLE IF NOT EXISTS`, conditional column
   checks, and/or seed records that allow them to be run again safely. The room
   capacity migration adds rooms as needed and updates occupancy data.

6. Verify the database connection in `includes/config.inc.php`:

   ```php
   $conn = mysqli_connect("localhost", "root", "", "hostel_management_system", 3306);
   ```

   Update the host, username, password, database name, or port if your local
   MySQL configuration differs.

7. Open the application:

   ```text
   http://localhost/Hostel_Management_System/
   ```

### Seeded Administrator

The base SQL dump contains a seeded administrator named `mainadmin` with the
password `trial`. Change this password immediately before using the system
outside a local development environment.

Create additional staff accounts through the administrator's Staff Access
screen. Staff passwords must contain at least eight characters.

## Database Setup via Command Line

The SQL files can also be imported with MySQL/MariaDB:

```powershell
mysql -u root -p -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/hostel_management_system.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/add_hostels_g_h.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/student_application_workflow.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/feature_expansion.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/payment_review_workflow.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/room_capacity_expansion.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/staff_role_assignments.sql"
mysql -u root -p hostel_management_system -e "SOURCE C:/xampp/htdocs/Hostel_Management_System/database/guard_movement_tracking.sql"
```

PowerShell may need forward slashes in MySQL `SOURCE` paths.

## Main Pages

| Page | Purpose |
|---|---|
| `index.php` | Student sign in |
| `signup.php` | Student registration |
| `home.php` | Public/student home page |
| `profile.php` | Student profile and application status |
| `application_form.php` | Hostel application |
| `application_form_mess.php` | Mess application |
| `payment_form.php` | Offline payment submission |
| `complaints.php` | Student complaint submission |
| `manage_student_requests.php` | Room-change and pass-out requests |
| `login-hostel_manager.php` | Staff sign in |
| `home_manager.php` | Hostel/Mess staff dashboard |
| `guard_movement.php` | Guard student movement tracking |
| `admin/admin_home.php` | Administrator dashboard |
| `admin/create_hm.php` | Staff account management |
| `admin/payments.php` | Payment review |

## Project Structure

```text
admin/          Administrator pages
database/       Base schema and migration SQL scripts
includes/       Authentication, database, access, and shared PHP helpers
uploads/        Uploaded payment proof files
web/            Shared CSS and image assets
web_home/       Home-page styles and scripts
templates/      Original template assets
```

## Deployment Notes

- Use HTTPS and secure database credentials in production.
- Replace all seeded and default credentials.
- Do not expose the `uploads/` directory without appropriate access controls.
- Back up the database before running migrations or allocation changes.
- Restrict PHP's MySQL user to only the permissions required by the application.
- Configure PHP upload limits and server storage rules for payment screenshots.
- The application uses server-side sessions; configure secure, HTTP-only,
  appropriately scoped session cookies on production servers.

## License

See the repository license if one is included. Template and third-party asset
licenses remain subject to their original license terms.
