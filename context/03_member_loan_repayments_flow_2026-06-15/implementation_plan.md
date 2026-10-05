# Implementation Plan - Member Loan Repayments Flow

This plan details the implementation of a new flow allowing organization members to submit loan repayment requests (with proof of payment/screenshots) and administrators to review, approve, or reject these requests.

## User Review Required

> [!IMPORTANT]
> The admin can still log manual payments directly. Repayment requests submitted by members are stored in a new requests table, and upon admin approval, they are recorded as official payments in the ledger (`loan_repayments`).

## Proposed Changes

### Database & Migration

#### [NEW] [2026_06_15_180000_create_loan_repayment_requests_table.php](file:///c:/xampp/htdocs/nfuh-dmv-system/database/migrations/2026_06_15_180000_create_loan_repayment_requests_table.php)
Create a table `loan_repayment_requests` to store member repayment submissions pending approval:
- `id`
- `loan_request_id` (foreign key to `loan_requests`)
- `member_id` (foreign key to `members`)
- `organization_id` (foreign key to `organizations`)
- `amount` (decimal 10,2)
- `status` (enum: `'pending'`, `'approved'`, `'rejected'`, default `'pending'`)
- `screenshot_path` (string, storing proof of payment receipt image)
- `payment_date` (date)
- `payment_method` (string)
- `reference_number` (string, nullable)
- `notes` (text, nullable)
- `submitted_at` (timestamp)
- `reviewed_by` (foreign key to `users`, nullable)
- `reviewed_at` (timestamp, nullable)
- `review_note` (text, nullable)
- `timestamps`

### Backend Models & Logic

#### [NEW] [LoanRepaymentRequest.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanRepaymentRequest.php)
A new Eloquent model:
- Define belongsTo relationships for `member`, `organization`, `loanRequest` (relationship to `LoanRequest`), and `reviewer` (relationship to `User`).
- Handle attribute casting for `payment_date`, `submitted_at`, and `reviewed_at`.

#### [MODIFY] [LoanController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/LoanController.php)
Add controller actions for handling repayment requests:
- `requestRepayment(Request $request, LoanRequest $loan)` (Member-side submission):
  - Validates amount (must not exceed remaining balance), payment method, screenshot upload, reference number, notes.
  - Stores receipt screenshot to public storage.
  - Creates a `LoanRepaymentRequest` record in pending status.
- `myRepaymentRequests(Request $request)` (Member-side list):
  - Displays a list of the member's repayment requests (pending, approved, rejected) with search/filters.
- `adminRepaymentRequests(Request $request)` (Admin-side queue):
  - Displays all repayment requests for review, sorting pending to the top.
- `approveRepayment(Request $request, LoanRepaymentRequest $repaymentRequest)` (Admin approval):
  - Verifies request is pending.
  - Updates status to `approved`.
  - Creates a new official `LoanRepayment` ledger entry.
  - Automatically updates the loan status to completed if remaining balance falls to 0.
- `rejectRepayment(Request $request, LoanRepaymentRequest $repaymentRequest)` (Admin rejection):
  - Validates rejection reason.
  - Updates request status to `rejected` with `review_note`.

### Routing

#### [MODIFY] [web.php](file:///c:/xampp/htdocs/nfuh-dmv-system/routes/web.php)
Add routes for member and admin interfaces:
- Member Repay Flow:
  - `POST /member/loans/{loan}/repay-request` -> `requestRepayment` (name: `member.loans.repay-request`)
  - `GET /member/loans/repayment-requests` -> `myRepaymentRequests` (name: `member.loans.repayment-requests`)
- Admin Approval Flow:
  - `GET /loans/repayment-requests` -> `adminRepaymentRequests` (name: `loans.repayment-requests`)
  - `POST /loans/repayment-requests/{repaymentRequest}/approve` -> `approveRepayment` (name: `loans.repayment-requests.approve`)
  - `POST /loans/repayment-requests/{repaymentRequest}/reject` -> `rejectRepayment` (name: `loans.repayment-requests.reject`)

### UI Views & Navigation

#### [MODIFY] [app.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/layouts/app.blade.php)
- Add "Repayment Requests" to the Member Loans sidebar dropdown.
- Add "Repayment Requests" to the Admin Loans sidebar dropdown, with a dynamic pending count badge.

#### [NEW] [repayment_requests.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/repayment_requests.blade.php) (Admin Review page)
- UI page showing a premium table of repayment requests with actions to "Approve" (with note) or "Reject" (with reason note).
- Includes receipt image lightbox/preview modal.

#### [NEW] [member_repayment_requests.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/member_repayment_requests.blade.php) (Member Requests page)
- UI page showing a premium table of their submitted repayment requests and status (Pending, Approved, Rejected).
- Includes receipt preview option.

#### [MODIFY] [member_applications.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/member_applications.blade.php)
- Add "Submit Repayment" button in the Actions column for loans that are `active` or `defaulted`.
- Add "Submit Loan Repayment" modal with inputs for Amount, Payment Date, Payment Method (Zelle, Cash, Check, Other), Screenshot Proof, Reference Number, and Notes.

---

## Verification Plan

### Automated Tests
- Create a feature test file `tests/Feature/LoanRepaymentRequestTest.php` to verify:
  - Member can submit repayment request with file.
  - Member cannot submit payment exceeding remaining loan balance.
  - Admin can approve request, creating ledger record.
  - Admin can reject request with reason.
  - Validation rules are properly enforced.

### Manual Verification
- Log in as a member, view loans, and submit a repayment request with receipt image.
- Log in as an admin, see the request, review receipt, approve the request, and verify that the loan outstanding balance updates and request shows approved.
