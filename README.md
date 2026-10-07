# Academic Clearance System

**“One Request. Multiple Departments. One Digital Certificate.”**

A complete web-based Academic Clearance System built with **HTML5, CSS3, JavaScript, PHP & MySQL** only (no frameworks).

---

## Features

- Student registration & login
- Submit one clearance request (Graduation / Transfer / Semester / Other)
- 5 independent departments (Library, Dormitory, Bursary, Cafeteria, Sports)
- Approve / Reject with mandatory remarks
- Re-application after rejection
- Smart status (In Progress → Action Required → Ready for Final → Completed)
- Progress tracking & timeline
- Outstanding dues
- Notifications
- Admin / Registrar final approval
- Digital clearance certificate (print / Save as PDF)
- Public certificate verification
- User management, analytics, audit logs
- Role-based access, CSRF, password hashing, prepared statements

---

## Requirements

- PHP 7.4+ (with PDO MySQL)
- MySQL 5.7+ / MariaDB 10.3+
- Apache / Nginx (or PHP built-in server)
- Modern browser

---

## Installation

### 1. Place the project

Copy the `academic-clearance` folder into your web root, for example:

- XAMPP: `C:\xampp\htdocs\academic-clearance`
- WAMP: `C:\wampp\www\academic-clearance`
- Linux: `/var/www/html/academic-clearance`

### 2. Create the database

1. Open phpMyAdmin or MySQL CLI
2. Import the file:  
   `database/academic_clearance.sql`

   Or run:

```bash
mysql -u root -p < database/academic_clearance.sql
```

### 3. Configure database connection

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // your MySQL password
define('DB_NAME', 'academic_clearance');
```

Also set the correct base URL:

```php
define('BASE_URL', '/academic-clearance');  // change if your folder name is different
```

### 4. Run the application

Open in browser:

```
http://localhost/academic-clearance/
```

---

## Demo Accounts

| Role       | Email                    | Password  |
|------------|--------------------------|-----------|
| Student    | shreya@student.edu       | password  |
| Library    | library@college.edu      | password  |
| Dormitory  | dormitory@college.edu    | password  |
| Bursary    | bursary@college.edu      | password  |
| Cafeteria  | cafeteria@college.edu    | password  |
| Sports     | sports@college.edu       | password  |
| Admin      | admin@college.edu        | password  |

Student Number for demo: **30** (BCA 2024)

---

## Demo Scenario (Competition)

1. Login as **shreya@student.edu** → Submit **Graduation Clearance**
2. Login as **library@college.edu** → Approve
3. Login as **sports@college.edu** → Approve
4. Login as **bursary@college.edu** → Reject with remark: “Rs. 5,000 tuition fee is pending.”
5. Login as Student → See rejection → Re-Apply
6. Login as Bursary again → Approve
7. Approve remaining departments (Dormitory + Cafeteria)
8. Login as **admin@college.edu** → Final Approve → Certificate generated
9. View / Print the certificate
10. Open public verification page and enter the certificate number

---

## Folder Structure

```
academic-clearance/
├── index.php
├── config/database.php
├── auth/          (login, register, logout, set_password)
├── student/       (dashboard, new_request, clearance, timeline, notifications, certificates)
├── departments/   (dashboard, pending, review, history)
├── admin/         (dashboard, final_approval, users, students, analytics, audit_logs)
├── certificate/   (view.php)
├── public/        (verify.php)
├── includes/      (header, footer, sidebar, functions, auth_check)
├── assets/        (css, js, images)
└── database/      (academic_clearance.sql)
```

---

## Security Features Implemented

- `password_hash()` / `password_verify()`
- PDO prepared statements
- `htmlspecialchars()` output escaping
- PHP sessions + role-based access control
- CSRF tokens on critical forms
- Session timeout (30 min)
- Account activation / deactivation
- Audit logging

---

## Notes

- Certificate is HTML/CSS based. Use browser **Print → Save as PDF**.
- No external libraries or frameworks are used.
- For production, change default passwords and set a strong `DB_PASS`.
- You can add more students via the registration page.

---

**Tagline:** Submit Once → Process in Parallel → Approve Digitally → Generate Certificate → Verify Anywhere.
