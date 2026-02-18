# Quick Verification Guide

## Before You Start

This guide helps you verify that all components are properly integrated and working.

---

## Step 1: Database Verification

### Check if Database Exists
```bash
# Connect to MySQL
mysql -u root -p ptc_database

# List all tables (should show 7)
SHOW TABLES;

# Expected tables:
# - admissions
# - exam_sessions
# - exam_registrations
# - email_logs
# - system_logs
# - programs
# - admission_stats
```

### If Database Missing
```bash
# Import the schema
mysql -u root -p ptc_database < PTC_Database.sql
```

### Add Test Program
```sql
INSERT INTO programs (program_name, description, capacity) VALUES 
('BS Information Technology', 'Bachelor of Science in Information Technology', 100),
('BS Business Administration', 'Bachelor of Science in Business Administration', 80);

SELECT * FROM programs;
```

---

## Step 2: API Verification

### Test 1: Check get_programs.php
**URL**: `http://localhost/arquero_sofia/index/get_programs.php`

**Expected Response**:
```json
{
    "success": true,
    "programs": [
        {
            "program_name": "BS Information Technology"
        },
        {
            "program_name": "BS Business Administration"
        }
    ]
}
```

**If Failed**:
- Check db_config.php credentials
- Verify database connection
- Check programs table has data

### Test 2: Check get_admitted_students.php (with test data)
**First, add a test student**:
```sql
INSERT INTO admissions 
(admission_id, given_name, last_name, email, program, status, exam_link_sent) 
VALUES 
('PTC-20260106-0001', 'Test', 'Student', 'test@example.com', 'BS Information Technology', 'admitted', 0);
```

**URL**: `http://localhost/arquero_sofia/index/get_admitted_students.php`

**Expected Response**:
```json
{
    "success": true,
    "students": [
        {
            "id": 1,
            "admission_id": "PTC-20260106-0001",
            "given_name": "Test",
            "last_name": "Student",
            "full_name": "Test Student",
            "email": "test@example.com",
            "program": "BS Information Technology",
            "exam_link_sent": 0,
            "email_sent_date": null
        }
    ],
    "count": 1
}
```

**Variations to Test**:
- With program filter: `?program=BS%20IT`
- Should only return 1 result

### Test 3: Check exam_config.php
**URL**: `http://localhost/arquero_sofia/index/exam_config.php?json`

**Expected Response**:
```json
{
    "exam_date": "2026-06-01",
    "exam_start_time": "09:00",
    "exam_end_time": "12:00",
    "exam_format": "Online",
    "exam_location": "PTC Grounds",
    "exam_link": "https://example.com/exam",
    "exam_link_description": "Check your email for credentials"
}
```

---

## Step 3: Admin Panel Verification

### Test 1: Load Programs in Admin Panel
1. Open [admin_exam_config.html](http://localhost/arquero_sofia/index/admin_exam_config.html)
2. Check "Filter by Program" dropdown
3. **Expected**: Should show "BS Information Technology", "BS Business Administration"
4. **What Happens**:
   - Page loads and calls `loadProgramsDropdown()`
   - JavaScript fetches from `get_programs.php`
   - Dynamically populates dropdown options

### Test 2: Load Students in Admin Panel
1. In [admin_exam_config.html](http://localhost/arquero_sofia/index/admin_exam_config.html)
2. Click "🔄 Load Students" button
3. **Expected**: Shows "Loaded 1 students"
4. **What You'll See**:
   - Student listed with name and email
   - Checkbox for selection
   - Email status

### Test 3: Check Exam Configuration
1. Click "📥 Load Current Settings"
2. **Expected**: Form fields populate with exam details
3. **Verify**: Can save configuration with password "ptc_admin_2026"

---

## Step 4: Database Integration Verification

### Verify Data Flow: Student Submission
1. Open [Register.html](http://localhost/arquero_sofia/index/Register.html)
2. Fill out form completely
3. Submit
4. Check database:
```sql
SELECT admission_id, given_name, last_name, email, program, status 
FROM admissions 
WHERE status = 'admitted';
```
**Expected**: New student record visible

### Verify Data Flow: Email Send
1. In admin panel, select student and click "Send Exam Links"
2. Enter password: `ptc_admin_2026`
3. Check database:
```sql
SELECT email, exam_link_sent, email_sent_date 
FROM admissions 
WHERE email = 'test@example.com';
```
**Expected**: `exam_link_sent = 1` and `email_sent_date` populated

### Verify Email Logs
```sql
SELECT * FROM email_logs 
ORDER BY sent_timestamp DESC 
LIMIT 1;
```
**Expected**: Latest email visible with timestamp

---

## Step 5: Scheduled Email Verification

### Verify Scheduler Files Exist
1. Check if `/scheduled_emails/` folder exists and is writable
2. Expected folder structure:
```
/scheduled_emails/
├── batch_20260106120000_abc123def456.json
└── batch_20260106120000_abc123def456_sent.json
```

### Verify Scheduler Execution (Manual Test)
**URL**: `http://localhost/arquero_sofia/index/scheduler_check_emails.php?debug=1`

**Expected Response**:
```json
{
    "success": true,
    "message": "Scheduler completed",
    "batches_checked": 0,
    "emails_sent": 0,
    "debug": {
        "scheduled_dir": "/path/to/scheduled_emails",
        "files_found": 0,
        "timestamp": "2026-01-06 12:00:00"
    }
}
```

### Create Test Scheduled Batch
1. In admin panel, select student and choose "Schedule for Later"
2. Set date to tomorrow and time to 10:00 AM
3. Click send
4. Check `/scheduled_emails/` folder
5. **Expected**: JSON file created with batch data

---

## Step 6: File Permissions Verification

### Required Writable Folders
```bash
# Windows (run as Administrator)
icacls "e:\arquero_sofia\index\scheduled_emails" /grant Users:F
icacls "e:\arquero_sofia\index\admissions" /grant Users:F
icacls "e:\arquero_sofia\index\logs" /grant Users:F

# Linux
chmod 755 /var/www/html/arquero_sofia/index/scheduled_emails/
chmod 755 /var/www/html/arquero_sofia/index/admissions/
chmod 755 /var/www/html/arquero_sofia/index/logs/
```

---

## Step 7: Complete End-to-End Test

### Scenario: Admit a Student and Send Exam Link

**Part 1: Student Submits Application**
1. Open [Register.html](http://localhost/arquero_sofia/index/Register.html)
2. Fill form with test data:
   - Name: John Test
   - Email: john.test@example.com
   - Program: BS Information Technology
   - All other required fields
3. Click "Submit"
4. **Expected**: 
   - PDF downloads (admission_PTC-XXXXXX.pdf)
   - Confirmation email sent
   - Success message shown

**Part 2: Check Database**
```sql
SELECT * FROM admissions 
WHERE email = 'john.test@example.com';
```
**Expected**: Record created with admission_id, status=admitted

**Part 3: Admin Sends Exam Link**
1. Open [admin_exam_config.html](http://localhost/arquero_sofia/index/admin_exam_config.html)
2. Click "🔄 Load Students"
3. **Expected**: John Test appears in list
4. Check checkbox for John Test
5. Click "📤 Send Exam Links to Selected Students"
6. Enter password: `ptc_admin_2026`
7. Click "Send Now"
8. **Expected**: Success message "Sent 1 emails successfully"

**Part 4: Verify Email Sent**
```sql
SELECT * FROM admissions 
WHERE email = 'john.test@example.com';
```
**Expected**: `exam_link_sent = 1`, `email_sent_date = NOW()`

```sql
SELECT * FROM email_logs 
WHERE recipient_email = 'john.test@example.com' 
ORDER BY sent_timestamp DESC;
```
**Expected**: Email log entry for sent exam link

---

## Checklist

### Configuration ✓
- [ ] Database created and accessible
- [ ] db_config.php has correct credentials
- [ ] Folders exist: /scheduled_emails/, /admissions/, /logs/
- [ ] Folders are writable by web server

### APIs ✓
- [ ] get_programs.php returns programs
- [ ] get_admitted_students.php returns students
- [ ] exam_config.php returns exam settings
- [ ] Data matches directory structure

### Admin Panel ✓
- [ ] Program dropdown populates from database
- [ ] Student list loads from database
- [ ] Emails send when "Send Now" clicked
- [ ] Email records update in database

### Security ✓
- [ ] Admin password required: ptc_admin_2026
- [ ] GET requests don't require password
- [ ] POST requests require password
- [ ] Database prepared statements prevent SQL injection

### Email System ✓
- [ ] Gmail SMTP configured
- [ ] App password: qjpf wvol cpgq tsoa
- [ ] Emails sent successfully
- [ ] Email logs recorded in database

### Scheduling ✓
- [ ] Scheduled emails create JSON files
- [ ] scheduler_check_emails.php runs successfully
- [ ] Batches move to "sent" folder after processing

---

## Troubleshooting

| Symptom | Probable Cause | Solution |
|---------|----------------|----------|
| `get_programs.php` returns empty list | No programs in database | INSERT test programs |
| `get_admitted_students.php` returns empty | No admitted students | Submit test form first |
| Dropdown shows "Loading programs..." forever | JavaScript error | Check browser console (F12) |
| "Database connection failed" | Wrong credentials | Update db_config.php |
| "Failed to load students" | Database query error | Check error logs, verify syntax |
| Emails not sending | SMTP credentials wrong | Test mail() function separately |
| Scheduled emails not sending | Scheduler not running | Check Task Scheduler / cron |
| Permission denied errors | Folder permissions | chmod 755 directories |

---

## Testing Checklist Results

After completing all steps, you should have:

✅ Database with 7 tables populated  
✅ Test data: 1+ program, 1+ student  
✅ All API endpoints responding correctly  
✅ Admin panel loading data from database  
✅ Email sending working (check inbox)  
✅ Database records updated after emails sent  
✅ Scheduled emails creating and tracking JSON files  
✅ Scheduler executing without errors  

If all above are complete: **System is fully integrated and ready to use!**

---

**Document Version**: 1.0
**Created**: 2026-01-06
**Purpose**: Verify database integration is complete and working
