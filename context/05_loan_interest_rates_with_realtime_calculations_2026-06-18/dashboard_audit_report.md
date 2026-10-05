# Member Dashboard Feature Audit Report

This report evaluates whether all requested information is clearly presented on the member dashboard of the NFUH DMV system.

---

## Summary Checklist

| Requested Information | Status on Dashboard | Location/Notes |
| :--- | :---: | :--- |
| **Current Njangi cycle** | **Present** | "Current Njangi Cycle" card at top of dashboard |
| **Next session** | **Missing** | Passed to view but not rendered. Found in "Submit Njangi Play" modal on the Njangi Payments page |
| **Benefit order** | **Present** | "Benefit Position" card at top of dashboard |
| **Whether member has benefited** | **Present** | Dynamic status badge under "Benefit Position" card |
| **Contribution history** | **Missing** | Fully present on **Njangi Report** page (`/member/njangi-report`) |
| **Current standing** | **Partially Present** | Shows payout date or post-benefit repayment count. Details on **Njangi Report** page |
| **Refund expected / remaining balance** | **Missing** | Fully present on **Njangi Report** page under "Refund Standings" |
| **Savings balance** | **Present** | "Savings Balance" card at top of dashboard |
| **Loan eligibility** | **Present** | Dynamic badge under "Savings Balance" card |
| **Active loans** | **Present** | "Active Loan Balance" card & detailed progress cards on dashboard |
| **Repayment progress** | **Present** | Percentage progress bar in "Active Loan Progress" card |
| **Guarantors** | **Present** | List of names inside "Active Loan Progress" card |
| **Statements** | **Present** | "View & Print Statement" link inside "Active Loan Progress" card |
| **Pending requests and approval status** | **Present** | "Pending Requests" card & detailed list widget on dashboard |

---

## Detailed Audit Results

### 1. Njangi Cycle & Next Session
*   **Current Njangi Cycle**: **Present.** Displays the active cycle name and active round year.
    *   *Code Reference:* [member.blade.php:L162-L171](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L162-L171)
*   **Next Session**: **Missing from Dashboard.**
    *   *Detail:* While `$activeSession` and `$sessions` are fetched in `DashboardController@index` and passed to the view, they are not rendered in `member.blade.php`.
    *   *Where to find:* The next session details can be viewed when launching the "Submit Njangi Play" modal on the Njangi Payments page (`/member/njangi-payments`).
    *   *Code Reference:* [MemberPortalController.php:L106-L125](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/MemberPortalController.php#L106-L125) and [member_payments.blade.php:L520-L576](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/njangi/member_payments.blade.php#L520-L576)

### 2. Benefit Order & Benefited Status
*   **Benefit Order**: **Present.** Displays drawing position (`#{{ $benefitOrder }}`).
    *   *Code Reference:* [member.blade.php:L173-L179](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L173-L179)
*   **Has Benefited**: **Present.** Renders `Status: Benefited` (green) or `Status: Awaiting Draw` (gray).
    *   *Code Reference:* [member.blade.php:L180-L186](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L180-L186)

### 3. Contribution History & Standing
*   **Contribution History**: **Missing from Dashboard.**
    *   *Detail:* Controller retrieves `$contributionsMade` and `$contributionsReceived` but they are not output on the dashboard.
    *   *Where to find:* Access the **Njangi Report** page (`/member/njangi-report`). It has specific tabs for "Contributions Paid" and "Payouts Received".
    *   *Code Reference:* [member-report.blade.php:L212-L271](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member-report.blade.php#L212-L271)
*   **Current Standing**: **Partially/Indirectly Present.**
    *   *Detail:* Dashboard shows dynamic "Upcoming benefit session" or "Post-benefit repayment count" (e.g. "3 left of 10 sessions").
    *   *Where to find details:* Complete standings per beneficiary are on the **Njangi Report** page under "Refund Standings".
    *   *Code Reference:* [member.blade.php:L188-L221](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L188-L221) (Dashboard overview) and [member-report.blade.php:L170-L210](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member-report.blade.php#L170-L210) (Detailed report)

### 4. Refund Summary (Benefited Members)
*   **Amount Expected to Refund & Remaining Balance**: **Missing from Dashboard.**
    *   *Detail:* Calculated via `$refundSummary` in controller but not displayed on the main dashboard page.
    *   *Where to find:* Go to the **Njangi Report** page (`/member/njangi-report`) -> "Refund Standings" tab. It lists the refund obligations, amount already refunded, outstanding balance, and status per beneficiary.
    *   *Code Reference:* [member-report.blade.php:L170-L210](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member-report.blade.php#L170-L210)

### 5. Savings Balance & Loan Eligibility
*   **Savings Balance**: **Present.** Displays balance in USD.
    *   *Code Reference:* [member.blade.php:L223-L228](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L223-L228)
*   **Loan Eligibility**: **Present.** Dynamically checks balance against threshold and labels either `Loan Eligible` (green) or `Under $X Limit` (amber).
    *   *Code Reference:* [member.blade.php:L229-L242](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L229-L242)

### 6. Active Loans, Repayment Progress, Guarantors & Statements
*   **Active Loans**: **Present.** Shows total remaining balance and individual loan details.
    *   *Code Reference:* [member.blade.php:L244-L261](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L244-L261) (Overview) and [member.blade.php:L386-L466](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L386-L466) (Detail)
*   **Repayment Progress**: **Present.** Shows progress percentage and visual progress bar.
    *   *Code Reference:* [member.blade.php:L407-L420](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L407-L420)
*   **Guarantors**: **Present.** Displays the list of guarantors on each active loan.
    *   *Code Reference:* [member.blade.php:L436-L450](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L436-L450)
*   **Statements**: **Present.** Clickable link "View & Print Statement" for each active loan.
    *   *Code Reference:* [member.blade.php:L452-L463](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L452-L463)

### 7. Pending Requests & Approval Status
*   **Pending Requests**: **Present.** Shows overview total count and a detailed feed widget.
    *   *Code Reference:* [member.blade.php:L263-L284](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L263-L284) (Overview) and [member.blade.php:L468-L562](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L468-L562) (Details)
*   **Approval Status**: **Present.** Shows dynamic status badges (e.g. `Pending`, `Guarantor Review`, `Committee Review`).
    *   *Code Reference:* [member.blade.php:L536-L549](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/dashboard/member.blade.php#L536-L549)
