# Quick Reference Card

## Admin URLs

| Interface | URL | Purpose |
|-----------|-----|---------|
| Admission Form | `http://localhost/arquero_sofia/index/Register.html` | Student application submission |
| Admin Panel | `http://localhost/arquero_sofia/index/admin_exam_config.html` | Exam config + bulk email |
| Exam Management | `http://localhost/arquero_sofia/index/manage_exams.html` | Sessions, registration, results |
| Scheduler Monitor | `http://localhost/arquero_sofia/index/view_scheduled_emails.html` | View pending/sent batches |
| Student Portal | `http://localhost/arquero_sofia/index/Student_portal.html` | Student dashboard |

---

## API Endpoints Quick Reference

### GET Endpoints (No Password)

```bash
# Get available programs
curl "http://localhost/arquero_sofia/index/get_programs.php"

# Get admitted students (optionally filter by program)
curl "http://localhost/arquero_sofia/index/get_admitted_students.php"
curl "http://localhost/arquero_sofia/index/get_admitted_students.php?program=BS%20IT"

# Get exam configuration
curl "http://localhost/arquero_sofia/index/exam_config.php?json"

# Get scheduled email batches
curl "http://localhost/arquero_sofia/index/get_scheduled_emails.php"
curl "http://localhost/arquero_sofia/index/get_scheduled_emails.php?filter=pending"

# Run scheduler (manual trigger)
curl "http://localhost/arquero_sofia/index/scheduler_check_emails.php"
curl "http://localhost/arquero_sofia/index/scheduler_check_emails.php?debug=1"
```

### POST Endpoints (Require Password)

```bash
# Send exam links immediately
curl -X POST "http://localhost/arquero_sofia/index/send_exam_link_bulk.php" \
  -d "action=send_emails" \
  -d "emails=[\"student1@example.com\",\"student2@example.com\"]" \
  -d "password=ptc_admin_2026"

# Schedule exam links for later
curl -X POST "http://localhost/arquero_sofia/index/send_exam_link_bulk.php" \
  -d "action=schedule_emails" \
  -d "emails=[\"student1@example.com\"]" \
  -d "schedule_date=2026-01-20" \
  -d "schedule_time=10:00" \
  -d "password=ptc_admin_2026"

# Save exam results
curl -X POST "http://localhost/arquero_sofia/index/manage_exams.php" \
  -d "action=save_results" \
  -d "registration_id=123" \
  -d "score=85" \
  -d "attendance=1" \
  -d "password=ptc_admin_2026"
```

---

## Key Credentials

| Component | Value |
|-----------|-------|
| **Admin Password** | `ptc_admin_2026` |
| **Gmail SMTP Email** | arquero.sofia.tcu@gmail.com |
| **Gmail App Password** | qjpf wvol cpgq tsoa |
| **SMTP Server** | smtp.gmail.com |
| **SMTP Port** | 587 |
| **SMTP Encryption** | TLS |
| **Default Passing Score** | 60 |
| **Contact Email** | arquero.sofia.tcu@gmail.com |

---

## Common Tasks

### Task 1: Update Exam Details
1. Login: Open [admin_exam_config.html](admin_exam_config.html)
2. Section 1: Edit exam date, time, location, link
3. Click "💾 Save Configuration"
4. Enter password: `ptc_admin_2026`
5. ✅ Settings saved and will appear in admission forms

### Task 2: Send Exam Link to Students
1. Open [admin_exam_config.html](admin_exam_config.html)
2. Section 2: Click "🔄 Load Students"
3. Select students with checkboxes (or "✓ Select All")
4. Verify count in stats
5. Choose "Send Now" (immediate) or "Schedule for Later"
6. Click "📤 Send Exam Links"
7. Enter password: `ptc_admin_2026`
8. ✅ Emails sent/scheduled

### Task 3: Record Exam Scores
1. Open [manage_exams.html](manage_exams.html)
2. Click "Results" tab
3. Find student in list
4. Enter: Score (0-100) and Attendance checkbox
5. Click "Save Results"
6. Enter password: `ptc_admin_2026`
7. ✅ Score saved (auto-calculates pass/fail)

### Task 4: Monitor Scheduled Emails
1. Open [view_scheduled_emails.html](view_scheduled_emails.html)
2. Page auto-refreshes every 30 seconds
3. View tabs: Pending | Sent | Failed
4. To cancel pending batch: Click ❌ button
5. Page shows batch IDs, count, scheduled time

### Task 5: Query Admitted Students
1. Method A - Web Interface:
   - Open [admin_exam_config.html](admin_exam_config.html)
   - Click "Load Students" button
   - Filter by program if needed

2. Method B - Database Query:
   ```sql
   SELECT admission_id, given_name, last_name, email, program, exam_link_sent 
   FROM admissions 
   WHERE status = 'admitted' 
   ORDER BY submission_date DESC;
   ```

### Task 6: Check Email Delivery Status
1. Open Database Query Tool
2. Run:
   ```sql
   SELECT recipient_email, subject, status, sent_timestamp 
   FROM email_logs 
   ORDER BY sent_timestamp DESC 
   LIMIT 20;
   ```
3. Status values: "sent", "failed", "pending"

### Task 7: Setup Automatic Scheduler

**Windows Task Scheduler:**
1. Open Task Scheduler
2. Create Basic Task: "PTC Email Scheduler"
3. Trigger: Daily, every 1 minute
4. Action: Run program
5. Program: `C:\xampp\php\php.exe` or PHP path
6. Arguments: `C:\path\to\scheduler_check_emails.php`
7. ✅ Task created

**Linux Cron:**
```bash
# Edit crontab
crontab -e

# Add line (runs every 1 minute):
* * * * * /usr/bin/php /var/www/html/scheduler_check_emails.php > /dev/null 2>&1

# Save and exit
# ✅ Scheduler running
```

---

## Database Quick Commands

### Connect to Database
```bash
mysql -u root -p ptc_database
```

### View All Admitted Students
```sql
SELECT admission_id, given_name, last_name, email, program, submission_date 
FROM admissions 
WHERE status = 'admitted' 
ORDER BY submission_date DESC;
```

### View Email Statistics
```sql
SELECT 
    COUNT(*) as total_sent,
    COUNT(CASE WHEN status='sent' THEN 1 END) as success,
    COUNT(CASE WHEN status='failed' THEN 1 END) as failed
FROM email_logs;
```

### View Exam Results
```sql
SELECT 
    a.admission_id,
    CONCAT(a.given_name, ' ', a.last_name) as name,
    a.program,
    er.score,
    er.pass_fail,
    er.attended
FROM exam_registrations er
JOIN admissions a ON er.student_admission_id = a.admission_id
ORDER BY er.updated_at DESC;
```

### View System Audit Log
```sql
SELECT * FROM system_logs 
ORDER BY timestamp DESC 
LIMIT 10;
```

### Count Students by Program
```sql
SELECT program, COUNT(*) as count 
FROM admissions 
WHERE status = 'admitted' 
GROUP BY program;
```

---

## Troubleshooting Quick Fixes

| Problem | Quick Fix |
|---------|-----------|
| Programs dropdown empty | Run: `SELECT * FROM programs;` - if empty, INSERT test programs |
| Students not loading | Check: `SELECT COUNT(*) FROM admissions WHERE status='admitted';` |
| Emails not sending | Test: `php send_exam_link_bulk.php` from command line |
| Scheduler not running | Verify: Task Scheduler or cron job is enabled |
| "Connection refused" | Check: MySQL service is running |
| "File permission denied" | Fix: `chmod 755 /scheduled_emails/ /admissions/ /logs/` |
| "Password incorrect" | Verify: Password is exactly `ptc_admin_2026` (case-sensitive) |

---

## Key Files & Locations

| File | Purpose | Editable |
|------|---------|----------|
| db_config.php | Database credentials | ✅ Yes |
| exam_config.php | Exam settings | ✅ Editable via admin panel |
| send_exam_link_bulk.php | Email sending logic | ⚠️ Only if modifying email template |
| admin_exam_config.html | Admin interface | ⚠️ Only for styling changes |
| Register.html | Student form | ⚠️ Only for validation changes |
| /scheduled_emails/ | Batch storage | ❌ Auto-managed by system |
| /admissions/ | PDF storage | ❌ Auto-managed by system |

---

## Important Notes

1. **Password Protection**: All admin operations require `ptc_admin_2026` password
2. **Email Provider**: Gmail only - other email services need SMTP configuration changes
3. **Scheduler**: Must be configured separately (Task Scheduler or cron)
4. **Backups**: Regularly backup MySQL database and /admissions/ folder with PDFs
5. **HTTPS**: Recommended for production (security for password transmission)
6. **Rate Limiting**: 0.5 second delay between emails to avoid Gmail rate limits
7. **Database**: Supports MySQL 5.7+, MariaDB 10.3+

---

## When Something Breaks

1. **Check error logs**:
   ```bash
   tail -f /var/log/php.log  # Linux
   # or Windows Event Viewer
   ```

2. **Test database connection**:
   ```bash
   mysql -u root -p -e "SELECT 1 FROM admissions LIMIT 1;"
   ```

3. **Check PHP errors**:
   Open browser DevTools (F12) → Network tab → Check POST responses

4. **Test email manually**:
   ```php
   php -r "mail('test@example.com', 'Test', 'Body', 'From: admin@test.com');"
   ```

5. **Look at system logs**:
   ```sql
   SELECT * FROM system_logs ORDER BY timestamp DESC LIMIT 5;
   ```

---

## Version Information

| Component | Version |
|-----------|---------|
| PHP | 7.4+ |
| MySQL | 5.7+ or MariaDB 10.3+ |
| JavaScript | ES6 (modern browsers) |
| jsPDF | Latest |
| QRCode.js | Latest |

---

## Document Info

- **Created**: 2026-01-06
- **Last Updated**: 2026-01-06
- **Status**: Current
- **Purpose**: Quick reference for daily admin tasks
