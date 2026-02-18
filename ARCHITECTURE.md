# System Architecture Diagram

## High-Level Component Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                          PTC ADMISSION SYSTEM                        │
│                    (Fully Database-Integrated)                       │
└─────────────────────────────────────────────────────────────────────┘

                              WEB BROWSERS
                          ┌─────────────────┐
                          │   Client Side   │
                          │   Applications  │
                          └─────────────────┘
                                   │
                    ┌──────────────┼──────────────┐
                    │              │              │
                 [1]            [2]            [3]
             Register.html  admin_exam_    manage_exams.html
                           config.html
                              │
                    ┌──────────┼──────────┐
                    │          │          │
              [Sections]   [Section 1]  [Section 2]
              ─────────    Config Exam  Bulk Emails
              1. Form
              2. PDF Gen
              3. Email

                         │        │         │
                    ┌────┘        │         └────┐
                    │             │              │
                    ▼             ▼              ▼
            ┌──────────────┬──────────────┬──────────────┐
            │              │              │              │
        [Frontend APIs - GET Requests - No Password Needed]
            │              │              │              │
      get_programs.php    exam_config.php  get_admitted_
                          (and others)     students.php
            │              │              │
            └──────────────┬──────────────┘
                           │
                    ┌──────┴──────┐
                    │             │
            ┌───────▼──────┐     │
            │ Fetch from   │     │
            │ Database()   │     │
            └───────┬──────┘     │
                    │            │
                    │       ┌─────▼──────────┐
                    │       │ [Backend APIs - POST Requests - Password Required]
                    │       │                │
                    │   send_exam_link_bulk.php
                    │       │ manage_exams.php
                    │       │ scheduler_check_emails.php
                    │       └─────┬──────────┘
                    │             │
                    └─────────────┼────────────────────┐
                                  │                    │
                      ┌───────────▼───────────┐        │
                      │                       │        │
                      │   MySQL/MariaDB       │        │
                      │   Database            │        │
                      │                       │        │
                      ├─admissions            │        │
                      ├─exam_sessions         │        │
                      ├─exam_registrations    │        │
                      ├─email_logs            │        │
                      ├─system_logs           │        │
                      ├─programs              │        │
                      └─admission_stats       │        │
                                              │        │
                          ┌───────────────────┘        │
                          │                            │
                          │  ┌───────────────────────┐ │
                          │  │ /scheduled_emails/    │ │
                          │  │ (JSON batch files)    │ │
                          │  └───────────────────────┘ │
                          │                            │
                          │  ┌───────────────────────┐ │
                          │  │ /admissions/          │ │
                          │  │ (PDF storage)         │ │
                          │  └───────────────────────┘ │
                          │                            │
                          │  ┌───────────────────────┐ │
                          │  │ /logs/                │ │
                          │  │ (Application logs)    │ │
                          │  └───────────────────────┘ │
                          │                            │
                          └────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     EXTERNAL SERVICES                               │
├─────────────────────────────────────────────────────────────────────┤
│ [Gmail SMTP] ←── Emails sent via smtp.gmail.com:587 with TLS       │
│                  • Dual mode: PHP mail() + SMTP fallback            │
│                  • App Password: qjpf wvol cpgq tsoa               │
│                                                                      │
│ [File System] ←── Batch scheduling via JSON files + Scheduler      │
│                  • Windows Task Scheduler OR                        │
│                  • Linux cron (runs scheduler_check_emails.php)    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Diagrams

### Scenario A: Student Submission Flow

```
Student Browser
     │
     ├─1─► Loads Register.html
     │
     ├─2─► fetch('exam_config.php?json')
     │     ↓ [Returns exam details]
     │     └─ Displays in form preview
     │
     ├─3─► Generates PDF with QR code
     │     (All client-side with jsPDF)
     │
     ├─4─► POST form data to save_admission.php
     │     │
     │     ├─→ save_admission.php validates data
     │     │   ├─→ Generates admission_id: "PTC-20260106-0001"
     │     │   ├─→ INSERT into admissions table
     │     │   └─→ Calls send_admission_email.php
     │     │       │
     │     │       ├─→ send_admission_email.php
     │     │       │   ├─→ fetch('exam_config.php?json')
     │     │       │   ├─→ sendEmail() via PHP mail() or SMTP
     │     │       │   └─→ INSERT into email_logs table
     │     │       │
     │     │       └─→ Response with confirmation
     │     │
     │     └─→ Response with success + PDF link
     │
     ├─5─► Download PDF (admission_PTC-20260106-0001.pdf)
     │
     └─6─► Check email for confirmation
           └─ Student receives HTML email with:
              • Admission ID
              • Form submission confirmation
              • Next steps info
              • Admin contact

Database Changes:
✓ admissions table: 1 new row (status='admitted', exam_link_sent=0)
✓ email_logs table: 1 new row (confirmation email logged)
✓ system_logs table: 1 new row (form submission record)
```

### Scenario B: Admin Sends Exam Link

```
Admin Browser
     │
     ├─1─► Opens admin_exam_config.html
     │
     ├─2─► Page loads programs
     │     └─ fetch('get_programs.php')
     │        └─ Response: [BS IT, BS BA, BS HM, ...]
     │           └─ Populate dropdown with database programs
     │
     ├─3─► Admin clicks "Load Students"
     │     └─ fetch('get_admitted_students.php?program=BS%20IT')
     │        └─ Response: [{id, admission_id, full_name, email, ...}, ...]
     │           └─ Render student list with checkboxes
     │
     ├─4─► Admin checks boxes for students to email
     │
     ├─5─► Admin clicks "Send Now"
     │     │
     │     ├─→ Admin enters password: "ptc_admin_2026"
     │     │
     │     ├─→ POST to send_exam_link_bulk.php
     │     │   (action='send_emails', emails=[], password=...)
     │     │
     │     ├─→ send_exam_link_bulk.php:
     │     │   │
     │     │   ├─→ Verify password
     │     │   │
     │     │   ├─→ For each email in list:
     │     │   │   ├─→ fetch('exam_config.php?json')
     │     │   │   ├─→ sendExamLinkEmail() via mail() or SMTP
     │     │   │   ├─→ UPDATE admissions SET exam_link_sent=1, email_sent_date=NOW()
     │     │   │   ├─→ INSERT into email_logs
     │     │   │   └─→ usleep(500000) - Small delay to avoid rate limit
     │     │   │
     │     │   └─→ Response: {success, sent: 25, failed: 0}
     │     │
     │     └─→ Admin sees progress bar and success message
     │
     ├─6─► Student receives exam link email
           └─ HTML email with:
              • Exam date and time
              • Exam link (clickable)
              • Admission ID
              • Instructions
              • Important warnings

Database Changes:
✓ admissions table: 25 rows updated (exam_link_sent=1, email_sent_date=NOW())
✓ email_logs table: 25 new rows (exam link emails logged)
✓ system_logs table: 1 new row (batch send action record)
```

### Scenario C: Scheduled Email Sending

```
Admin Browser (Schedule for Later)
     │
     ├─1─► Open admin_exam_config.html
     ├─2─► Select students (same as Scenario B)
     │
     ├─3─► Click "Schedule for Later" radio button
     │
     ├─4─► Enter date: 2026-01-10, time: 10:00 AM
     │
     ├─5─► Click "Send"
     │     │
     │     ├─→ POST to send_exam_link_bulk.php
     │     │   (action='schedule_emails', date, time, emails, password)
     │     │
     │     ├─→ send_exam_link_bulk.php:
     │     │   │
     │     │   ├─→ Verify password
     │     │   ├─→ Calculate timestamp for 2026-01-10 10:00 AM
     │     │   ├─→ Create /scheduled_emails/ folder if needed
     │     │   │
     │     │   ├─→ Create batch_20260110100000_abc123.json:
     │     │   │   {
     │     │   │     "batch_id": "batch_20260110100000_abc123",
     │     │   │     "scheduled_time": "2026-01-10 10:00:00",
     │     │   │     "scheduled_timestamp": 1736464800,
     │     │   │     "status": "pending",
     │     │   │     "emails": ["john@example.com", "jane@example.com", ...]
     │     │   │   }
     │     │   │
     │     │   └─→ Response: {success, count: 25}
     │     │
     │     └─→ Admin sees: "✓ 25 emails scheduled for 2026-01-10 at 10:00 AM"
     │
     └─6─► Admin closes browser

= = = = = = = = TIME PASSES = = = = = = = = = = =

System Scheduler (via Task Scheduler / Cron)
     │
     ├─1─► Task runs every 1 minute (or configurable interval)
     │
     ├─2─► Calls scheduler_check_emails.php
     │     │
     │     ├─→ scheduler_check_emails.php executes:
     │     │   │
     │     │   ├─→ Get current timestamp
     │     │   ├─→ Scan /scheduled_emails/ folder
     │     │   │
     │     │   ├─→ For each .json file (not _sent):
     │     │   │   ├─→ Read JSON file
     │     │   │   ├─→ Check if current_time >= scheduled_time
     │     │   │   │
     │     │   │   ├─→ If due:
     │     │   │   │   ├─→ For each email in batch:
     │     │   │   │   │   ├─→ sendExamLinkEmail()
     │     │   │   │   │   ├─→ UPDATE admissions table
     │     │   │   │   │   ├─→ INSERT email_logs
     │     │   │   │   │   └─→ usleep(500000)
     │     │   │   │   │
     │     │   │   │   ├─→ Rename file to _sent
     │     │   │   │   │   (batch_xxx.json → batch_xxx_sent.json)
     │     │   │   │   │
     │     │   │   │   └─→ UPDATE batch status: "sent"
     │     │   │   │
     │     │   │   └─→ If not due: skip and check next
     │     │   │
     │     │   └─→ Log results
     │     │
     │     └─→ Response: {success, batches_checked: X, emails_sent: Y}
     │
     └─3─► Students receive emails automatically at scheduled time

     Later: Admin views view_scheduled_emails.html
            │
            └─ Shows completed batches with "✓ Sent" status
               └─ Can view stats: timestamps, recipients, delivery status
```

### Scenario D: Enter Exam Results

```
Admin clicks "Manage Exams" → "Results" tab
     │
     ├─1─► Page calls manage_exams.php?action=get_results
     │     └─ Returns registrations needing score entry
     │
     ├─2─► Admin enters:
     │     ├─ Student name (auto-filled from DB)
     │     ├─ Attendance checkbox
     │     ├─ Score (e.g., 85)
     │
     ├─3─► Admin clicks "Save Results"
     │     │
     │     └─→ POST to manage_exams.php
     │         (action='save_results', registration_id, score, attendance, password)
     │
     ├─4─► manage_exams.php:
     │     │
     │     ├─→ Verify password: "ptc_admin_2026"
     │     │
     │     ├─→ Get passing_score from exam_sessions
     │     │
     │     ├─→ Calculate pass_fail:
     │     │   if (score >= passing_score) pass_fail = 'pass'
     │     │   else pass_fail = 'fail'
     │     │
     │     ├─→ UPDATE exam_registrations:
     │     │   ├─ score = 85
     │     │   ├─ attended = 1
     │     │   ├─ pass_fail = 'pass'
     │     │   └─ updated_at = NOW()
     │     │
     │     ├─→ INSERT system_logs with action
     │     │
     │     └─→ Response: {success, pass: true}
     │
     ├─5─► Admin sees success and updated results table
     │
     └─6─► Dashboard auto-updates statistics

Database Changes:
✓ exam_registrations table: 1 row updated (score, attended, pass_fail)
✓ system_logs table: 1 new row (admin action)
✓ admission_stats table: Updated counts
```

---

## Database Schema Relationships

```
                        ┌──────────────────┐
                        │    programs      │
                        ├──────────────────┤
                        │ id (PK)          │
                        │ program_name     │◄─┐
                        │ description      │  │
                        │ capacity         │  │
                        └──────────────────┘  │
                                               │
                                               │
                        ┌──────────────────┐  │
                        │  admissions      │  │
                        ├──────────────────┤  │
                        │ id (PK)          │  │
                        │ admission_id     │  │
                        │ given_name       │  │
                        │ last_name        │  │
                        │ email            │  │
                        │ program (FK)     ├──┘
                        │ exam_link_sent   │
                        │ email_sent_date  │
                        │ status           │
                        │ submission_date  │
                        └────────┬─────────┘
                                 │
                        ┌────────▼─────────┐
                        │ exam_registrations│
                        ├──────────────────┤
                        │ id (PK)          │
                        │ exam_id (FK)     │
                        │ student_adm_id(FK)
                        │ attended         │
                        │ score           │
                        │ pass_fail        │
                        └──────────────────┘
                                 ▲
                                 │
                        ┌────────┴─────────┐
                        │  exam_sessions   │
                        ├──────────────────┤
                        │ id (PK)          │
                        │ exam_date        │
                        │ start_time       │
                        │ duration         │
                        │ passing_score    │
                        └──────────────────┘

Audit & Logging:
┌──────────────────┐        ┌──────────────────┐
│   email_logs     │        │  system_logs     │
├──────────────────┤        ├──────────────────┤
│ id (PK)          │        │ id (PK)          │
│ recipient_email  │        │ action           │
│ subject          │        │ admin_id         │
│ status           │        │ timestamp        │
│ sent_timestamp   │        │ details          │
└──────────────────┘        └──────────────────┘

Statistics:
┌──────────────────┐
│ admission_stats  │
├──────────────────┤
│ total_admitted   │
│ exam_links_sent  │
│ exams_scheduled  │
│ exams_completed  │
│ pass_count       │
└──────────────────┘
```

---

## File Access Permissions

```
e:\arquero_sofia\index\
│
├── 📁 [R] HTML/PHP Files (Read-Only for web server is fine)
│   ├── Register.html
│   ├── admin_exam_config.html
│   ├── *.php (all API files)
│   └── ...
│
├── 📁 [RW] Writable Directories (Must be writable by web server)
│   ├── /scheduled_emails/     ← JSON files for scheduler
│   │   ├── batch_².json (pending)
│   │   └── batch_²_sent.json (completed)
│   │
│   ├── /admissions/           ← PDF files storage
│   │   ├── admission_PTC-20260106-0001.pdf
│   │   └── ...
│   │
│   └── /logs/                 ← Application logs
│       ├── scheduler.log
│       ├── emails.log
│       └── ...
│
└── 📄 Configuration
    ├── db_config.php          (Database credentials)
    ├── exam_config.php        (Exam settings / JSON store)
    └── [db_config.php may be outside web root in production]
```

---

## Security Layers

```
Public Access (No Authentication)
├─ Register.html           → Student form submission
├─ get_programs.php        → List programs (read-only)
├─ get_admitted_students.php → List students (read-only)
└─ exam_config.php?json    → Read exam config (read-only)

Protected Access (Password Required: ptc_admin_2026)
├─ send_exam_link_bulk.php  → Send/schedule emails (write)
├─ manage_exams.php         → Create/update exam sessions (write)
├─ scheduler_check_emails.php → trigger scheduler (write)
└─ exam_config.php [POST]   → Update exam settings (write)

Database Level
├─ All queries use prepared statements (SQL injection prevention)
├─ Sensitive fields (email, contact) encrypted in transit (HTTPS recommended)
└─ Email credentials stored separately in send_exam_link_bulk.php

Transmission Level
├─ SMTP: TLS/SSL encryption (smtp.gmail.com:587)
├─ Browser: HTTPS recommended for admin panel
└─ Form validation: Both client-side and server-side
```

---

**Document Version**: 1.0  
**Created**: 2026-01-06  
**Purpose**: Visual representation of system architecture and data flows
