# Task Checklist - Loan Interest Rates with Real-time Calculations

- [x] Create and run the database migration `add_interest_columns_to_loan_requests_table`
- [x] Update `LoanRequest.php` model with casts, fillable fields, and the `total_repayable` logic
- [x] Modify `LoanController@approve` to validate and save `interest_rate` and `interest_type`
- [x] Update `status_list.blade.php` to add real-time Alpine.js math helpers and display elements inside the Approve modal
- [x] Write feature tests in `LoanTest.php` to verify interest calculations (both Flat and Duration-based)
- [x] Run the test suite to ensure everything passes successfully
