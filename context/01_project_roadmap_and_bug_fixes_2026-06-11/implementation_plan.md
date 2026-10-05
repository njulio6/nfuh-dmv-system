# 3-Phase Project Roadmap & Bug Fixes

This implementation plan details the technical roadmap for the NFUH DMV Membership & Njangi System, broken down into three milestones with specific bug fixes added to the first phase.

## User Review Required

> [!IMPORTANT]
> - All UI enhancements and page updates will be built using modern, premium CSS styling via Tailwind CSS and Alpine.js loaded via CDN. No Node.js or npm compile dependencies will be introduced.
> - The application will dynamically determine if a logged-in user is an admin or standard member using email matching at login, routing them to the correct dashboard context.

## Proposed Changes

### Component 1: Phase 1 Bug Fixes & Code Cleanups

#### [MODIFY] [MembersImport.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Imports/MembersImport.php)
- Parse imported names to dynamically split them into `first_name` and `last_name`.
- Link to the default database `Organization` record if not provided in the import.
- Generate a unique member code with the pattern `STATE-YEAR-SEQUENCE` using helper logic.

#### [MODIFY] [NjangiSessionService.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Services/NjangiSessionService.php) (or cleanup)
- Clean up unused, obsolete calculation logic to ensure a clean service layer.

#### [MODIFY] [ApproveNjangiPaymentSubmission.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Actions/ApproveNjangiPaymentSubmission.php)
- Correct the logic that records beneficiary contributions upon Zelle sheet approval.
- Divide the total submission amount by the number of active session beneficiaries to write fractional ledger contributions rather than duplicate the total amount.

#### [MODIFY] [Njangi views / controllers](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views)
- Remove hardcoded cycle links (like cycle ID 2) and replace them with dynamic URLs using the active cycle ID context.

### Component 2: Member Dashboard & UI Updates (Milestone 1)

#### [NEW] [MemberDashboardController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/MemberDashboardController.php)
- Create controller to load user profile summary, current Njangi cycle, benefit order, contribution history, active loans, and guarantor requests.

#### [NEW] [dashboard.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/member/dashboard.blade.php)
- Construct the premium-styled Member Dashboard.
- Include a form for Zelle screenshot uploads, attendance checking, and submission.

### Component 3: Savings, Loans, and Guarantors (Milestone 2)

#### [NEW] Savings, Loans, and Guarantors database schemas, models, and controllers
- Database migration schemas for `savings_transactions`, `loans`, and `loan_guarantors`.
- Controllers and forms for manual savings ledger entries and loan requests.
- Validations verifying the `$500` minimum savings rule and handling guarantor approvals.
- Repayment logging and Njangi payout auto-deductions.

### Component 4: Financial Reports & Handover (Milestone 3)

#### [NEW] Financial Report Exports
- Exportable CSV/Excel spreadsheets and printable summaries for member accounts.
- Handover documentation and final testing.

---

## Verification Plan

### Automated Tests
- Run PHPUnit tests to verify no regressions in current features:
  ```powershell
  php artisan test
  ```

### Manual Verification
- Test Excel member import with sample records.
- Verify payment contributions division math on approved submissions.
- Test dashboard view rendering and Zelle receipt submission.
