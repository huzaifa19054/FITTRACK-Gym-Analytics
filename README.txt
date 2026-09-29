FITTRACK GYM MANAGEMENT SYSTEM
Current build: Admin Portal + Members CRUD + Trainers CRUD + Membership Plans CRUD + Memberships

TECH:
PHP + MySQL + XAMPP

SETUP:
1. Copy this folder into C:\xampp\htdocs\FitTrack-Gym
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin.
4. Import database/fittrack_gym.sql.
5. Open http://localhost/FitTrack-Gym/database/seed.php once.
6. Open http://localhost/FitTrack-Gym/login.php

DEMO:
Admin: admin / password
Trainer: trainer / password
Member: member / password

CURRENT ADMIN MODULES:
Dashboard, Members CRUD, Trainers CRUD, Membership Plans CRUD, Memberships.
Prepared pages: Payments, Attendance, Workout Plans, Diet Plans, Classes.

Memberships supports:
Assign active plans to members, automatic end-date calculation, edit,
automatic expiration, cancel and re-activate cancelled memberships.

All PHP files in this build were syntax checked.

PORTALS ADDED - NEXT BUILD
---------------------------
Trainer Portal: dashboard, My Members, Workout Plans, Diet Plans, Attendance, Classes, Progress, Profile.
Member Portal: dashboard, My Membership, My Attendance, My Workout, My Diet, My Payments, Classes, Progress, Profile.
Both portals use the same PHP/MySQL database, role-based session protection, responsive admin UI, and Light/Dark theme toggle.
Demo accounts: trainer / password, member / password.

NEXT BUILD UPDATE
------------------
Admin dashboard now surfaces expiring memberships, pending payments and new contact messages.
Reports supports CSV export for payments and member activity for the selected date range.
