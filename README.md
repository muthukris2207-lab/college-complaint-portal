# AI-Powered Smart Complaint & Escalation System

A secure, responsive, full-stack college portal for students to submit and track complaints (via text or voice speech-to-text). The system leverages AI to classify categories and priorities, route them to department dashboards, and auto-escalates unresolved complaints using a hierarchy over time (Staff -> HOD -> Principal).

## Features
1. **Student Module**:
   - Complaint submission form (Text / Voice Input via Web Speech API).
   - Anonymous submission toggle (hides student details from staff views).
   - Collision-free generation of unique Complaint IDs (e.g. `CMP-2026-00001`).
   - Track Status page (Submitted / In Progress / Escalated / Resolved) with timeline logs and staff resolution notes.
2. **AI Module**:
   - Automated category classification (Academic, Infrastructure, Hostel, Transport, Harassment).
   - Automated priority classification (Low, Medium, High).
   - Short 1-2 sentence automated summary generation.
   - Local fallback rules when Claude API is unconfigured or offline.
   - Admin Override to manually correct AI classifications.
3. **Dashboards & SLA Escalation**:
   - Role-based staff portals (Department Staff, HOD, Principal).
   - Auto-escalation cron engine:
     - Unresolved for 24 hours -> Escalate to HOD.
     - Unresolved for 48 hours -> Escalate to Principal.
     - Logged in `escalation_log` table for auditability.

---

## File Structure

```text
smart-complaint-system/
├── assets/
│   ├── css/
│   │   └── style.css            # Base stylesheet (glassmorphism, variables, layout, animations)
│   └── js/
│       └── speech.js            # Web Speech API speech-to-text voice assistant
├── config/
│   ├── config.php               # System settings and API keys
│   └── config.sample.php        # Sample config template
├── includes/
│   ├── db.php                   # PDO database handler and query functions
│   ├── ai.php                   # Claude API cURL integration and keyword fallbacks
│   ├── auth.php                 # PHP Session authentication and role validators
│   ├── header.php               # Common page header and navigation
│   └── footer.php               # Common page footer
├── cron/
│   └── escalate.php             # SLA auto-escalation cron script
├── index.php                    # Student submission page
├── track.php                    # Student tracking page
├── login.php                    # Portal Login page
├── dashboard_staff.php          # Department Staff dashboard
├── dashboard_hod.php            # HOD dashboard
├── dashboard_principal.php      # Principal dashboard
├── logout.php                   # Session logout handler
├── schema.sql                   # SQL tables schema and seed data
└── README.md                    # Setup and guide (This file)
```

---

## Setup Instructions

### 1. Database Setup
1. Open your MySQL/MariaDB database server (e.g., XAMPP Control Panel, Laragon, or standalone service).
2. Create or import the database using the provided `schema.sql`:
   ```bash
   # From your command line:
   mysql -u root < schema.sql
   ```
   *Note: This automatically creates the `smart_complaint_db` database and seeds it with default departments and hashed credentials.*

### 2. Configuration Setup
1. Copy the sample config to create `config.php`:
   - Duplicate `config/config.sample.php` and rename it to `config/config.php`.
2. Open `config/config.php` and update:
   - **Database Credentials** if different from the default (port, socket, password).
   - **Claude API Key** (`CLAUDE_API_KEY`). You can paste your Anthropic API Key directly or configure it as an environment variable. If left unconfigured, the system automatically uses the robust local keyword classifier.

### 3. Run the Local Development Server
From the root directory of the project, run PHP's built-in web server:
```bash
php -S localhost:8000
```
Open your browser and navigate to: [http://localhost:8000](http://localhost:8000)

---

## Seed User Accounts for Testing

All seeded accounts have the preconfigured passwords below (hashed securely with `password_hash()` in the database):

| Portal Role | Username | Password | Access / Scope |
| :--- | :--- | :--- | :--- |
| **Principal** | `principal` | `principal123` | College-wide overview, resolves Principal level escalations. |
| **Academic HOD** | `hod_academic` | `hodacad123` | Academic department view & HOD level escalations. |
| **Academic Staff** | `staff_academic` | `staffacad123` | Academic department staff queue. |
| **Infrastructure HOD**| `hod_infra` | `hodinfra123` | Infrastructure department view & HOD level escalations. |
| **Infrastructure Staff**| `staff_infra`| `staffinfra123` | Infrastructure department staff queue. |
| **Hostel HOD** | `hod_hostel` | `hodhostel123` | Hostel department HOD view. |
| **Hostel Staff** | `staff_hostel` | `staffhostel123` | Hostel department staff queue. |
| **Transport HOD** | `hod_trans` | `hodtrans123` | Transport HOD view. |
| **Transport Staff** | `staff_trans` | `stafftrans123` | Transport staff queue. |
| **Harassment HOD** | `hod_harass` | `hodharass123` | Harassment committee HOD view. |
| **Harassment Staff** | `staff_harass` | `staffharass123` | Harassment committee staff queue. |

---

## Testing the SLA Escalation Engine

To test the SLA escalation script (`cron/escalate.php`) without waiting 24 or 48 real hours:
1. File a complaint on the student portal.
2. Open the escalation engine URL in test mode:
   [http://localhost:8000/cron/escalate.php?test=1](http://localhost:8000/cron/escalate.php?test=1)
   *(Or run CLI: `php cron/escalate.php --test`)*
3. The test mode reduces the escalation duration from 24 hours to **24 seconds** for easy demonstration.
4. Refresh the track page or the staff/HOD/Principal dashboards to observe the complaint moving up the organizational chart!
