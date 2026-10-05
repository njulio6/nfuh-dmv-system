# Implementation Plan: Member Portal Njangi Payments Page & Navigation Restructuring

This plan outlines the changes required to introduce a dedicated **My Njangi Payments** page to the member portal, move the "Submit Njangi Play" form into a modal triggered from this page, and add a dedicated **Njangi** group to the member sidebar.

## Proposed Changes

### 1. Routes

#### [MODIFY] [routes/web.php](file:///c:/xampp/htdocs/nfuh-dmv-system/routes/web.php)
- Add a new route `GET /member/njangi-payments` mapped to `MemberPortalController@myPayments` with name `member.njangi-payments`.

---

### 2. Controllers

#### [MODIFY] [app/Http/Controllers/MemberPortalController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/MemberPortalController.php)
- Implement `myPayments(Request $request)` method:
  - Retrieve the current authenticated user and the corresponding `Member` record.
  - Setup query for `NjangiPaymentSubmission` for this member.
  - Apply search filters: search submissions by amount, notes, or session title/number.
  - Apply status filter (pending, approved, rejected).
  - Implement dynamic pagination (5, 10, 20, 30, 50 rows, default 10).
  - Query current active cycle context (`activeCycle`, `cycleMember`, `activeSession`, and list of scheduled/open `sessions`) to populate the Njangi Play submit form in the modal.
  - Format `$sessionsData` for Alpine dropdown just as we did on the dashboard.
  - Return the new view `njangi.member_payments`.
- Update `storeSubmission` redirection: redirect back to `member.njangi-payments` instead of `dashboard`.

#### [MODIFY] [app/Http/Controllers/DashboardController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/DashboardController.php)
- Remove retrieval of `$submissions` list from `index()` method since it will no longer be displayed on the dashboard.
- We still keep `activeSession` and `sessions` data if needed, but we can clean up any variables that were purely for the payment submissions list.

---

### 3. Navigation & Sidebar

#### [MODIFY] [resources/views/layouts/app.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/layouts/app.blade.php)
- In the desktop and mobile sidebars for **members** (non-admin):
  - Add a new group called **Njangi**.
  - Under this group, add a menu item **My Njangi Payment** pointing to `member.njangi-payments`.
  - Move the existing **Njangi Report** item (previously under "Reports") to this new **Njangi** group for cohesive grouping.
  - Add wildcard route expansion support for `member.njangi-payments*`.

---

### 4. Views

#### [NEW] [njangi/member_payments.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/njangi/member_payments.blade.php)
- Create a premium-styled table view page with:
  - Main Alpine.js state context (modals state, receipt popup details, form dropdown open/close).
  - Top search control bar, reload button, and status filter popover button.
  - **Submit Njangi Play** primary action button.
  - Interactive modal displaying the "Submit Njangi Play" form (moved from the dashboard), supporting attendance radios, Zelle receipt uploads, and optional notes.
  - Paginated submissions table using `<x-premium-table>` and `<x-premium-table-row>` displaying submission dates, session titles, amounts, attendance modes, statuses, and links to upload screenshot files.
  - Image receipt modal for previewing Zelle screenshot uploads in a lightbox style.

#### [MODIFY] [dashboard/member.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php)
- Remove the **My Payment Submissions** table card entirely from the left column.
- Remove the **Submit Njangi Play** form card entirely from the right column.
- Re-align the columns grid: the left column will now focus purely on **My Profile Summary**, and the right column will hold **Active Loan Progress** and **My Pending Requests**, keeping the page beautifully balanced and highly readable.

---

### 5. Verification Plan

#### Automated Tests
- Modify `MemberPortalTest.php` to:
  - Verify that the `member.njangi-payments` route is protected, accessible to authenticated members, and correctly renders the payments history.
  - Verify that search, filter, and pagination on this page work.
  - Verify that submitting a Njangi payment redirects to the payments page (instead of dashboard) with success messages.
  - Verify that the dashboard no longer displays the payment submission table or form.

#### Manual Verification
- Log in as a regular member.
- Navigate to the new "My Njangi Payment" sidebar link.
- Click the "Submit Njangi Play" button, fill in the modal form, upload a receipt, and submit.
- Verify that the modal closes, the page reloads, and the submission appears immediately in the paginated table below.
