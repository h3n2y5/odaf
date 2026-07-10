---
document_id: DB-V2-016
title: Validation Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-015
  - DB-V2-017
  - DB-V2-005
---

# Chapter 16

# Validation Metadata

---

# 1. Purpose

This chapter defines the Validation Repository of the Oracle Dynamic Application Framework (ODAF).

Validation Metadata describes business rules declaratively.

Validation SHALL be reusable across all platform components including:

- User Interface
- Dataset
- Workflow
- REST API
- Compiler
- Deployment
- Runtime
- AI Agent

Validation logic SHALL be implementation independent.

---

# 2. Design Objectives

Validation Metadata SHALL:

- centralize business rules;
- eliminate duplicated validation logic;
- support multiple execution engines;
- support rule composition;
- support deterministic execution;
- support enterprise governance.

---

# 3. Validation Architecture

```text
Business Requirement
        │
        ▼
Rule Set
        │
        ▼
Rule
        │
        ▼
Condition
        │
        ▼
Execution Engine
        │
        ▼
Validation Result
```

---

# 4. Aggregate Root

The Aggregate Root is

```text
Rule Set
```

Every Rule belongs to exactly one Rule Set.

---

# 5. Validation Meta Model

```text
Rule Set
        │
        ├── Rule
        ├── Condition
        ├── Expression
        ├── Parameter
        ├── Message
        ├── Severity
        ├── Execution Policy
        └── Runtime Mapping
```

---

# 6. Rule Set

A Rule Set groups logically related validation rules.

Examples

```text
Purchase Order Validation

Supplier Validation

Customer Validation

Inventory Validation

Financial Validation
```

Rule Sets SHALL be reusable.

---

# 7. Rule

A Rule represents one business constraint.

Examples

- Supplier must exist.
- Budget must be available.
- Quantity must be positive.
- Credit limit must not be exceeded.
- Delivery date must be after order date.

Rules SHALL remain atomic.

---

# 8. Conditions

Conditions determine when a Rule executes.

Examples

- Always
- On Create
- On Update
- On Delete
- Before Approval
- Before Deployment
- Before Compilation

Conditions SHALL be metadata-driven.

---

# 9. Validation Engines

ODAF supports multiple validation engines.

| Engine | Description |
|---------|-------------|
| EXPRESSION | Declarative expression |
| SQL | SQL validation |
| PLSQL | Oracle PL/SQL |
| REGEX | Pattern validation |
| DECISION_TABLE | Rule table |
| DECISION_TREE | Decision tree |
| SCRIPT | Future scripting engine |
| AI | Future AI validation |

Additional engines MAY be introduced through plugins.

---

# 10. Parameters

Validation MAY define parameters.

Examples

- Company
- Site
- Currency
- Language
- User
- Threshold

Parameter values SHALL be resolved at runtime.

---

# 11. Expressions

Expressions SHALL be declarative.

Examples

```text
AMOUNT > 0

ORDER_DATE <= DELIVERY_DATE

STATUS = 'ACTIVE'
```

Expressions SHALL be portable whenever possible.

---

# 12. Messages

Validation messages SHALL be metadata.

Each message SHALL define:

- code;
- severity;
- language;
- template;
- localization.

Messages SHALL support internationalization.

---

# 13. Severity

Supported severities include:

| Severity | Meaning |
|----------|---------|
| INFO | Informational |
| WARNING | Execution may continue |
| ERROR | Operation rejected |
| BLOCKER | Execution prohibited |

Severity SHALL influence runtime behavior.

---

# 14. Execution Policy

Execution policies define how validation is performed.

Examples

- Stop on first error
- Execute all rules
- Parallel evaluation
- Ordered evaluation

Execution policy SHALL be metadata-defined.

---

# 15. Validation Context

Validation SHALL execute within an explicit context.

Examples

- UI Request
- Dataset Execution
- Workflow Activity
- API Request
- Compiler
- Deployment

Rules MAY behave differently depending on context.

---

# 16. Runtime Mapping

Compilation transforms

```text
VAL_RULE_SET

↓

Compiler

↓

RT_VALIDATION

↓

Validation Engine
```

Rule identity SHALL be preserved.

---

# 17. Relationships

```text
Feature

owns

Rule Set

owns

Rule

owns

Condition

owns

Message

references

Dataset

references

Workflow

references

Security
```

---

# 18. Constraints

| ID | Constraint |
|----|------------|
| VAL-001 | Every Rule belongs to one Rule Set |
| VAL-002 | Every Rule uses one Engine |
| VAL-003 | Messages SHALL be localized |
| VAL-004 | Execution Policy SHALL be defined |
| VAL-005 | Rule evaluation SHALL be deterministic |

---

# 19. Traceability

```text
Business Requirement

↓

Rule Set

↓

Rule

↓

Runtime Validation

↓

Business Operation
```

---

# 20. Risks

Potential risks include:

- duplicated rules;
- conflicting validations;
- engine incompatibility;
- excessive rule complexity;
- inconsistent localization.

These risks SHALL be mitigated through compiler validation, governance, and reusable Rule Sets.

---

# 21. Summary

The Validation Repository establishes a metadata-driven rule engine that centralizes business validation across the ODAF platform.

By organizing validation into Rule Sets composed of reusable Rules, Conditions, Expressions, Messages, and Execution Policies, ODAF enables consistent enforcement of business constraints regardless of execution context.

This architecture eliminates duplicated validation logic, supports multiple execution engines, and provides a scalable foundation for enterprise governance and future extensions.

---

# Validation Repository Model

```text
Rule Set
        │
        ├── Rule
        ├── Condition
        ├── Expression
        ├── Parameter
        ├── Message
        ├── Severity
        ├── Execution Policy
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Rule Set | VAL_RULE_SET |
| Rule | VAL_RULE |
| Condition | VAL_CONDITION |
| Expression | VAL_EXPRESSION |
| Parameter | VAL_PARAMETER |
| Message | VAL_MESSAGE |
| Execution Policy | VAL_EXECUTION_POLICY |
| Runtime Validation | RT_VALIDATION |

---

# Next Document

➡ **17-Reporting-Metadata.md**

The next chapter defines the Reporting Repository, including report definitions, layouts, templates, output formats, scheduling, distribution, rendering pipelines, and metadata-driven report generation across the ODAF platform.