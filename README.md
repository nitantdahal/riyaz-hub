# 🎵 Smart Music Practice Tracking System

A full-stack web application for tracking music practice sessions, goals, streaks, and instructor feedback — built with **Core PHP**, **MySQL**, and **plain HTML/CSS/JavaScript** (no frameworks).

Theme: **"Daylight Stage"** — a bright, airy canvas with amber + magenta spotlight accents and a live animated equalizer motif used throughout the interface.

---

## ✨ Features

### 👨‍🎓 Student
- Live practice **timer** (start/pause/reset) that logs directly into a new session
- **Unlimited-length microphone recording** — pressing Start also starts recording your mic; when you click "Log This Session" the recording is automatically attached to the session form, ready to submit
- Manual session logging with instrument, focus area, mood, and notes
- Optional **practice video/audio upload** per session (MP4, MOV, WEBM, OGG, AVI, MKV, MP3, WAV, M4A — up to 150MB) so students can record themselves and review their form over time; files can be watched inline, replaced, or removed from the edit screen
- Full CRUD on practice history
- **Goals** with live progress bars, and automatic **streak** tracking (current + longest)
- **Achievements** — 8 auto-unlocking badges (sessions logged, hours practiced, streak milestones)
- View instructor **feedback (with star ratings)** and complete assigned **tasks**, with on-time/late/overdue badges

### 🎧 Instructor
- Dashboard with weekly activity, pending assignments, and feedback stats
- View all assigned students and their streak/instrument snapshot
- Review practice logs (filterable by student), **watch any attached practice videos/recordings**, and leave **feedback with a 1–5 star rating** per session
- **Student Leaderboard** — ranks your students by a composite score of practice minutes + your ratings + on-time assignment rate (formula shown on the page)
- Create and track **assignments** for students, with automatic on-time/late/overdue detection
- Feedback history log

### 🎛️ Administrator
- Platform-wide dashboard (users, sessions, minutes, instruments)
- Full **user management** (create/edit/delete admins, instructors, students)
- **Instrument catalogue** management
- **Analytics**: 14-day activity chart, top students, mood breakdown, instructor workload
- **Reports**: CSV export, a **structured PDF report per student** (profile, stats, sessions, goals, feedback, assignments, achievements), and a **platform-wide monthly cumulative PDF report** — perfect for running at month-end

### 🔑 Account & Security
- **Email OTP verification on sign-up** — a 6-digit code is emailed to new students; the account is only created after the code is verified, so unverified emails can never log in
- **Forgot password** — request a 6-digit reset code by email, then set a new password
- **Show/hide password** toggles on every password field (login, register, reset, admin user forms)
- Passwords hashed with bcrypt (`password_hash` / `password_verify`)
- CSRF tokens on every form submission
- Role-based route guards (`requireRole()`) on every page
- Prepared statements (PDO) throughout — no raw SQL concatenation

---

## 🗂️ Project Structure

```
music-tracker/
├── admin/                  Admin pages (dashboard, users, instruments, analytics, reports, PDF generation)
├── instructor/              Instructor pages (dashboard, students, logs, leaderboard, assignments, feedback)
├── student/                 Student pages (dashboard, sessions, goals, achievements, feedback)
├── auth/                    login/register/OTP/password-reset processors, logout.php
├── config/
│   ├── database.php          PDO/MySQL connection (edit this with your DB credentials)
│   └── mail.php                SMTP settings for OTP & password-reset emails
├── includes/
│   ├── auth.php               Session, CSRF, role-guard helpers
│   ├── functions.php           Streaks, achievements, chart-data, media & badge helpers
│   ├── otp.php                  OTP generation/storage for registration & password reset
│   ├── mailer.php                 PHPMailer wrapper + dev-mode fallback
│   ├── leaderboard.php             Composite score calculation
│   ├── pdf_helpers.php              FPDF-based report builders
│   ├── sidebar.php                   Shared sidebar/topbar layout (opens <html>)
│   ├── footer.php                     Closes layout, loads main.js
│   ├── PHPMailer/                      Bundled PHPMailer library (no Composer needed)
│   └── FPDF/                            Bundled FPDF library (no Composer needed)
├── assets/
│   ├── css/style.css        The full "Daylight Stage" design system
│   └── js/main.js            Timer, mic recording, password toggles, modals, sidebar toggle
├── database/
│   └── schema.sql             Full schema + seed/demo data
├── uploads/
│   └── practice_videos/       Uploaded/recorded practice media (auto-created)
├── index.php                 Login page
├── register.php               Student self-registration page
├── verify_otp.php               Email OTP verification page
├── forgot_password.php           Request a password reset code
├── reset_password.php             Enter code + set new password
└── README.md
```

---

## ⚙️ Installation

### 1. Requirements
- PHP 8.0+ with the **PDO MySQL** extension enabled
- MySQL 5.7+ / MariaDB 10.3+
- A local server stack: **XAMPP**, **WAMP**, **MAMP**, or `php -S` + a MySQL install

### 2. Get the files onto your server
Unzip this project into your server's web root, e.g.:
- XAMPP: `C:\xampp\htdocs\music-tracker`
- MAMP: `/Applications/MAMP/htdocs/music-tracker`
- Linux/LAMP: `/var/www/html/music-tracker`

### 3. Create the database
Open **phpMyAdmin** (or the `mysql` CLI) and import the schema file:

```bash
mysql -u root -p < database/schema.sql
```

This creates the `riyaz_hub` database, all tables, and demo/seed data.

> Using phpMyAdmin instead? Just create nothing manually — open the **Import** tab,
> choose `database/schema.sql`, and click **Go**. It creates the database for you.

### 4. Configure the database connection
Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'riyaz_hub');
define('DB_USER', 'root');
define('DB_PASS', '');   // set your MySQL password here if you have one
```

### 5. Configure email (for OTP verification & password reset)
Edit `config/mail.php` with your SMTP provider's details:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
```

- **Gmail:** use an ["App Password"](https://myaccount.google.com/apppasswords), not your normal password (requires 2-Step Verification enabled).
- **Local testing:** [Mailtrap](https://mailtrap.io) sandbox SMTP is great for testing without sending real emails.
- **Haven't set this up yet?** No problem — leave the placeholder values as-is. The app automatically falls back to **dev mode**, showing the OTP code directly on the verification page instead of emailing it, so you can still test registration and password reset end-to-end. Set `MAIL_DEV_FALLBACK` to `false` in `config/mail.php` before going to production.

### 6. Run it
- **XAMPP/WAMP/MAMP:** start Apache + MySQL from the control panel, then visit
  `http://localhost/music-tracker/`
- **PHP built-in server** (quick local testing):
  ```bash
  cd music-tracker
  php -S localhost:8000
  ```
  then visit `http://localhost:8000/`

---

## 🔑 Demo Accounts

All seeded accounts use the password: **`Password123`**

| Role       | Email                              |
|------------|-------------------------------------|
| Admin      | admin@musictrack.com                |
| Instructor | sarah.instructor@musictrack.com     |
| Instructor | daniel.instructor@musictrack.com    |
| Student    | emma.student@musictrack.com         |
| Student    | liam.student@musictrack.com         |

New students can also self-register from the **Create an account** link on the login
page — this requires verifying a 6-digit code sent to their email. If you haven't
configured SMTP yet (see "Configure email" above), the code is shown directly on
screen in dev mode so registration still works end-to-end for testing.

---

## 🎥 Practice Video Uploads

Students can optionally attach a short video recording to any practice session
(handy for tracking hand position, posture, or performance over time).

- Files are stored on disk under `uploads/practice_videos/` (created automatically
  on first upload) and the relative path is saved in `practice_sessions.video_path`.
- Accepted formats: **MP4, MOV, WEBM, OGG, AVI, MKV**. Max size: **100MB** per file
  (adjust `VIDEO_MAX_BYTES` in `includes/functions.php` if you need a different limit).
- Deleting a session, replacing its video, or checking "Remove current video" on the
  edit screen all clean up the old file from disk automatically.
- Instructors can watch a student's attached video directly from **Practice Logs**.

**Important — PHP upload limits:** PHP's default `php.ini` usually caps uploads at
2MB, which is too small for real video files. To support larger uploads, edit your
`php.ini` (or add a `.htaccess`/`.user.ini` override) and increase:

```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 128M
max_execution_time = 300
```

Then restart Apache/PHP. If a video upload silently fails, this is almost always
the cause — check `upload_max_filesize` and `post_max_size` first.

> **Already imported the database before this feature was added?** Run this once to
> add the new column without losing existing data:
> ```sql
> ALTER TABLE practice_sessions ADD COLUMN video_path VARCHAR(255) DEFAULT NULL;
> ```

> **Upgrading from an earlier version of this project?** Run these once to bring an
> existing database up to date without losing data:
> ```sql
> ALTER TABLE feedback ADD COLUMN rating TINYINT DEFAULT NULL;
> ALTER TABLE assignments ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL;
> CREATE TABLE IF NOT EXISTS otp_verifications (
>   id INT AUTO_INCREMENT PRIMARY KEY,
>   email VARCHAR(150) NOT NULL,
>   otp_code VARCHAR(10) NOT NULL,
>   full_name VARCHAR(120) NOT NULL,
>   password_hash VARCHAR(255) NOT NULL,
>   instrument_id INT DEFAULT NULL,
>   instructor_id INT DEFAULT NULL,
>   attempts INT NOT NULL DEFAULT 0,
>   expires_at DATETIME NOT NULL,
>   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
>   INDEX idx_otp_email (email)
> );
> CREATE TABLE IF NOT EXISTS password_resets (
>   id INT AUTO_INCREMENT PRIMARY KEY,
>   email VARCHAR(150) NOT NULL,
>   otp_code VARCHAR(10) NOT NULL,
>   attempts INT NOT NULL DEFAULT 0,
>   expires_at DATETIME NOT NULL,
>   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
>   INDEX idx_reset_email (email)
> );
> ```

---

## 🏆 Leaderboard Scoring

Each instructor sees their own students ranked by a composite score:

```
score = total_practice_minutes
      + (average_teacher_rating_out_of_5 × 20)
      + (on_time_assignment_percentage × 0.5)
```

- **Practice minutes** — the raw, all-time total from logged sessions.
- **Average rating** — the average of the 1–5 star ratings an instructor gives alongside feedback (only that instructor's ratings count).
- **On-time rate** — of all assignments with a due date that are either completed or overdue, the percentage that were completed on or before their due date.

The formula and each student's raw metrics (minutes, rating, on-time %) are shown directly on the leaderboard page so the ranking is always transparent.

## 🎙️ Voice Recording in the Practice Timer

Clicking **Start** on the student dashboard timer also starts recording the student's microphone (unlimited duration, no time cap) using the browser's MediaRecorder API. Clicking **"Log This Session"** stops the recording and automatically attaches it to the Add Session form as a file — the student just fills in the remaining details and submits.

**Important:** browsers only allow microphone access (`getUserMedia`) on **secure contexts** — that means `https://` or `localhost`. If you deploy this to a live domain, it must be served over HTTPS or the recording feature will silently be unavailable (the timer itself still works fine, just without recording). `http://localhost/...` and `php -S localhost:8000` both work for local testing without HTTPS.

## 📄 PDF Reports (Admin)

From **Admin → Reports**:
- **Per-student report** — click "📄 PDF Report" next to any student for a structured PDF covering their profile, summary stats, full session history, goals, feedback (with ratings), assignments (with on-time/late status), and achievements.
- **Monthly cumulative report** — pick a month and generate a platform-wide PDF summarizing every student's sessions and minutes for that month, ideal to run right after month-end.

Both are built with the bundled FPDF library (pure PHP, no external dependencies or binaries required).

## 🧩 Database Schema Overview

| Table               | Purpose                                                |
|----------------------|---------------------------------------------------------|
| `users`              | Admins, instructors, students (role column + FKs)      |
| `instruments`        | Instrument catalogue (name, icon, description)          |
| `practice_sessions`  | Logged practice sessions (duration, mood, notes, optional video_path) |
| `goals`              | Student practice goals + progress                        |
| `streaks`            | One row per student — current & longest streak            |
| `feedback`           | Instructor → student notes + 1-5 star rating, linked to a session (optional)|
| `assignments`        | Instructor → student tasks, with completed_at for on-time tracking |
| `achievements`       | Unlocked badges per student                                |
| `otp_verifications`  | Pending student registrations awaiting email OTP confirmation |
| `password_resets`    | Pending forgot-password requests awaiting email OTP confirmation |

---

## 🎨 Design System Notes

The entire UI shares one stylesheet (`assets/css/style.css`) built around CSS custom
properties (`--gold`, `--magenta`, `--cyan`, etc.), so re-theming the app means editing
a handful of variables at the top of the file. The recurring **equalizer bars**
(`.eq-bars`) component is used as a loading/ambient motif across login, dashboards,
section dividers, and the live practice timer.

---

## 🛠️ Troubleshooting

- **"Database Connection Failed"** — double-check `config/database.php` credentials
  and that MySQL is running.
- **Blank page / 500 error** — enable PHP error display temporarily by adding
  `ini_set('display_errors', 1); error_reporting(E_ALL);` to the top of `index.php`.
- **Never receive the OTP email** — check `config/mail.php`; if it still has placeholder
  values, dev-mode is active and the code appears directly on the verification page
  instead (also logged to `uploads/otp_log.txt`). With real SMTP configured, check
  spam folders and confirm the account isn't blocking "less secure" SMTP logins.
- **Voice recording doesn't start** — the browser blocked microphone access. This
  happens automatically on any origin that isn't `https://` or `localhost` — deploy
  behind HTTPS in production. Also check the browser's own site permissions for mic access.
- **PDF report downloads as a blank/broken file** — make sure `includes/FPDF/font/`
  wasn't excluded when copying the project (it holds the core font metrics FPDF needs).
- **"Could not send email"** — verify `SMTP_HOST`/`SMTP_PORT`/`SMTP_ENCRYPTION` match
  your provider exactly (e.g. Gmail is host `smtp.gmail.com`, port `587`, encryption `tls`).
- **Styles look unstyled** — make sure you're serving the app through a URL like
  `http://localhost/music-tracker/` rather than opening the `.php` files directly
  from disk (PHP must be processed by a server).
#   R i y a z _ H u b  
 