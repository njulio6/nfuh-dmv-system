# Walkthrough: Dynamic Loan Interest Rates & Calculations

This document summarizes the changes, features, and verification of the dynamic interest rate configurations on loan approvals.

---

## 🛠️ Changes Implemented

### 🗄️ Database & Schema Changes
- Generated and executed migration [2026_06_17_200325_add_interest_columns_to_loan_requests_table.php](file:///c:/xampp/htdocs/nfuh-dmv-system/database/migrations/2026_06_17_200325_add_interest_columns_to_loan_requests_table.php):
  - Added `interest_rate` (decimal, `5,2`, default `0.00`) and `interest_type` (string, default `'flat'`) to the `loan_requests` table.

### 🧩 Models & Calculations
- **Casts & Attributes:** Updated [LoanRequest.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanRequest.php):
  - Cast `interest_rate` to `float` and `interest_type` to `string`.
  - Added virtual attribute `total_repayable` to compute dynamic interest charges on-the-fly:
    - **Flat Percentage**: `Principal + (Principal * Rate / 100)`
    - **Duration-Based (Annualized)**: `Principal + (Principal * Rate / 100 * Months / 12)`
  - Updated `remaining_balance` virtual attribute to evaluate as `total_repayable - repayments_paid`, ensuring any interest charges are fully incorporated into outstanding balances.

### ⚙️ Controller Logic
- **Approvals Validation:** Updated `approve` method in [LoanController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/LoanController.php):
  - Added validation rule ensuring `interest_rate` (numeric, min: 0, max: 100) and `interest_type` (string, in: flat, duration_based) are present.
  - Saves the selected interest rate and type directly to the approved `LoanRequest` record.

### 🖥️ Admin Interface & Real-time Calculator
- **Alpine.js Calculations:** Updated [status_list.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/status_list.blade.php):
  - Integrated Alpine state values `reviewInterestRate` and `reviewInterestType`.
  - Added `getInterestAmount()`, `getTotalRepayable()`, and `getFormulaText()` to perform instantaneous client-side calculations as the admin changes inputs.
- **Cycle Members Table-Style Dropdown Form Control:** 
  - Styled the **Interest Type** dropdown options list to match the clean status toggle dropdowns (border-free transparent buttons, hover colors) from `njangi/cycles/show.blade.php`.
  - Adjusted the height (`py-3`), padding, text sizes (`text-sm font-medium`), and rounded corners (`rounded-xl`) of the trigger button to match the text input fields (`x-premium-input`) in the same modal. This aligns all field dimensions perfectly.
  - Added a numeric input field for **Interest Rate (%)**.
- **Visual Stacking Context Fix:**
  - Added the `z-20` class to the wrapper elements of the custom dropdowns (`Payment Method` and `Interest Type`) in `status_list.blade.php`. This fixes the stacking order context, ensuring that the options panel expands *on top of* adjacent `relative` positioned input containers rather than behind them.
- **Visual Breakdown Box:**
  - Implemented a live calculated breakdown card in the approval modal displaying the Principal, Rate, calculated Interest Amount, and Total Repayable.
  - Prints the exact mathematical formula in plain English for transparency (e.g. `Formula: Principal ($1,000.00) × Rate (10.00%) × Duration (6/12 Months) = $50.00 Interest`).
- **Responsive Layout Fix (Global Modal Scrolls):**
  - Applied `max-h-[90vh]` and `overflow-y-auto` style properties to **all** modal cards in the application. This prevents layout overflow and button clipping issues across the entire application interface, rendering a consistent experience on shorter browser viewports or mobile/tablet heights.

---

## 🧪 Automated Feature Verification

We wrote two new test cases in [LoanTest.php](file:///c:/xampp/htdocs/nfuh-dmv-system/tests/Feature/LoanTest.php) to assert calculation correctness:

1. **Flat Interest Calculation:**
   - Verifies that a $1,000.00 loan approved at a 10.00% flat rate evaluates to a total repayable amount of $1,100.00.
2. **Duration-Based Interest Calculation:**
   - Verifies that a $1,000.05 loan with a 6-month term approved at a 10.00% annualized simple interest rate evaluates to a total repayable amount of $1,050.00.
3. **Repayment Deductions:**
   - Asserts that subsequent repayments subtract from the newly calculated interest-inclusive balance correctly.

### Suite Execution Output:
```powershell
C:\xampp\php\php.exe artisan test --filter=LoanTest

   PASS  Tests\Feature\LoanTest
  ✓ member cannot request loan if savings balance is under dynamic settings threshold                            0.73s  
  ✓ member can request loan with sufficient savings and it defaults to pending_guarantors                        0.03s  
  ✓ loan transitions to pending_committee only when all guarantors approve                                       0.03s  
  ✓ admin can approve and disburse loan, and record repayment lifecycle                                          0.06s  
  ✓ admin can approve loan and override repayment term duration_months                                           0.03s  
  ✓ member can view their own loan applications page with search and status filters                              0.07s  
  ✓ admin can access loan dashboard, status lists, repayments log, and member statements                         0.69s  
  ✓ non-admin is forbidden from accessing admin loan dashboard, status lists, repayments log, and statements     0.06s  
  ✓ member can view their own statement but not other members statements                                         0.03s  
  ✓ admin can approve loan with flat interest rate and calculations are correct                                  0.03s  
  ✓ admin can approve loan with duration based interest rate and calculations are correct                        0.03s  

  Tests:    11 passed (89 assertions)
  Duration: 1.97s
```
