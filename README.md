# NFUH DMV System: Architecture and Technical Documentation

Documentation for the operations and financial platform of the Nkwen Cultural and Development Association (NFUH), Washington DC, Maryland, and Virginia (DMV) Chapter.

---

## 1. System Overview

The NFUH DMV System is a Laravel 11 application built to manage the administrative, cultural, and financial operations of the NFUH DMV Chapter. It replaces manual spreadsheets, paper ledgers, and informal message threads with a centralized platform.

The system handles six core functional areas:

1. **Member Directory and Cultural Governance:** Member profiles, auto-generated member codes, next of kin information, traditional royal titles, and member ranks.
2. **Meeting Attendance and Roll-Call:** Monthly meeting sessions, automated scheduling rules, a live roll-call desk for secretaries, advance excuse workflows with audit logging, and QR code self-check-in with strict arrival windows.
3. **The Reciprocal Njangi Engine:** Multi-cycle management, beneficiary draw orders, member payment submissions with Zelle proof uploads, Treasurer review workflows, and pair-wise reciprocal ledger accounting.
4. **Member Savings:** Savings accounts, deposit requests with proof verification, withdrawal requests with configurable fee deductions, dual approval and disbursement workflows, and sensitive bank detail masking.
5. **Community Loan Program:** Loan applications, multi-guarantor requests with member-portal approvals, Loan Committee reviews with customizable sub-statuses, Treasurer disbursements, interest calculation, amortization tracking, and default management.
6. **Governance and Access Control:** Spatie role-based access control, separation-of-duties conflict detection, non-destructive permission seeding, and a web-based setup wizard.

---

## 2. System Architecture and Data Model

The application follows the Laravel MVC pattern with specialized Service and Action classes for business logic.

```
                    ┌──────────────────────────────────────────────┐
                    │               Web Routing Layer              │
                    │   (Member Portal, Admin, Desks, Installer)   │
                    └──────────────────────┬───────────────────────┘
                                           │
                    ┌──────────────────────▼───────────────────────┐
                    │              Controller Layer                │
                    │   (Attendance, Njangi, Savings, Loans, RBAC) │
                    └──────────────────────┬───────────────────────┘
                                           │
         ┌─────────────────────────────────┼────────────────────────────────┐
         ▼                                 ▼                                ▼
  ┌──────────────┐                  ┌──────────────┐                 ┌──────────────┐
  │   Services   │                  │ Eloquent ORM │                 │ View Engine  │
  │ • QrCode     │                  │  24 Models   │                 │ Blade Views  │
  │ • Compliance │                  │  56 Tables   │                 │ Tailwind CSS │
  │ • MemberCode │                  │ Relationships│                 │ Lucide Icons │
  │ • NjangiOps  │                  │  Constraints │                 │ Responsive   │
  └──────────────┘                  └──────────────┘                 └──────────────┘
```

### Core Database Entities

* **Organization (`organizations`):** Top-level tenant model representing the association chapter. Owns members, cycles, sessions, and settings.
* **User (`users`):** Authentication entity. Holds login credentials, email address, password hash, and Spatie role assignments.
* **Member (`members`):** Community identity entity. Linked to `users` via `user_id`. Stores `member_code`, names, phone, state, join date, traditional rank, and next of kin details.
* **MemberRank (`member_ranks`):** Cultural and traditional titles recognized within the association.
* **RoyalAuthority (`royal_authorities`):** Traditional leadership records associated with the chapter.
* **MemberRole (`member_roles`):** Organizational roles within the association structure.
* **NjangiCycle (`njangi_cycles`):** An annual or multi-month Njangi cycle with defined contribution amounts, start dates, and end dates.
* **NjangiCycleMember (`njangi_cycle_members`):** Pivot table assigning members to a specific cycle with their benefit order numbers.
* **NjangiSession (`njangi_sessions`):** Individual monthly meetings tied to a cycle. Holds meeting dates, check-in tokens, and check-in window times.
* **NjangiSessionBeneficiary (`njangi_session_beneficiaries`):** Designates which members take the pot during a specific session.
* **NjangiPaymentSubmission (`njangi_payment_submissions`):** Member-submitted payment proofs with uploaded Zelle screenshots, status, and Treasurer review notes.
* **NjangiContribution (`njangi_contributions`):** Pair-wise financial record tracking money from `contributor_member_id` to `beneficiary_member_id`.
* **NjangiDisbursement (`njangi_disbursements`):** Record of payout disbursed to a beneficiary.
* **MeetingAttendance (`meeting_attendances`):** Attendance status (`present`, `absent`, `excused`) per member per session, check-in method, verification timestamps, and officer notes.
* **MeetingGuest (`meeting_guests`):** Visiting attendees registered during meeting sessions.
* **AttendanceAuditLog (`attendance_audit_logs`):** Tamper-evident log of changes made to attendance records, storing old status, new status, officer ID, timestamp, and justification.
* **SavingsTransaction (`savings_transactions`):** Approved deposits, withdrawals, and balance adjustments.
* **SavingsDepositRequest (`savings_deposit_requests`):** Member requests to deposit savings, backed by payment screenshots.
* **SavingsWithdrawalRequest (`savings_withdrawal_requests`):** Member withdrawal applications, tracking gross amount, fee deductions, net payout, reviewer notes, and disbursement details.
* **LoanRequest (`loan_requests`):** Loan applications tracking amount, duration, interest rate, repayment frequency, purpose, status, and disbursement proof.
* **LoanGuarantor (`loan_guarantors`):** Members who pledge to guarantee a loan, including their response status and agreed terms.
* **LoanRepayment (`loan_repayments`):** Approved loan repayment transactions.
* **LoanRepaymentRequest (`loan_repayment_requests`):** Member-submitted repayment proofs awaiting Treasurer approval.
* **LoanSubStatus (`loan_sub_statuses`):** Configurable operational sub-states used by the Loan Committee during application reviews.
* **Setting (`settings`):** Global application configuration including theme colors, logos, loan policies, meeting schedules, and withdrawal fees.

---

## 3. Member Directory and Cultural Governance

The member module handles registration, profile updates, and traditional cultural structures.

### Member Code Generation
Every member receives an identifier generated by `App\Services\Member\MemberCodeGenerator`. The code follows the format `{StateCode}-{Year}-{Sequence:003}` (for example, `MD-2026-001` or `VA-2026-005`).

When new members are imported via Excel (`App\Imports\MembersImport`) or registered through the admin panel, the service queries the highest existing sequence for that state and year to assign the next number.

### Next of Kin and Emergency Records
Members maintain family contact records on their profile:
* Next of kin full name
* Next of kin phone number
* Next of kin email address
* Next of kin physical address

### Cultural Hierarchy and Traditional Ranks
The association recognizes traditional Cameroonian ranks and titles. The `MemberRank` model links to members through `rank_id`. In the UI, a member's title is displayed alongside their name (for example, Fon, Ntoh, Mbe, or Member) across attendance sheets and reports.

---

## 4. Meeting Attendance and QR Check-In Engine

Attendance tracking ensures the association meets constitutional quorum requirements and keeps accurate participation histories.

### Secretary Roll-Call Desk (`/admin/attendance/desk`)
The roll-call desk provides an interface for the General Secretary during monthly meetings:
* **Real-Time Counters:** Dynamic tallies showing Total Members, Present, Absent, Excused, and Unverified for the selected session.
* **Quick-Tap Status Buttons:** Direct buttons to mark Present, Absent, or Excused with immediate AJAX updates.
* **Single-Member Reset:** A reset control returns a member's record to unverified if a mistake was made during roll call.
* **Batch Clear Challenge:** To clear all attendance records for a session, the officer must enter the challenge confirmation word "Clear".

### Future Date Protection and Audit Logging
To prevent attendance records from being marked before a meeting takes place:
1. When an officer attempts to mark attendance for a session with a future date, the system prevents direct updates and opens the Advance Override Modal.
2. The officer must provide a written reason of at least 5 characters (for example, "Member provided advance travel notice").
3. The change is written to `attendance_audit_logs` storing the member ID, session ID, officer ID, previous status, new status, timestamp, and justification.

### QR Code Self-Check-In (`/attendance/check-in/{token}`)
Members can check themselves in by scanning a QR code projected in the meeting hall:
* **Vector SVG Generation:** The system uses `App\Services\QrCodeService`, a pure PHP implementation that generates standards-compliant SVG QR codes without requiring external binaries or extensions.
* **Session Start Window:** Self-check-in opens at 5:00 PM Eastern Time on the scheduled meeting date. The service displays an informational countdown screen if scanned before the window opens.
* **Duplicate Prevention:** Database unique constraints on `[njangi_session_id, member_id]` prevent duplicate check-ins.
* **Guest Check-In:** Visitors can check in using the same QR link by entering their name, phone number, and host member, creating records in `meeting_guests` without creating full member accounts.

---

## 5. The Reciprocal Njangi System

The Njangi is a mutual financial system built on reciprocal obligations rather than flat lottery pools.

### The Two Phases: Playing vs. Refunding

1. **Playing (Advance Solidarity):**
   Before receiving their payout, a member contributes money to whichever member is taking the pot. This money is recorded as "played" for that beneficiary.
2. **Refunding (Paying Back):**
   After a member receives their payout, their role shifts. In future meetings, when the members who supported them take their turn to receive the pot, the past beneficiary must refund the exact amount previously received from those members.

### Pair-Wise Accounting Model

The system does not treat contributions as a generic pool. The `njangi_contributions` table records:
* `contributor_member_id`
* `beneficiary_member_id`
* `amount`
* `njangi_session_id`

For any two members (Member A and Member B), the reciprocal engine evaluates:
* **Expected Refund:** Sum of contributions from B to A when A was the beneficiary.
* **Amount Refunded:** Sum of contributions from A to B when B was the beneficiary.
* **Outstanding Balance:** Expected Refund minus Amount Refunded (with a minimum of $0.00).

Status classifications shown on the member report (`/member/njangi-report`):
* **Settled:** Outstanding balance equals $0.00.
* **Partial:** A payment has been made, but an outstanding balance remains.
* **Unpaid:** No refunds have been made toward the obligation yet.

### The Beneficiary Session Exemption ($0 Obligation)
When a member is an active beneficiary taking the pot in a session, their personal contribution obligation for that session is $0.00. The system excludes self-contributions, ensuring beneficiaries are not marked delinquent on their own payout night.

### Payment Submission and Approval Flow
1. **Submission (`/member/njangi-payments`):** The member chooses the active session, enters the dollar amount sent through Zelle, uploads a screenshot of the payment receipt, and submits.
2. **Verification (`/njangi-submissions`):** The Treasurer inspects the uploaded receipt image alongside the claimed amount and compares it against the bank ledger.
3. **Approval:** Upon approval via `App\Services\Njangi\ApproveNjangiPaymentSubmission`, the system records the contribution entries, updates session totals, and marks the submission as approved. If rejected, the Treasurer provides an explanatory note to the member.

---

## 6. Member Savings and Withdrawal Workflows

The savings module provides internal credit union functions for association members.

### Savings Balance Calculations
A member's savings standing is computed from `savings_transactions`:
* **Total Balance:** All approved deposits and positive adjustments minus all approved withdrawals.
* **Available Balance:** Total balance minus any pending or approved withdrawal requests currently in the queue.

### Deposit Requests
Members submit deposit requests through `/member/savings/request` by attaching payment receipts. The Financial Secretary reviews the request, verifies the bank transfer, and approves it to credit the member's savings ledger.

### Withdrawal Process and Fee Deductions
1. **Application:** The member submits a withdrawal request specifying the gross amount and payout destination (Zelle or Bank Transfer).
2. **Fee Calculation:** The system calculates the withdrawal fee based on `savings_withdrawal_fee_percent` configured in settings (for example, 1.0%), subtracting it from the gross amount to determine the net payout.
3. **Approval:** An administrator reviews and approves the request, reserving the funds from the member's available balance.
4. **Disbursement:** The Treasurer executes the bank transfer, uploads proof of disbursement, records the transaction reference, and marks the request disbursed.
5. **Security:** Sensitive bank account and routing numbers are masked in display views (for example, showing only the last 4 digits of account numbers and hiding routing numbers).

---

## 7. Community Loans and Credit Union

Members in good standing can apply for internal community loans with peer guarantor backing.

### Application Requirements
* **Minimum Savings Balance:** Configured in `settings.min_savings_for_loan`. The system verifies the applicant meets this threshold before allowing submission.
* **Loan Parameters:** Amount, duration in months, loan purpose, payout method, and repayment frequency (monthly installments or lump-sum payment).
* **Interest Types:** Configured either as flat interest or duration-based interest calculated against loan term.

### Multi-Guarantor Workflow
* The application requires peer guarantors (minimum and maximum bounds set in `settings.loan_guarantor_min` and `settings.loan_guarantor_max`).
* Nominated guarantors receive notifications in their personal portal (`/member/loans/applications`).
* Each guarantor must review the loan terms and click Approve or Decline before the Loan Committee can take action.

### Loan Committee Review and Custom Sub-Statuses
* The Loan Committee inspects applicant savings history, existing obligations, and guarantor approvals.
* Committee members update operational sub-statuses (`App\Models\LoanSubStatus`) to track progress through review stages (for example, "Under Credit Review", "Awaiting Documentation", or "Approved by Committee").

### Dual-Control Disbursement
To maintain separation of duties, the Loan Committee approves the application, but only the Treasurer (`loans.disburse` permission) can disburse funds. The Treasurer enters payment references, disbursement destination, payment method, and uploads transfer receipts.

### Repayment Tracking
* Members submit repayment proofs through `/member/loans/repayment-requests`.
* Treasurers approve repayments, automatically reducing the remaining loan balance.
* If a loan passes its repayment due date without complete settlement, administrators can transition the loan status to Defaulted.

---

## 8. Roles, Permissions, and Segregation of Duties

The application uses Spatie Laravel-Permission with custom safeguards for financial compliance.

### Organizational Roles

| Role | Primary Permissions | Key Responsibilities |
|---|---|---|
| **Admin** | All permissions | System configuration, user accounts, role assignment, and master overrides. |
| **Treasurer** | `loans.disburse`, `savings.disburse_withdrawal`, `njangi.disburse` | Cash custody, outgoing bank transfers, payment verification, and disbursement proofs. |
| **Financial Secretary** | `savings.record_deposit`, `njangi.audit_submissions`, `njangi.manage_cycles` | Incoming deposit verification, payment audits, and cycle configurations. |
| **General Secretary** | `attendance.mark`, `attendance.view_all`, `members.manage` | Roll call, advance attendance excuses, member directory, and meeting minutes. |
| **Loan Committee** | `loans.committee_review`, `loans.approve`, `loans.reject`, `loans.manage_sub_statuses` | Evaluating loan applications, reviewing guarantor pledges, and managing approval stages. |
| **Member** | `attendance.rsvp`, `loans.apply`, `savings.deposit_request`, `njangi.submit_payment` | Personal dashboard access, payment proof submissions, and self-service check-in. |

### Separation of Duties Compliance Engine
The `App\Services\RoleComplianceService` checks for conflicting role assignments:
* **Treasurer and Loan Committee Conflict:** Prevents the same user from both approving loans and disbursing loan cash.
* **Treasurer and Financial Secretary Conflict:** Prevents the same user from managing bookkeeping records while holding cash custody.

If an administrator assigns conflicting roles, the service surfaces an advisory warning highlighting the reduced internal checks and balances.

### Safe Permission Synchronization
To allow updates to default permissions without overwriting custom role assignments, the application provides a non-destructive synchronization endpoint (`POST /admin/roles/sync-defaults`). It creates missing permissions, adds new permissions to the admin role, and flushes the Spatie cache without detaching existing user roles.

---

## 9. Association Settings and Theme Customization

Global application settings are managed through `/settings`.

* **Branding and Themes:** Custom primary and secondary colors for light and dark modes with automatic text contrast calculation (`getContrastTextColor`).
* **Logos and Icons:** Upload forms for light logo, dark logo, and site favicon.
* **Meeting Schedules:** Dynamic session rules defining which week and weekday meetings occur (for example, the first Saturday of the month).
* **Financial Policies:** Minimum savings balances for loan eligibility, default interest rates, guarantor limits, and withdrawal fee percentages.
* **Mid-Cycle Controls:** Toggles governing whether new members can join or leave an ongoing Njangi cycle.

---

## 10. Web Installer and System Maintenance Tools

### Web-Based Setup Wizard (`/install`)
For fresh deployments, the system provides a multi-step visual installer:
1. **Requirements Check:** Verifies PHP version (>= 8.2) and required extensions (`pdo_mysql`, `openssl`, `mbstring`, `xml`, `json`, `fileinfo`).
2. **Permissions Check:** Validates write permissions on `storage/` and `bootstrap/cache/`.
3. **Database Configuration:** Interactive form to input database host, port, name, username, and password, followed by an immediate connection test.
4. **Administrator Account:** Creates the primary administrator profile.
5. **Database Migration and Seeding:** Runs migrations and default seeders automatically.

### Admin System Tools (`/admin/tools`)
Authenticated administrators can perform maintenance tasks directly from the web interface:
* Execute database migrations
* Clear and rebuild application caches
* Re-link the public storage symlink for uploaded receipts

---

## 11. Local Setup and Installation Guide

### Prerequisites
* PHP 8.2 or higher with `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo` extensions
* Composer 2.x
* MySQL 8.0 or MariaDB 10.4+
* Node.js 18+ and NPM

### Installation Steps

```bash
# 1. Clone the repository
git clone https://github.com/njulio6/nfuh-dmv-system.git
cd nfuh-dmv-system

# 2. Install PHP and frontend dependencies
composer install
npm install

# 3. Create environment configuration
cp .env.example .env
php artisan key:generate

# 4. Configure database connection in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=nfuh_dmv
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Run database migrations and seed default data
php artisan migrate --seed

# 6. Create the symbolic link for uploaded receipts and avatars
php artisan storage:link

# 7. Compile frontend assets
npm run build

# 8. Start the local development server
php artisan serve
```

Access the application in your browser at `http://localhost:8000`.

---

## 12. Automated Testing Suite

The application includes an automated test suite using Pest PHP and PHPUnit.

```bash
# Run the entire test suite
php artisan test

# Run specific functional test suites
./vendor/bin/pest tests/Feature/AttendanceDeskValidationTest.php   # Roll call, overrides, and reasons
./vendor/bin/pest tests/Feature/AttendanceQrCheckInTest.php        # QR generation, windows, duplicate checks
./vendor/bin/pest tests/Feature/RoleSegregationTest.php            # RBAC and safe permission sync
./vendor/bin/pest tests/Feature/MemberDashboardTest.php            # Reciprocal Njangi calculations
./vendor/bin/pest tests/Feature/LoanTest.php                       # Loan applications, interest, repayments
./vendor/bin/pest tests/Feature/SavingsTest.php                    # Savings transactions and balances
./vendor/bin/pest tests/Feature/SavingsWithdrawalTest.php          # Fee calculations and dual approval
./vendor/bin/pest tests/Feature/GuarantorGovernanceTest.php        # Guarantor pledge and review flows
```

---

## 13. Support and Issues

For feature requests, bug reports, or questions regarding this system, submit an issue on the project repository or contact the NFUH DMV Executive Committee.
