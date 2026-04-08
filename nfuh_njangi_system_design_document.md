# NFUH Njangi System – Design & Explanation Document

## 1. Purpose
This document explains how the Njangi system works within NFUH and how it is being translated into a digital system. It serves as a reference for:
- Finance Committee members
- Stakeholders
- System development updates

---

## 2. Overview of Njangi Process

Njangi takes place during official NFUH sessions:
- Frequency: Monthly
- Timing: First Saturday of every month

Each session includes:
- A group of beneficiaries (typically 4 members)
- Contributions (“plays”) from other members

---

## 3. How Contributions Work (Real Process)

- Contributions are **flexible** (not fixed)
- Each member decides how much to contribute per beneficiary
- Example:
  - $100 per beneficiary → $400 total (for 4 beneficiaries)
  - $200 per beneficiary → $800 total

- Payments are made before or during the session
- Payments may be made via Zelle, bank transfer, or other methods

---

## 4. Core Concept: Reciprocal System

Njangi is based on **mutual contribution and refund logic**:

- When you benefit, other members contribute to you
- When those same members later benefit, you are expected to return (refund) what they gave you

Example:
- Member A gives you $500 when you benefit
- Later when Member A benefits, you must return $500 to Member A

---

## 5. End-of-Cycle Rule

- Njangi operates in a yearly cycle
- Members contribute throughout the cycle
- The last beneficiaries:
  - Do not need to refund afterward
  - Because they already contributed before benefiting

---

## 6. Key System Requirements

The system must track:

1. Njangi Cycle (year/round)
2. Monthly Sessions
3. Beneficiaries per session
4. Contributions (who paid who and how much)
5. Payment status (paid / pending)
6. Historical contributions (for refund tracking)

---

## 7. System Structure (High-Level)

### 7.1 Njangi Cycle
Represents a full year or round

### 7.2 Njangi Session
Represents a monthly NFUH session (first Saturday)

### 7.3 Beneficiaries
Members receiving funds during a session

### 7.4 Plays (Contributions)
Records each contribution from a member to a beneficiary

---

## 8. Example Scenario

Session: April 2026
Beneficiaries: Member A, B, C, D

Member PJ decides to play:
- $100 to A
- $100 to B
- $100 to C
- $100 to D

Total contributed: $400

System records 4 entries (one per beneficiary)

---

## 9. Reporting Goals

The system will allow:
- Viewing who contributed to whom
- Tracking how much each member received
- Tracking how much each member owes back
- Monthly and yearly summaries

---

## 10. Alignment with NFUH Goals

This system supports:
- Transparency
- Accuracy
- Centralized data
- Reduced manual work
- Better reporting for meetings

---

## 11. Current Status

Phase 1 Completed:
- Member registration
- Unique Member IDs
- Search and filtering

Phase 2 In Progress:
- Njangi (this module)
- Savings (next)
- Loans (later phase)

---

## 12. Next Steps

- Validate Njangi structure with Finance Committee
- Begin system implementation
- Test with real data
- Iterate based on feedback

---

## 13. Notes

This document reflects the current understanding of Njangi operations and will be updated as needed.

---

Prepared by: PJ Tech Systems / NFUH Finance System Project

