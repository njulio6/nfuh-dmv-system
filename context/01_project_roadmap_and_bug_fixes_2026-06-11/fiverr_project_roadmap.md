# Fiverr Project Roadmap & Requirements

This document outlines the agreed-upon development scope, phased timeline, and design rules for completing the remaining modules of the NFUH DMV Membership & Njangi System based on the client agreement from June 11, 2026.

---

## 📅 Agreement & Timeline
* **Budget**: $250 USD
* **Timeline**: 15 Days (starting June 11, 2026) for development, testing, and feedback loop.
* **Workflow**: Phased approach. Code must be committed and reviewed module-by-module. No moving to the next phase until the current phase is approved.

---

## 🧩 Phased Scope of Work

### Phase 1: Member Dashboard (Current Phase)
The Member Dashboard serves as the front-end portal for logged-in members to view their standing and perform actions.

* **Profile Summary**: Display member's personal info (name, address, ranks, titles).
* **Njangi Cycle Info**: Display details of their active cycle and their specific rotational position (benefit order number).
* **Payment Submissions Form**:
  * Fields: Active session select, payment amount, Zelle reference number, and a checkbox for attendance status ("Are you attending? Yes/No").
  * constraint: Mandatory Zelle screenshot file upload (blocked if empty).
* **Submissions & Contribution History**:
  * List of past submissions and their status (pending, approved, rejected).
  * Table showing approved contributions they have played.
* **Summaries**: Displays of savings balance, active loan balances, and pending guarantor requests.
* **Access Control**: Regular members can only see their own dashboard; admins/auditors have full access to management panels.

---

### Phase 2: Savings Module
Built to manage regular or optional member savings logs.

* **Manual Ledger Management**: An admin view (Treasurer/Financial Secretary) to manually post savings transactions (deposits, withdrawals, manual ledger adjustments).
* **Ledger Schema**: Migrations and models for a transaction table (`savings_transactions`) tracking member ID, transaction date, amount, type (deposit/withdrawal), and notes.
* **Balances**: Automatic calculation of a member's net savings balance based on transaction history.
* **Member View**: Dashboard integration showing their cumulative balance and list of transactions.

---

### Phase 3: Loans & Guarantors Module
Handles loan requests, approvals, and repayment processing.

* **Workflow Support**:
  1. **Loan Request**: Member submits request specifying loan amount, duration, and notes.
  2. **Guarantor Request & Approval**: Member assigns other members as guarantors. Those members receive a request on their dashboards to approve or decline the guarantee.
  3. **Committee Approval**: Admin/Finance Committee panel to review and approve or reject the request.
  4. **Disbursement & Repayment**: Logging disbursement date and tracking repayment installments.
* **Loan Eligibility Rule**: Check savings ledger to ensure member's total savings balance is $\ge \$500$ (or a configurable setting) and that they participate in savings.
* **Repayment Integration**: Manual logging of repayments and automatic deduction from Njangi payouts using the `loan_deduction` field in the Njangi disbursements table.

---

### Phase 4: Reports Module
Summarizes logs and financial sheets.

* **Member Statements**: Printable summaries of individual member status.
* **Savings Statements**: Summary of savings balances and transactions.
* **Loan Statements**: Active loan status, guarantor lists, and repayment history.
* **Njangi Contribution Statements**: Dynamic refund/audit sheets showing expected play totals, received plays, and cycle balances (who owes who).
* **Exports**: Export functionality to CSV/Excel for audit purposes.

---

## ⚠️ Key Architectural & Bug Fix Reminders
We must fix these existing issues in the codebase during development:
1. **Excel Importer**: Update `MembersImport.php` to write to `first_name` and `last_name` (not `name`), and supply required `organization_id` and `member_code`.
2. **MemberController**: Implement the missing `destroy` method.
3. **Payment Approvals**: Fix the multiplier bug in `ApproveNjangiPaymentSubmission.php` so the total Zelle submission amount is split/divided correctly among session beneficiaries.
4. **Hardcoded Redirects**: Remove hardcoded links to cycle ID `2` in `index.blade.php` pages and dynamicize them.
5. **NjangiSessionService**: Safely clean up or align the unused `NjangiSessionService.php` file since it references an obsolete database schema.
