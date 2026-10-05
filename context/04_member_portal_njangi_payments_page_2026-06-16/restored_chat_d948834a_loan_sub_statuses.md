# Restored Chat Context: Custom Loan Sub-statuses (Option A)

* **Chat ID**: `d948834a-168e-467e-a1e3-d99db76cfb97`
* **Topic**: Custom Loan Sub-statuses (Option A)
* **Goal**: Introduce a customizable sub-status (tag) system allowing administrators to classify active and defaulted loans under operational tags (e.g. *Grace Period*, *Legal Review*, *Written Off*) without affecting core financial metrics and logic.
* **Status**: Fully implemented, verified, and committed.

---

## 📋 Implementation Plan

### Database & Schema
- **Migration**: Added lookup table `loan_sub_statuses` (`id`, `name`, `color`, timestamps) and introduced a nullable `sub_status_id` column to `loan_requests` referencing it.
- **Model (`LoanSubStatus.php`)**: Eloquent model mapping to `loan_sub_statuses` table. Defines relationship: `loanRequests()` (has many `LoanRequest`).
- **Model (`LoanRequest.php`)**: Added `sub_status_id` to `$fillable` and defined the `subStatus()` relationship (belongs to `LoanSubStatus`).

### Controllers & Routing
- **`LoanController.php`**:
  - Eager-loads `subStatus` relation on index and member portal list requests.
  - `subStatusesIndex()`: Displays a dedicated page listing custom sub-statuses.
  - `storeSubStatus()` and `destroySubStatus()`: Manage custom sub-status tags.
  - `updateSubStatus()`: Updates the assigned tag of individual active/defaulted loans.
- **`web.php`**: Registered routes for managing sub-statuses (`POST /settings/loan-sub-statuses`, `DELETE /settings/loan-sub-statuses/{subStatus}`) and updating a loan's sub-status (`POST /loans/{loan}/sub-status`).

### UI & Templates
- **Dedicated Sub-Statuses Page (`resources/views/loans/sub_statuses.blade.php`)**: Integrated a clean management interface for sub-statuses.
- **System Settings (`resources/views/settings/edit.blade.php`)**: Removed the inline sub-status card from branding settings.
- **Navigation Menus (`resources/views/layouts/app.blade.php`)**:
  - Added a **Sub-Statuses** link under the main **Loans** admin sidebar menu, positioned after the **Overview** link.
  - Added the corresponding link under the mobile menu drawer under **Loans**.
- **Admin Loans List (`resources/views/loans/index.blade.php`)**:
  - Added sub-status tags/badges next to the main status badge if assigned.
  - Rendered inline dropdown forms that auto-submit on change for active or defaulted loans.
  - Added a status select selector to the filter modal that includes a sub-status filter.
- **Member Loans Queue (`resources/views/loans/member_applications.blade.php`)**: Displays sub-status badges and handles `defaulted` filter option cleanly.
- **Printable Statement (`resources/views/loans/statement.blade.php`)**: Renders the custom sub-status tag cleanly beside the main status for records.

---

## 🛠️ Task List Checklist

- [x] Create migration for `loan_sub_statuses` table and add `sub_status_id` to `loan_requests`
- [x] Run the migration
- [x] Create `LoanSubStatus` model
- [x] Update `LoanRequest` model (add relation `subStatus` and `sub_status_id` fillable)
- [x] Update `LoanController.php` with actions to manage sub-statuses (store, destroy, update loan sub-status)
- [x] Register new routes in `routes/web.php`
- [x] Update System Settings view to manage sub-statuses (list, add, delete form)
- [x] Update Admin Loans dashboard index view to assign sub-statuses and render badges
- [x] Update Member Loans dashboard and applications views to render sub-status badges
- [x] Update Loan Statement view to render sub-status badges
- [x] Create automated feature tests in `tests/Feature/LoanSubStatusTest.php` and verify they pass

---

## 🚦 Verification & Testing

Pest feature tests were built in `tests/Feature/LoanSubStatusTest.php`:
- Admin can create, list, and delete loan sub-statuses.
- Non-admin user cannot manage sub-statuses (GET route blocked with 403 Forbidden).
- Admin can update a loan request's sub-status, and deleting a sub-status sets the loan request's `sub_status_id` to null safely.
- Admin can edit a custom sub-status.
- Admin can transition a loan status to defaulted and back to active.
