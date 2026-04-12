# Member Module Design — NFUH DMV Membership & Njangi System

## Purpose

This document captures the approved design changes for the Member module before implementation.
The goal is to enhance the current working Member module without breaking existing CRUD behavior,
while preparing the system for Njangi, Savings, and future Loans.

---

## 1. Design Goals

The Member module is not being rebuilt. It is being enhanced in a controlled way.

### Required outcomes
- Rename Rank to Title in the UI
- Add geographic grouping support
- Add seniority support
- Add participation classification
- Expand next-of-kin information
- Change member ID format
- Seed 10 dummy members for testing
- Keep current CRUD stable

---

## 2. Keep vs Change

### Keep as-is
The following are already working and should remain intact:

- `members` table as the core table
- `member_ranks` table in the database
- `member_roles` and pivot structure
- current Member CRUD flow
- search/filter/table patterns
- SQLite compatibility
- existing model/controller/view structure unless a change is required

### Change now
The following enhancements are required:

- UI wording: `Rank` → `Title`
- Add member state
- Add join date
- Add next-of-kin email
- Add next-of-kin address
- Add participation flags
- Redesign member ID generator

---

## 3. Target Member Data Model

### Existing member fields
The current module already includes some or all of the following:

- `first_name`
- `last_name`
- `email`
- `phone`
- `address`
- `next_of_kin_name`
- `next_of_kin_phone`
- `rank_id`
- `status`
- `member_code`

### New fields to add to `members`

- `state_code`
- `join_date`
- `next_of_kin_email`
- `next_of_kin_address`
- `participates_in_njangi`
- `participates_in_savings`
- `participates_in_cultural`

### Field definitions

#### `state_code`
- Type: string
- Length: 2
- Allowed values: `MD`, `VA`, `DC`
- Required: yes

Purpose:
- grouping
- member ID generation
- hosting coordination

#### `join_date`
- Type: date
- Required: yes

Purpose:
- derive join year
- support seniority
- enable future reporting

#### `next_of_kin_email`
- Type: nullable string

#### `next_of_kin_address`
- Type: nullable text or string

#### `participates_in_njangi`
- Type: boolean
- Default: false

#### `participates_in_savings`
- Type: boolean
- Default: false

#### `participates_in_cultural`
- Type: boolean
- Default: true if that reflects real membership usage; otherwise false

---

## 4. Title Design

### Decision
Do not rename the database table or foreign key yet.

Keep:
- `member_ranks`
- `rank_id`

But in the application UI:
- show `Title`

### Why
This avoids risky refactoring before there is business value.

### UI behavior
- Forms label: `Title`
- Table column: `Title`
- Details page label: `Title`
- Empty value display: `-`

### Validation
- Title is optional
- A member may have no title

---

## 5. Member ID Strategy

### Required format
`STATE-YEAR-SEQUENCE`

Examples:
- `MD-2026-001`
- `VA-2026-002`
- `DC-2025-004`

### Rules
- `STATE` comes from `state_code`
- `YEAR` comes from `join_date`
- `SEQUENCE` increments within the same state + year group

### Examples
If existing codes include:
- `MD-2026-001`
- `MD-2026-002`

Then the next Maryland member joining in 2026 becomes:
- `MD-2026-003`

If first Virginia member joins in 2026:
- `VA-2026-001`

### Storage decision
Keep using the existing `member_code` field unless there is a strong reason to rename it.

### Generation timing
Generate member code:
- during create
- after `state_code` and `join_date` validation succeed

### Edit rule
Once created, member code should remain stable.
Editing `state_code` or `join_date` should not silently regenerate the code.

If correction is required later, it should be handled through a deliberate admin process.

---

## 6. Participation Model Design

Use simple boolean fields in `members` for now.

### Participation flags
- `participates_in_njangi`
- `participates_in_savings`
- `participates_in_cultural`

### Why booleans now
- practical
- easy to implement
- enough for current phase
- avoids unnecessary complexity

### Future note
This can later evolve into structured enrollments if needed.

---

## 7. Validation Rules

### Create member validation

#### Required
- `first_name`
- `last_name`
- `phone`
- `address`
- `state_code`
- `join_date`
- `status`

#### Optional
- `email`
- `rank_id` (Title)
- `next_of_kin_name`
- `next_of_kin_phone`
- `next_of_kin_email`
- `next_of_kin_address`

#### Participation flags
Recommended business rule:
At least one participation type should be selected:
- Njangi
- Savings
- Cultural

If temporary flexibility is needed during development, allow none for now and tighten later.

### Specific validation expectations
- `state_code` must be one of: `MD`, `VA`, `DC`
- `email` nullable but unique if present
- `join_date` must be a valid date
- `rank_id` nullable but must exist in `member_ranks`
- role IDs must exist in `member_roles`

---

## 8. Duplicate Handling Policy

### Manual create flow
The form should avoid obvious duplicates.

Recommended warnings:
- same email already exists
- same full name + phone already exists

Keep this lightweight for manual CRUD.
Stronger duplicate handling will come with the import module later.

---

## 9. UI Changes Required

### Member list page
Add columns for:
- Member ID
- Title
- State
- Join Date
- Participation summary

Examples of participation summary:
- `Njangi, Savings`
- `Savings only`
- `Cultural only`

### Member create/edit form
Add fields for:
- Title
- State
- Join Date
- Next of kin email
- Next of kin address
- Participation checkboxes

### Member details page
Display:
- full member code
- Title
- State
- Join Date
- full next-of-kin section
- participation flags

---

## 10. Filtering Additions

The member list should eventually support filters for:
- state
- title
- status
- njangi participation
- savings participation
- cultural participation

State and participation filters are the highest priority.

---

## 11. Seeder Design for 10 Dummy Members

These records should be intentionally useful for testing.

### Distribution
- 4 Maryland
- 3 Virginia
- 3 DC

### Participation mix
- 3 Njangi + Savings
- 2 Njangi only
- 2 Savings only
- 3 Cultural only

### Titles mix
- some with titles
- some without titles

### Roles mix
- some with roles
- some without roles

### Join year mix
Use at least:
- 2023
- 2024
- 2025
- 2026

This helps test:
- member code generation
- grouping
- sorting by seniority
- participation filters

---

## 12. Migration Design Guidance

Use one focused migration to add the new member fields.

### Members table additions
- `state_code`
- `join_date`
- `next_of_kin_email`
- `next_of_kin_address`
- `participates_in_njangi`
- `participates_in_savings`
- `participates_in_cultural`

### Existing data handling
Because development is still in the local/dummy data stage, use safe defaults and a migration path that does not break existing records.

### Recommended approach
Initially allow:
- `state_code` nullable
- `join_date` nullable

Then tighten later after the system is seeded and stable.

---

## 13. Model Change Guidance

### Member model
Update:
- `$fillable`
- `$casts`

### Casts to add
- `join_date` => `date`
- participation fields => `boolean`

### Possible future accessor
A computed label for participation summary can be added later, for example:
- `Njangi, Savings`
- `Cultural only`

Not required immediately.

---

## 14. Member ID Generator Design

Do not bury this logic in a controller.

### Recommended structure
Use a dedicated generator:
- small service class
- helper
- or model method if staying simple

### Inputs
- `state_code`
- `join_date`

### Output
- next `member_code`

### Behavior
- derive year from `join_date`
- search existing records with same `state-year`
- increment sequence
- pad sequence to 3 digits

This logic must be centralized so manual create and future import use the same rule.

---

## 15. What Not to Do Yet

Avoid the following for now:
- renaming `member_ranks` table
- renaming `rank_id` column
- creating a participation join table
- mixing loan logic into members
- auto-regenerating member IDs on edit
- starting import before finishing member structure
- starting Njangi logic before member enhancements are complete

---

## 16. Recommended Implementation Order

1. Create migration for new member fields
2. Update `Member` model:
   - fillable
   - casts
3. Update create/edit validation rules
4. Update forms and views:
   - Title label
   - new fields
   - participation checkboxes
5. Implement member code generator
6. Use generator in member create flow only
7. Create 10 dummy members seeder
8. Test:
   - create member
   - edit member
   - member code generation
   - title empty display
   - participation display
   - list filtering behavior

---

## 17. Architectural Conclusion

The Member module should now be treated as having five domains:

- identity
- hierarchy/title
- geography
- participation
- emergency contact

This is the right foundation for the Njangi, Savings, and Loan modules that will follow.

---

## 18. Immediate Next Step

Before coding, generate the exact migration and model change plan:
- migration file scope
- column types
- nullable/default strategy
- model fillable changes
- model cast changes
- member code generator touchpoints
