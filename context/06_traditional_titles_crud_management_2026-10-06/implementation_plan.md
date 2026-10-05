# Implementation Plan - Dedicated Traditional Titles CRUD Management

This plan outlines the steps to build a premium CRUD panel for managing Traditional Titles (`member_ranks` table) under the Admin panel, matching the style and layout of the existing Member CRUD.

## Proposed Changes

### Routing & Navigation

#### [MODIFY] [web.php](file:///c:/xampp/htdocs/nfuh-dmv-system/routes/web.php)
* Register resource routes for titles:
  ```php
  Route::resource('titles', \App\Http\Controllers\TitleController::class);
  ```
  inside the existing `Route::middleware(['auth', 'admin'])->group(...)` group.

#### [MODIFY] [app.blade.php](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/layouts/app.blade.php)
* Map the routes matching `titles.*` to the title `"Traditional Titles"` under the route-based title resolver block.
* In the desktop sidebar (around line 455), add a menu link for "**Traditional Titles**" directly under "Members".
* In the mobile sidebar (around line 928), add a menu link for "**Traditional Titles**" directly under "Members".

---

### Backend Logic

#### [NEW] [TitleController.php](file:///c:/xampp/htdocs/nfuh-dmv-system/app/Http/Controllers/TitleController.php)
* Implement a resource controller `TitleController` with standard CRUD actions:
  * `index(Request $request)`: Retrieve, paginate, and search titles (ranks) by name. Use `withCount('members')` to show how many members have that title.
  * `create()`: Render the creation view.
  * `store(Request $request)`: Validate and save a new traditional title (validations: `name` is required, unique, and max:255; `level` is required, integer, and min:0).
  * `edit(MemberRank $title)`: Render the edit view.
  * `update(Request $request, MemberRank $title)`: Validate and update the rank details.
  * `destroy(MemberRank $title)`: Delete the traditional title. (Because of database `nullOnDelete` constraint on `members.rank_id`, deleting a rank will automatically reset affected members to "Warrior (Default)" without errors).

---

### User Interface Views

#### [NEW] [index.blade.php (titles)](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/titles/index.blade.php)
* Build the list view matching the design of `members/index.blade.php`:
  * Control bar with a Search input and an "Add Title" button.
  * Premium data table showing:
    * **Name** (with a clean leading initial circle)
    * **Level** (the hierarchical order/priority)
    * **Active Members** (count of members holding this title with a count badge)
    * **Actions** (inline "Edit" button and "Delete" button)
  * Standard pagination footer.
  * Alpine-powered confirmation modal for deleting titles.

#### [NEW] [create.blade.php (titles)](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/titles/create.blade.php)
* Build the title creation form using standard premium design components (`<x-premium-header>`, `<x-premium-card>`, `<x-premium-input>`, `<x-premium-button>`).
* Form inputs: Title Name, Level (Hierarchy Rank).

#### [NEW] [edit.blade.php (titles)](file:///c:/xampp/htdocs/nfuh-dmv-system/resources/views/titles/edit.blade.php)
* Build the title editing form pre-filled with the active title's details.

---

## Verification Plan

### Automated Tests
* Run `c:\xampp\php\php.exe artisan test tests/Feature/LoanTest.php` to ensure existing systems function correctly.

### Manual Verification
1. Log in as an admin and navigate to the sidebar under **Membership**.
2. Click the new **Traditional Titles** link and verify the CRUD list view renders properly.
3. Click the **Add Title** button, fill out the form, and verify that the title is created and displays in the table.
4. Try creating a member or editing an existing member; verify that the newly added traditional title appears in the member's dropdown select field.
5. Edit a title and verify that changes are saved.
6. Delete a title that is assigned to a member. Verify that the title is removed and that the member's profile is automatically updated to the default "Warrior" title.
