# Walkthrough: Custom Loan Sub-statuses (Option A)

This walkthrough documents the implementation of Option A for the Loan module, introducing a customizable sub-status (tag) system. This gives administrators the flexibility to classify active and defaulted loans under operational tags (e.g. *Grace Period*, *Legal Review*, *Written Off*) without affecting core financial metrics and logic.

## Changes Made

### 1. Database & Schema
- **Migration**: Added lookup table `loan_sub_statuses` (`id`, `name`, `color`, timestamps) and introduced a nullable `sub_status_id` column to `loan_requests` referencing it.

### 2. Models & Relationships
- **[LoanSubStatus](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanSubStatus.php)**: Created Eloquent model map for the lookup table.
- **[LoanRequest](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Models/LoanRequest.php)**: Added `sub_status_id` to `$fillable` and defined the `subStatus()` relationship.

### 3. Controllers & Routing
- **[LoanController](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/LoanController.php)**:
  - Eager-loaded `subStatus` relation on index and member portal list requests.
  - Implemented `subStatusesIndex()` to display a dedicated page listing custom sub-statuses.
  - Implemented `storeSubStatus()` and `destroySubStatus()` to manage custom sub-status tags.
  - Implemented `updateSubStatus()` to update the assigned tag of individual active/defaulted loans.
- **[web.php](file:///c:/xampp/htdocs/nfuh-dmv-system/routes/web.php)**: Registered the GET route `loans.sub-statuses` and corresponding actions.

### 4. UI & Templates
- **[Dedicated Sub-Statuses Page](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/sub_statuses.blade.php) [NEW]**: Integrated a clean management interface for sub-statuses on their own page.
- **[System Settings](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/settings/edit.blade.php)**: Removed the inline sub-status card from branding settings.
- **[Navigation Menus](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/layouts/app.blade.php)**:
  - Added a **Sub-Statuses** link under the main **Loans** admin sidebar menu, positioned after the **Overview** link.
  - Added the corresponding link under the mobile menu drawer under **Loans**.
- **[Admin Loans List](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/index.blade.php)**:
  - Added sub-status tags/badges next to the main status badge if assigned.
  - Rendered inline dropdown forms that auto-submit on change for active or defaulted loans.
  - Added a status select selector to the filter modal that includes a sub-status filter.
- **[Member Loans Queue](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/member_applications.blade.php)**: Displays sub-status badges and handles `defaulted` filter option cleanly.
- **[Printable Statement](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/loans/statement.blade.php)**: Renders the custom sub-status tag cleanly beside the main status for records.

---

## Verification & Testing

### Automated Tests
Pest feature tests were built/updated in **[LoanSubStatusTest.php](file:///c:/xampp/htdocs/nfuh-dmv-system/tests/Feature/LoanSubStatusTest.php)** and validated successfully:
- Admin can create, list, and delete loan sub-statuses on the new page.
- Non-admin user cannot manage sub-statuses (GET route is blocked with 403 Forbidden).
- Admin can update a loan request's sub-status, and deleting a sub-status sets the loan request's `sub_status_id` to null safely.

All 80 feature tests in the suite compile and pass perfectly:
```bash
PASS  Tests\Feature\LoanSubStatusTest
✓ admin can create, list, and delete loan sub-statuses
✓ non-admin user cannot manage sub-statuses
✓ admin can update a loan request sub-status and deleting the sub-status resets the loan relation to null

Tests:    80 passed (317 assertions)
Duration: 6.55s
```
