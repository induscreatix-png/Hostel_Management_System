# Hostel-Management-System
A system built for hostel room and mess allocation and management. Implemented using HTML, JavaScript, PHP and MySql database

# Features
 1. Log in as student or Hostel Manager
 2. As student pay fees, apply for rooms / mess
 3. As Hostel Manager look at all the room / mess applications and allocate them
 4. Vacate Students from a room / mess
 5. View a list of all allocated students

# Student request workflow

After importing `database/hostel_management_system.sql`, run
`database/student_application_workflow.sql`,
`database/feature_expansion.sql`, `database/payment_review_workflow.sql`,
`database/room_capacity_expansion.sql`, and
`database/staff_role_assignments.sql` against the same database. The room
capacity migration adds six rooms to every existing hostel and sets each room
to hold up to five students. Room allocations and releases update occupancy
counts, and full rooms are skipped by allocation. Staff-role assignments allow
hostel roles, mess managers, and finance reviewers to be assigned only to the
area they manage. These scripts can be safely re-run; they add room-change and
pass-out requests, payment verification fields, staff positions, mess
options/cards, and complaint tracking. The feature expansion seeds messes A-H,
five cards per mess, and unpaid fee records for existing students.

Students can view hostel-application decisions and their hostel, room, mess,
payment and profile details on `profile.php`. They can submit payment proof for
review, apply for a mess, request a room change, request to leave the hostel,
submit complaints, and track their requests. The configured payment choices are
Hostel A (Rs 27,000) / G (Rs 13,000) and Mess A (Rs 45,000) / G (Rs 25,000);
the page totals the selected hostel and mess fees. Administrators can set rates
for other hostel and mess options from the payment review screen; unset options
are not offered for payment. Payment is handled offline and is not connected
to a bank or payment gateway. The admin account number is displayed on the
payment page. Screenshots are visible only to administrators and finance
reviewers. Existing payment records are preserved by the migration; legacy
paid records without proof are marked for students to resubmit documentation.

Managers can review room-change, pass-out and mess requests. Administrators can
review and allocate pending hostel room and mess applications from the admin
dashboard, create staff accounts with Warden, Hostel Manager, Mess Manager, and
Finance Reviewer positions, and review payment submissions. Finance reviewers
can review payment submissions.

Room and mess application statuses are `1` (pending), `0` (approved) and `-1`
(rejected). Payment statuses are `0` (unpaid), `2` (pending review), `1`
(approved), and `-1` (rejected).




