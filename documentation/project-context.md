# 📌 PROJECT CONTEXT — NFUH DMV Membership & Njangi System

---

## 1. 🎯 Objective

Build a centralized, self-owned system for NFUH to manage:

* Members
* Titles (traditional hierarchy)
* Roles (functional responsibilities)
* Njangi cycles (core financial system)
* Sessions (monthly meetings)
* Beneficiaries
* Contributions (“plays”)
* Refund tracking (who owes who)
* Future modules:

  * Savings
  * Loans

System must be:

* Flexible (real-world Njangi behavior)
* Scalable (multi-branch support)
* Maintainable (clean Laravel architecture)
* Independent (no SaaS dependency)

---

## 2. 🧩 Core Modules

### ✅ Completed

* Members (CRUD)
* Titles (stored as `member_ranks`, displayed as Titles)
* Roles (many-to-many)

### 🚧 In Progress

* Member enhancements (state, join date, participation)
* Member ID redesign

### ⏳ Pending

* Member import
* Njangi module (core logic)
* Savings module
* Loan module

---

## 3. 👥 Member Rules

### 3.1 Member Identity

Each member must have:

* First Name
* Last Name
* Phone
* Optional Email
* Address
* State (`MD`, `VA`, `DC`)
* Join Date (`join_date`)

---

### 3.2 Member ID Format

Member IDs must follow:

`STATE-YEAR-SEQUENCE`

Examples:

* `MD-2026-001`
* `VA-2025-003`
* `DC-2024-012`

Rules:

* State is mandatory
* Year is derived from `join_date`
* Sequence resets per state per year

Purpose:

* Enable geographic grouping
* Reflect seniority
* Support hosting coordination

---

### 3.3 Titles (formerly Ranks)

* Database table remains: `member_ranks`
* UI label: **Title**
* Each member:

  * Can have ONE title
  * Can have NO title

Display rule:

* If no title → show `-` or `None`

---

### 3.4 Roles (Functional)

* Stored in `member_roles`
* Pivot: `member_role_member`

Rules:

* Member can have multiple roles
* Member can have none

Examples:

* Secretary
* Treasurer
* Financial Secretary
* Loan Officer
* Lead Nformi

---

3.5 Participation Types (FINAL RULE)

NFUH membership has one universal participation and two optional financial participations.

Core Rule:

✅ ALL members participate in cultural activities by default

👉 Cultural participation is NOT optional

Financial Participation (Optional)

Members may optionally participate in:

Njangi
Savings
Final Participation Model
Participation Type	Required	Description
Cultural	✅ Mandatory	Core NFUH membership
Njangi	❌ Optional	Rotational financial system
Savings	❌ Optional	Financial contribution pool
System Fields (UPDATED)
participates_in_njangi (boolean)
participates_in_savings (boolean)

❌ REMOVE concept of:

participates_in_cultural

👉 Because it is always true by default

Njangi Constraint

Only members with:

participates_in_njangi = true

👉 Can be added to a Njangi cycle

Loan Constraint (Reminder)

Loan eligibility requires:

participates_in_savings = true
savings balance ≥ $500
Design Principle
Cultural participation = baseline identity
Financial participation = optional modules

---

### 3.6 Next of Kin

Each member must include:

* Name
* Phone
* Email
* Address

---

## 4. 💰 Njangi Core Logic

Njangi is a **reciprocal financial system**, not a fixed contribution pool.

### Core Concepts:

#### Cycles

* Represents one full round
* Members participate per cycle

#### Sessions

* Monthly meetings
* Typically first Saturday

#### Beneficiaries

* One or more per session
* Define who receives contributions

#### Contributions (“Plays”)

* Member → Beneficiary
* Not fixed amounts
* Tracked per relationship

---

### 4.1 Key Rule: Contribution Relationship

Each contribution must track:

* Contributor
* Beneficiary
* Amount
* Cycle
* Session

This is required for refund tracking.

### 4.1.1 Duplicate Prevention Rule

The system must prevent duplicate official Njangi contribution records.

Rules:

- A member should not have duplicate contribution records for the same:
  - cycle
  - session
  - contributor
  - beneficiary
  - approved payment submission

- Approved submissions must only be converted to contributions once.
- If a submission is already approved and converted, the system must not create duplicate contribution rows.
- Duplicate prevention should be enforced in both:
  - service logic
  - database constraints where practical

Design Principle:

Official contribution records are financial ledger entries and must remain clean, auditable, and non-duplicated.

---

### 4.2 Refund Principle (CRITICAL)

* If Member A contributes to Member B
* Member B must repay Member A later when A becomes beneficiary

System must track:

* who owes who
* how much
* when repayment should occur

---

### 4.3 End-of-Cycle Rule

* Final beneficiaries do NOT repay
* Refund obligations depend on beneficiary order

This logic must be handled in the service layer.

### 4.2.1 Refund Tracking Logic

The system must track financial relationships between members.

Rules:

- Every contribution creates a relationship:
  - Contributor → Beneficiary

- If Member A contributes to Member B:
  - Member B owes Member A

- Refunds must be calculated based on:
  - Contribution history
  - Beneficiary order
  - Session timing

- The system must NOT rely on manual tracking.
- Refund balances must be dynamically computed.

Outputs required:

- Who owes who
- Total amount owed
- Amount repaid
- Outstanding balance
- Settlement status

---

### 4.4 Source of Truth

* Beneficiaries define **who receives**
* Contributions define **who paid whom**
* Refunds are **calculated**, not manually stored
🔹 4.5 Beneficiary Distribution Rule (UPDATED)

Beneficiary distribution per session is flexible.

Rules:

- Each session must have a **minimum of 4 beneficiaries**
- Some sessions may have **more than 4 beneficiaries (e.g., 5)**
- Beneficiary count is NOT fixed across sessions
- Distribution depends on:
  - Cycle structure
  - Total members
  - Committee decisions

Important:

- System must NOT enforce a fixed number (e.g., 4)
- System must support dynamic beneficiary assignment per session

Design Principle:

Njangi must reflect real-world flexibility, not rigid system constraints.

---

🔹 4.5 Hosting Groups (CRITICAL DISTINCTION)

Hosting groups are operational units, NOT financial units.

Definition
Each session has a hosting group
A hosting group is responsible for:
Venue coordination
Food/logistics
Meeting support
Key Rules
Each hosting group must contain at least 7 members
A hosting group is independent of beneficiaries
A session has:
1 hosting group
~4 beneficiaries (financial recipients)
Important Distinction
Concept	Purpose
Hosting Group	Operational/logistics
Beneficiaries	Financial distribution
System Rules
Hosting group members ≠ beneficiaries
A member can:
Be a host but NOT a beneficiary
Be a beneficiary but NOT part of hosting group
Hosting groups are tied to months/sessions, not cycles directly
Architectural Decision

Hosting must be modeled separately from Njangi financial logic.

DO NOT:

Store hosting members inside njangi_session_beneficiaries
Mix hosting with contribution tracking
Future Tables (Planned)
hosting_groups
hosting_group_members
(optional) session_hosting_groups
Design Principle

Njangi system must remain:

Financially accurate (beneficiaries + contributions)
Operationally flexible (hosting handled separately)

🔹 4.6 Attendance System (UPDATED RULE)

Attendance is tracked per session but is NOT mandatory.

Rules:
Members may:
Attend and play Njangi
Play Njangi without attending
Attend without playing
Attendance must NOT block participation
System Behavior:
During Njangi submission, system must ask:

“Are you attending this session?”

Options:

Yes
No
Data Requirements:

Attendance must be recorded with:

njangi_session_id
member_id
is_attending (boolean)
recorded_at
Design Principle:

Attendance is informational, not a validation constraint.

🔹 4.7 Njangi Payment Submission & Approval (CRITICAL WORKFLOW)

Njangi contributions must follow a submission + approval workflow.

Step 1 — Member Submission

Member submits:

Amount
Session (auto-linked)
Zelle screenshot (MANDATORY)
Attendance (Yes/No)
Mandatory Rule:

❌ Submission MUST be blocked if screenshot is missing

Step 2 — System Stores Submission

Submissions must be stored in a separate table:

njangi_payment_submissions

Fields:

member_id
njangi_session_id
amount
screenshot_path
is_attending
status (pending / approved / rejected)
submitted_at
reviewed_by
reviewed_at
Step 3 — Treasurer Review

Treasurer must:

Review screenshot
Confirm payment against Zelle/bank
Approve or reject
Step 4 — System Conversion

ONLY after approval:

👉 System creates records in:

njangi_contributions

Design Principle:
Submissions ≠ Contributions
Contributions are verified financial records
🔹 4.8 Free Will Donations (FUTURE MODULE)

Free will donations are separate from Njangi.

Definition:

Used for:

Bereavement support
Medical emergencies
Accidents
Community support
Special events
Workflow:
Treasurer creates donation campaign
Campaign includes:
Title
Description
Purpose
Target member/cause
Active period
Members see campaign in portal
Members contribute (similar to Njangi submission)
System Design:

Separate module:

Tables (planned):

donation_campaigns
donation_contributions
Key Rule:

Free will donations:

Are OPTIONAL
Are NOT tied to Njangi cycles
Must be tracked per member
🔹 4.9 Member Activity & Audit Trail

System must maintain full history of member actions.

Must Track:
Njangi submissions
Approved contributions
Attendance records
Donation contributions (future)
Savings (future)
Loan repayment (future)
Purpose:
Transparency
Accountability
Financial traceability
Member performance tracking
Design Principle:

All financial-related actions must be:

Auditable
Traceable
Non-editable after approval (soft-lock)

---

## 4.10 👤 Member Dashboard (NEW REQUIREMENT)

Members must have a personal dashboard to view their financial and Njangi status.

### Purpose

Provide transparency, reduce manual inquiries, and allow members to track their obligations and benefits in real-time.

---

### Dashboard Sections

#### 1. Njangi Overview

- Current cycle
- Member benefit order position
- Whether member has already benefited
- Upcoming benefit session (date)
- Monthly Njangi contribution requirement
- Repayment obligations (after benefiting)

---

#### 2. Contributions (Njangi Payments)

- Submitted payments
- Pending approvals (treasurer)
- Approved contributions
- Rejected submissions
- Contribution history per session

---

#### 3. Savings (Future Module)

- Total savings balance
- Savings contribution history
- Eligibility status (≥ $500 rule)

---

#### 4. Loans (Future Module)

- Active loan balance
- Monthly repayment due
- Payment history
- Guarantor details
- Loan status (active / completed / defaulted)

---

#### 5. Member Actions

Members must be able to:

- Submit Njangi payment (with screenshot)
- Indicate attendance (Yes/No)
- View contribution history
- View repayment obligations

---

### Access Control

- Members can ONLY view their own data
- FinCom/Admin can view all members

---

### Design Principle

Dashboard is:

❌ NOT a core financial module  
✅ A visualization layer built on top of:

- Njangi cycles
- Sessions
- Contributions
- Loans
- Savings

---

### Implementation Phase

This feature will be implemented AFTER:

- Njangi sessions
- Beneficiaries
- Contributions
- Disbursement tracking

---


## 5. 💵 Financial Rules

### Savings

* Separate module (not yet built)
* Members opt-in

---

### Loan Eligibility

A member is eligible ONLY IF:

* Participates in savings
* Savings balance ≥ $500

Important:

* Do NOT store `loan_eligible` manually
* Must be computed dynamically

---

## 6. 🗄️ Database Overview

### Core Tables

* `members`
* `member_ranks` (Titles)
* `member_roles`
* `member_role_member`
* `organizations`

---

### Njangi Tables (Already Migrated)

* `njangi_cycles`
* `njangi_cycle_members`
* `njangi_sessions`
* `njangi_session_beneficiaries`
* `njangi_contributions`
* `njangi_disbursements`

---

## 7. 🧭 Architecture Decisions (CRITICAL)

* System is **Njangi-first**, not loan-first
* Members are global; participation is modular
* Titles remain in DB as ranks for now (no refactor yet)
* Refunds are derived, not stored manually
* Avoid over-engineering early (no accounting engine yet)
* Use Laravel service layer for business logic
* Keep controllers thin

---

## 8. 🧪 Current System Status

### Completed

* Laravel setup
* SQLite database
* Member CRUD
* Roles & Titles working

### In Progress

* Member enhancements
* Member ID redesign

### Pending

* Member import
* Njangi logic implementation

---

## 9. ✅ Confirmed Njangi Rules

* Beneficiary order is fixed for the entire cycle once drawn in December.
* A member can benefit only once per cycle.
* Contributions must be paid in full; partial contributions are not allowed.
* Last beneficiaries do not repay because the cycle ends with them.

---

## 10. 🔜 Next Steps

### Phase 1.5 — Member Enhancements

* Add state field
* Add join_date
* Add next of kin email & address
* Add participation flags
* Implement new member ID format
* Create 10 dummy members

---

### Phase 2 — Member Import

* Excel import
* Validation rules
* Duplicate handling

---

### Phase 3 — Njangi Module

1. Cycle management
2. Add members to cycle
3. Session generation
4. Beneficiary assignment (fixed order)
5. Contribution tracking (full payments only)
6. Refund engine (order-based logic)
7. Disbursement tracking

---

## 11. 📁 File Usage Rule

This file must be updated whenever:

* New business rules are introduced
* Committee feedback changes logic
* System architecture decisions are made
* New modules are added

This file is the **single source of truth** for the system.

---

## 12. 🚀 Chat Continuity Rule

When starting a new ChatGPT session:

Paste this file and say:

> “Use this project-context.md as system memory. Continue development.”

---

**END OF FILE**
