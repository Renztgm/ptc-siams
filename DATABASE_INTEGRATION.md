# PTC Admission System - Database Integration Guide

## Overview

The admission system has been fully integrated with database functionality. Students can now submit applications, and admins can manage exam configurations and send bulk email communications - all backed by database records.

## System Architecture

### Database Layer
- **Database**: MySQL/MariaDB
- **Configuration**: [db_config.php](db_config.php)
- **Schema**: [PTC_Database.sql](PTC_Database.sql)
- **Tables**: 7 main tables (see DATABASE_SETUP.txt)

### API Endpoints

#### 1. **get_programs.php** ✨ NEW
Fetches available programs for dropdown filters.
```
GET /get_programs.php
Returns: { success, programs[] }
```
- Used by: `admin_exam_config.html` program filter
- Replaces hardcoded program list
- Has fallback to hardcoded list if database unavailable

#### 2. **get_admitted_students.php** ✨ NEW
Fetches admitted students with complete details.
```
GET /get_admitted_students.php?program=BS%20IT
Returns: { success, students[], count }
Query Parameters:
  - program (optional): Filter by program name
  - status (optional): Filter by status (default: "admitted")
```
- Used by: `admin_exam_config.html` bulk email loading
- Includes: full_name, email, program, exam_link_sent status
- Replaces the `get_students` endpoint pattern

#### 3. **send_exam_link_bulk.php** (Updated)
Handles bulk email sending and scheduling.
```
POST /send_exam_link_bulk.php
Action: send_emails | schedule_emails
```
- Sends immediate or scheduled emails
- Updates database with email_sent_date and exam_link_sent flag
- Maintains backward compatibility with `get_students` action as fallback

#### 4. **get_scheduled_emails.php**
Manages scheduled email batches.
```
GET /get_scheduled_emails.php?filter=pending|sent|failed
POST /get_scheduled_emails.php?action=cancel&batch_id=xxxxx
```
- Lists pending, sent, and failed email batches
- Allows cancellation of pending batches
- Used by: `view_scheduled_emails.html`

#### 5. **scheduler_check_emails.php**
Automatic email scheduler (runs periodically).
```
GET /scheduler_check_emails.php
GET /scheduler_check_emails.php?debug=1  (Shows detailed logs)
```
- Runs via Windows Task Scheduler or Linux cron
- Checks `/scheduled_emails/` folder
- Sends emails when scheduled time passes
- Updates database with email_sent_date

#### 6. **manage_exams.php**
Exam session and results management.
```
GET /manage_exams.php?action=get_sessions|get_registrations|get_results|get_stats
POST /manage_exams.php?action=create_session|save_results
```
- Creates exam sessions
- Registers students for exams
- Records attendance and scores
- Calculates pass/fail status

### Component Hierarchy

```
┌─ admin_exam_config.html (Main Admin Dashboard)
│  ├─ get_programs.php          (Dynamic program list)
│  ├─ get_admitted_students.php (Dynamic student list)
│  ├─ exam_config.php           (Load/save exam settings)
│  └─ send_exam_link_bulk.php   (Send immediate/scheduled emails)
│
├─ view_scheduled_emails.html (Scheduler Monitor)
│  ├─ get_scheduled_emails.php  (List/cancel batches)
│  └─ scheduler_check_emails.php (Manual trigger)
│
├─ manage_exams.html (Exam Management)
│  └─ manage_exams.php          (Session/results API)
│
└─ Register.html (Student Application)
   ├─ exam_config.php           (Dynamic exam details)
   ├─ save_admission.php        (Save application)
   └─ send_admission_email.php  (Confirmation email)
```

## Data Flow

### Scenario 1: Student Submission → Admin Sends Exam Link

```
1. Student fills Register.html form
   ↓
2. JavaScript calls exam_config.php for exam details
   (Displays in form preview)
   ↓
3. Form submitted to save_admission.php
   ↓
4. save_admission.php:
   - Inserts record into admissions table
   - Calls send_admission_email.php
   ↓
5. Confirmation email sent to student
   ↓
6. Admin logs into admin_exam_config.html
   ↓
7. Admin clicks "Load Students"
   ↓
8. JavaScript calls get_admitted_students.php
   - Returns all admitted students from DB
   ↓
9. Admin selects students and chooses "Send Now"
   ↓
10. Form posts to send_exam_link_bulk.php
   ↓
11. send_exam_link_bulk.php:
    - For each student, sends exam link email
    - Updates admissions table: email_sent_date, exam_link_sent = 1
   ↓
12. Admin sees success message and updated statistics
```

### Scenario 2: Scheduled Email Sending

```
1. Admin selects students and chooses "Schedule for Later"
   ↓
2. Form posts to send_exam_link_bulk.php (action=schedule_emails)
   ↓
3. Script creates JSON file in /scheduled_emails/ folder:
   - batch_id: unique identifier
   - scheduled_time: ISO datetime
   - emails: array of addresses
   - status: "pending"
   ↓
4. Admin confirmation message
   (Email scheduled for 2026-06-15 at 09:00 AM)
   ↓
5. Scheduler runs periodically (via cron/Task Scheduler)
   ↓
6. scheduler_check_emails.php executes:
   - Scans /scheduled_emails/ folder
   - Checks if current time >= scheduled_time
   - For pending batches that are due:
     - Sends emails
     - Updates database
     - Updates batch status to "sent"
   ↓
7. Admin views view_scheduled_emails.html
   ↓
8. JavaScript calls get_scheduled_emails.php
   - Returns batch status and history
```

### Scenario 3: Exam Results Entry

```
1. Admin logs into manage_exams.html
   ↓
2. Clicks "Results" tab
   ↓
3. JavaScript calls manage_exams.php (action=get_results)
   - Retrieves registrations needing score entry
   ↓
4. Admin enters attendance and score
   - Clicks "Save Results"
   ↓
5. Form posts to manage_exams.php (action=save_results)
   ↓
6. Script:
   - Updates exam_registrations table
   - Calculates pass/fail (score >= passing_score)
   - Logs to system_logs
   ↓
7. Results tab auto-refreshes to show updated data
```

## Database Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| **admissions** | Student applications | admission_id, email, exam_link_sent, email_sent_date |
| **exam_sessions** | Scheduled exams | exam_date, start_time, duration, capacity |
| **exam_registrations** | Student-exam assignments | student_admission_id, exam_id, score, pass_fail |
| **email_logs** | Audit trail | recipient_email, subject, sent_timestamp |
| **admission_stats** | Quick statistics | total_admitted, exam_links_sent, exams_scheduled |
| **system_logs** | Admin actions | action, admin_id, timestamp |
| **programs** | Available programs | program_name, description, capacity |

## Configuration Files

### db_config.php
Database connection details. Supports:
- Local development (localhost)
- InfinityFree (cpanel hosting)
- Azure MySQL

```php
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'ptc_database';
```

### exam_config.php
Exam settings (loaded by dynamic forms). Can be updated via:
- Admin panel → Save Configuration button
- Direct JSON edit

### /.env (Future)
Currently hardcoded, consider moving to .env:
- SMTP credentials
- Admin password
- Database credentials

## Security Considerations

### Authentication
- **Admin operations**: Require `ptc_admin_2026` password
- **Student operations**: Admission ID verification available
- **API calls**: No external authentication (IP whitelist optional)

### Data Protection
- Passwords: Hashed (bcrypt recommended for future users)
- Sensitive data: Email, admission details only in database
- Logs: Full audit trail in system_logs table

### Email Security
- **SMTP**: Gmail App Password (not main password)
- **TLS**: Encrypted connection to smtp.gmail.com:587
- **Headers**: Proper MIME headers to prevent injection

### Upcoming Improvements
- [ ] Move credentials to environment variables
- [ ] Hash admin password in database
- [ ] Add IP whitelist for scheduled task
- [ ] Implement OAuth for admin login
- [ ] Database user with limited privileges

## Troubleshooting

### Issue: "Database connection failed"
**Solution**: Check db_config.php credentials
```php
verify $servername, $username, $password, $dbname
```

### Issue: Students not appearing in dropdown
**Solution**: 
1. Check if `get_admitted_students.php` exists
2. Verify admissions table has data
3. Check browser console for fetch errors
4. Fallback should load 404 page (hardcoded list)

### Issue: "Program filter not working"
**Solution**:
1. Verify programs table has data
2. Check if get_programs.php returns success
3. Fallback to hardcoded list in loadProgramsDropdown()

### Issue: "Scheduled emails not sending"
**Solution**:
1. Verify scheduler is running (check Task Scheduler icon)
2. Check error logs in /tmp/ or Windows Event Viewer
3. Verify /scheduled_emails/ folder exists and readable
4. Run scheduler_check_emails.php manually: `php scheduler_check_emails.php?debug=1`

## Future Enhancements

1. **User Authentication**
   - Admin login page
   - Role-based access control
   - Password reset functionality

2. **Reporting Dashboard**
   - Admission funnel analysis
   - Email delivery statistics
   - Exam performance analytics
   - Program comparison reports

3. **Advanced Scheduling**
   - Recurring email campaigns
   - Student segmentation
   - A/B testing email templates
   - Drip campaigns

4. **Integration**
   - SMS notifications
   - SMS-based exam reminders
   - Slack/Discord webhooks for admin alerts
   - Calendar integration (Google Calendar export)

5. **Student Portal**
   - View admission status
   - Update personal information
   - Download documents
   - Track exam progress

## Quick Start

### 1. Setup Database
```bash
# Import schema
mysql -u root -p ptc_database < PTC_Database.sql

# Verify tables
mysql -u root -p ptc_database -e "SHOW TABLES;"
```

### 2. Configure Connection
Edit `db_config.php` with your database credentials

### 3. Create Initial Programs
```sql
INSERT INTO programs (program_name, description) VALUES
('BS Information Technology', 'Bachelor of Science in Information Technology'),
('BS Business Administration', 'Bachelor of Science in Business Administration'),
...
```

### 4. Setup Admission Form
- File: [Register.html](Register.html)
- Submits to: [save_admission.php](save_admission.php)
- Sends confirmation via: [send_admission_email.php](send_admission_email.php)

### 5. Setup Admin Panel
- File: [admin_exam_config.html](admin_exam_config.html)
- APIs: [get_programs.php](get_programs.php), [get_admitted_students.php](get_admitted_students.php)
- Required password: `ptc_admin_2026`

### 6. Setup Scheduler
See [SCHEDULER_SETUP.txt](SCHEDULER_SETUP.txt) for Windows Task Scheduler or cron setup

## Architecture Benefits

✅ **Scalable**: Database supports thousands of students
✅ **Maintainable**: Clear separation of concerns (API endpoints)
✅ **Flexible**: Easy to modify exam config without code changes
✅ **Auditable**: Complete email and admin action logs
✅ **Resilient**: Fallback mechanisms if database unavailable
✅ **Professional**: Automated scheduling, bulk operations
✅ **Secure**: Password protection, proper data validation

---

**Document Version**: 1.5
**Last Updated**: 2026-01-06
**Status**: Complete and Integrated
