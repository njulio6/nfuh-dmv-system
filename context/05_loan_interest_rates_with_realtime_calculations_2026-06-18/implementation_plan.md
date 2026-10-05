# Implementation Plan - Loan Interest Rates with Real-time Calculations

Implement customizable loan interest rates and types directly in the loan approval modal. When approving a loan request, the administrator will specify the interest rate (percentage) and the interest calculation type (Flat vs. Duration-Based). 

The modal will show **real-time calculations** of the interest and total repayable amount, along with the text formula being applied, so the admin sees the exact math before confirming approval.

---

## Proposed Changes

### Database & Models

#### [NEW] [2026_06_18_000000_add_interest_columns_to_loan_requests_table.php](file:///c:/xampp/htdocs/nfuh-dmv-system/database/migrations/2026_06_18_000000_add_interest_columns_to_loan_requests_table.php)
- Add `interest_rate` and `interest_type` columns to the `loan_requests` table:
  - `decimal('interest_rate', 5, 2)->default(0.00)`
  - `string('interest_type', 50)->default('flat')` (Values: `'flat'` or `'duration_based'`)

#### [MODIFY] [LoanRequest.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanRequest.php)
- Add `interest_rate` and `interest_type` to `$fillable` and cast:
  - `'interest_rate' => 'float'`
  - `'interest_type' => 'string'`
- Define a virtual attribute `total_repayable` to compute interest:
  - **Flat calculation**: `Principal + (Principal * InterestRate / 100)`
  - **Duration-based calculation**: `Principal + (Principal * InterestRate / 100 * DurationMonths / 12)`
- Update `getRemainingBalanceAttribute()` to subtract paid repayments from `total_repayable`.

---

### Loan Approvals & View (Real-time Calculations)

#### [MODIFY] [LoanController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/LoanController.php)
- Update the `approve` method to validate:
  - `interest_rate` (required, numeric, min: 0, max: 100)
  - `interest_type` (required, in: `'flat'`, `'duration_based'`)
- Update `interest_rate` and `interest_type` on the approved `LoanRequest` record.

#### [MODIFY] [status_list.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/status_list.blade.php)
- Update Alpine.js variables inside `x-data` to track:
  - `reviewInterestRate` (defaults to `0`)
  - `reviewInterestType` (defaults to `'flat'`)
- Add helper methods in `x-data` to compute calculations dynamically:
  - `getInterestAmount()`: Computes the interest value.
    - If Flat: `amount * (rate / 100)`
    - If Duration-Based: `amount * (rate / 100) * (months / 12)`
  - `getTotalRepayable()`: Computes `amount + interest`.
  - `getFormulaText()`: Returns a text string displaying the exact math formula applied.
    - *Example Flat*: `"Math: $1,000.00 Principal + (10.00% of $1,000.00) = $1,100.00"`
    - *Example Duration-Based*: `"Math: $1,000.00 Principal + (10.00% of $1,000.00 * 6/12 months) = $1,050.00"`
- Add form fields in Approve modal:
  - **Interest Type selection**: A custom styled dropdown/select for `Flat Percentage` and `Duration-Based (Annualized)`.
  - **Interest Rate (%) input**: Pre-filled with `0`.
- Add a **Dynamic Breakdown Box** in the modal:
  - Shows Principal, Calculated Interest, Total Repayable, and the Formula breakdown string in real-time as the admin edits the fields.

---

## Verification Plan

### Automated Tests
- Create test assertions in `LoanTest.php` to verify:
  - Admin can approve a loan and set `interest_type => 'flat'` and `interest_rate => 10`. Verify remaining balance evaluates to \$1,100 (for \$1,000 principal).
  - Admin can approve a loan and set `interest_type => 'duration_based'` and `interest_rate => 10` for a 6-month term. Verify remaining balance evaluates to \$1,050.
- Run `C:\xampp\php\php.exe artisan test --filter=LoanTest`.

### Manual Verification
- Go to **Pending Committee** queue.
- Click **Approve** on a loan request.
- Choose `Flat Percentage`, enter `10.00`, and inspect the real-time breakdown box. Verify that it instantly calculates the interest to \$100.00, total to \$1,100.00, and shows the flat formula string.
- Select `Duration-Based (Annualized)`, change the rate, and verify that it recalculates in real-time using months and prints the correct annualized formula.
