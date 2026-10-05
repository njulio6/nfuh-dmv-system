# Implementation Plan: Custom Loan Sub-statuses (Option A)

This plan details the implementation of Option A for the Loan module, introducing a customizable sub-status (tag) system. This gives administrators the flexibility to classify loans under operational states (e.g. *Grace Period*, *Legal Review*, *Written Off*) without breaking the core financial metrics and calculation logic.

## User Review Required

> [!IMPORTANT]
> - Database changes: We will add a new `loan_sub_statuses` lookup table and a foreign key `sub_status_id` in `loan_requests`.
> - Core loan statuses (`pending_guarantors`, `pending_committee`, `approved`, `active`, `completed`, `rejected`, `defaulted`) remain system-controlled.
> - Admins can create and delete sub-statuses from the System Settings page, and apply them directly to individual loans in the Loans Management index.

---

## Proposed Changes

### Database & Models

#### [NEW] [2026_06_14_000006_create_loan_sub_statuses_table.php](file:///c:/xampp/htdocs/nfuh-dmv-system/database/migrations/2026_06_14_000006_create_loan_sub_statuses_table.php)
- Schema for `loan_sub_statuses`:
  - `id` (primary key)
  - `name` (string, unique, e.g. "Grace Period", "Legal Action")
  - `color` (string, default "slate", Tailwind/vanilla compatibility)
  - Timestamps
- Schema update for `loan_requests`:
  - Add `sub_status_id` (foreign key pointing to `loan_sub_statuses`, nullable, set null on delete).

#### [NEW] [LoanSubStatus.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanSubStatus.php)
- Eloquent model mapping to `loan_sub_statuses`.
- Define relationships: `loanRequests()` (has many `LoanRequest`).

#### [MODIFY] [LoanRequest.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanRequest.php)
- Add `$fillable` attribute `sub_status_id`.
- Define relationship: `subStatus()` (belongs to `LoanSubStatus` via `sub_status_id`).
- Update active query scopes or calculations (e.g. `getOutstandingLoanBalanceAttribute()`) to treat `defaulted` state as active debt.

---

### Controllers & Routing

#### [MODIFY] [LoanController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/LoanController.php)
- **`index()`**: Eager-load `subStatus` relation on loan requests and pass all `LoanSubStatus` records to the view.
- **`updateSubStatus(Request $request, LoanRequest $loan)`** [NEW Action]:
  - Validates `sub_status_id` exists in custom table (or is null).
  - Updates the loan request's `sub_status_id`.
- **`storeSubStatus(Request $request)`** [NEW Action]:
  - Validates and creates a new custom `LoanSubStatus`.
- **`destroySubStatus(LoanSubStatus $subStatus)`** [NEW Action]:
  - Deletes the custom sub-status.

#### [MODIFY] [web.php](file:///c:/xampp/htdocs/nfuh-dmv-system/routes/web.php)
- Register routes:
  - Update loan's sub-status: `POST /loans/{loan}/sub-status`
  - Manage sub-status list: `POST /settings/loan-sub-statuses` and `DELETE /settings/loan-sub-statuses/{subStatus}`

---

### Views & Layouts

#### [MODIFY] [settings/edit.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/settings/edit.blade.php)
- Add a new card interface to list existing custom sub-statuses, with delete buttons, and a simple form to add new ones.

#### [MODIFY] [loans/index.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/index.blade.php)
- Display the custom sub-status badge next to the main status badge if assigned.
- Add an inline dropdown selector to change the sub-status of any active or defaulted loan.

#### [MODIFY] [loans/member.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/member.blade.php) and [loans/member_applications.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/member_applications.blade.php)
- Display the custom sub-status badge next to the main status.

#### [MODIFY] [loans/statement.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/statement.blade.php)
- Render the sub-status tag in statements.

---

## Verification Plan

### Automated Tests
- Create Pest test in `tests/Feature/LoanSubStatusTest.php` to verify:
  - Only admin can create or delete sub-statuses.
  - Applying sub-status updates the loan request.
  - Deleting a sub-status sets the loan request's `sub_status_id` to null safely.
  - Custom sub-statuses do not alter outstanding balance calculations.

### Manual Verification
- Access the Settings page as admin, add "Grace Period" as a sub-status.
- Go to Loans overview, assign "Grace Period" to a loan.
- Confirm the badge is rendered on both the Admin index and the Member loan center dashboard.
