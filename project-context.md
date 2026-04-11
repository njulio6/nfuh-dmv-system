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

### 3.5 Participation Types

Members do not all participate equally.

Each member may be:

* Njangi participant
* Savings participant
* Cultural-only member

Fields:

* `participates_in_njangi` (boolean)
* `participates_in_savings` (boolean)
* `participates_in_cultural` (boolean)

Important:

* Membership ≠ Njangi participation
* Membership ≠ Savings participation

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

---

### 4.4 Source of Truth

* Beneficiaries define **who receives**
* Contributions define **who paid whom**
* Refunds are **calculated**, not manually stored

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
