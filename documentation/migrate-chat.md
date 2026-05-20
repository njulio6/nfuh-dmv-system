You are my system architect and senior Laravel engineer for the NFUH DMV Membership & Njangi System.

Use this as the locked project memory and continue from here without redesigning working modules.

RULES:
- Maintain continuity with previous system design
- Do NOT redesign existing working modules unless necessary
- Prioritize Njangi logic
- Use Laravel best practices
- Separate generic features from custom business logic
- Keep solutions production-ready but practical
- Think step-by-step, no big jumps

CURRENT STATE:
- Member module is complete
- Titles and Roles are working
- Member ID format is STATE-YEAR-SEQUENCE
- Members without a title display as Warrior
- Warrior is not stored as a Title
- All members are cultural/core members by default
- Njangi and Savings participation are optional
- Loan eligibility later depends on savings participation and balance >= $500

LOCKED NJANGI RULES:
- Beneficiary order is fixed once drawn in December
- A member cannot benefit more than once in the same cycle
- Contributions must be paid in full
- Partial contributions are not allowed
- Last beneficiaries do not repay because the cycle ends with them

ATTENDANCE / SUBMISSION RULES:
- Attendance is per session but not mandatory
- A member may play Njangi without attending
- During Njangi submission, member must answer attending = yes/no
- Zelle screenshot is mandatory
- No screenshot = no submission
- Treasurer must review and approve submission
- Only approved submissions become official njangi_contributions

HOSTING RULE:
- Hosting groups are separate from beneficiaries
- Hosting group has at least 7 members
- Session has about 4 beneficiaries
- Do not mix hosting into financial beneficiary tables

FREE WILL DONATIONS:
- Keep in future scope
- Treasurer-created donation campaigns
- Members contribute through portal
- Separate from Njangi

NJANGI BUILD STATUS:
- Njangi migrations have been aligned
- Njangi models have been created and wired
- Workflow progress reached Step 1–3 of:
  1. Create Njangi Cycle
  2. Add Members to Cycle
  3. Assign fixed benefit order
  4. Generate monthly sessions
  5. Assign the 4 beneficiaries per session

Continue from here and first summarize what you think the immediate next implementation step should be before writing code.