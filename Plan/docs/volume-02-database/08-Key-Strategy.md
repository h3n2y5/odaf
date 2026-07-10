---
document_id: DB-V2-008
title: Key Strategy
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-007
  - DB-V2-009
  - SAD-V1-014
---

# Chapter 08

# Key Strategy

---

# 1. Purpose

This chapter defines the identity strategy used throughout the Oracle Dynamic Application Framework (ODAF).

A consistent identity model is essential to support metadata management, runtime execution, deployment, auditing, compiler operations, and long-term maintainability.

Every metadata object SHALL follow the key strategy defined in this chapter.

---

# 2. Design Objectives

The identity model SHALL provide:

- global uniqueness;
- immutable identity;
- business readability;
- compiler friendliness;
- deployment stability;
- audit traceability;
- cross-platform compatibility.

---

# 3. Identity Layers

ODAF defines four identity layers.

```text
Canonical Name (Logical)

↓

Business Identifier

↓

Platform Identifier

↓

Physical Oracle Identifier
```

Each layer has a distinct responsibility.

---

# 4. Identity Types

| Identity | Purpose | Mutable |
|----------|----------|----------|
| OBJECT_ID | Internal platform identifier | No |
| OBJECT_CODE | Business code | No |
| OBJECT_NAME | Display name | Yes |
| Canonical Name | Global logical identifier | No |

---

# 5. OBJECT_ID

OBJECT_ID is the primary identity used internally by the platform.

Characteristics:

- globally unique;
- immutable;
- never reused;
- independent of business meaning;
- independent of Oracle table names.

Example

```text
01K0M4Q5QZ8K1N6J3P7A9R2B4C
```

OBJECT_ID SHALL remain unchanged throughout the lifecycle.

---

# 6. OBJECT_CODE

OBJECT_CODE represents the stable business identifier.

Examples

```text
CUSTOMER

SALES_ORDER

PURCHASE_REQUEST
```

Business code SHALL:

- be unique within its domain;
- remain stable;
- never contain display text.

---

# 7. OBJECT_NAME

OBJECT_NAME is intended for human-readable display.

Example

```text
Customer

Sales Order

Purchase Request
```

OBJECT_NAME MAY change without affecting runtime identity.

---

# 8. Canonical Object Name (CON)

Every Aggregate Root SHALL have a globally unique Canonical Object Name.

Examples

```text
APP.APPLICATION

APP.MODULE

APP.PAGE

UI.FIELD

DS.DATASET

WF.WORKFLOW

SEC.ROLE
```

Canonical Names SHALL be immutable.

---

# 9. Oracle Primary Keys

Every metadata table SHALL expose a single surrogate primary key.

Example

```text
OBJECT_ID
```

Oracle Primary Keys SHALL NOT use business codes.

---

# 10. Foreign Keys

Foreign Keys SHALL reference OBJECT_ID.

Example

```text
APPLICATION_ID

PAGE_ID

DATASET_ID

WORKFLOW_ID
```

Business codes SHALL NOT be used as foreign keys.

---

# 11. Business Keys

Business uniqueness SHALL be enforced separately.

Example

```text
APP_APPLICATION

OBJECT_ID

OBJECT_CODE

OBJECT_NAME
```

Constraint

```text
UK_APP_APPLICATION_CODE
```

Business uniqueness SHALL remain independent of primary keys.

---

# 12. Composite Keys

Composite primary keys SHALL NOT be used except where explicitly justified.

Composite unique constraints MAY be used.

Example

```text
APPLICATION_ID

MODULE_CODE
```

---

# 13. History Keys

History tables SHALL preserve the original OBJECT_ID.

History SHALL introduce:

```text
HISTORY_ID
```

Example

```text
AUD_APP_APPLICATION_HIST

HISTORY_ID

OBJECT_ID
```

This enables multiple historical versions of a single metadata object.

---

# 14. Runtime Keys

Compiled runtime metadata SHALL retain the originating OBJECT_ID.

Example

```text
APP_APPLICATION

OBJECT_ID

↓

RT_APPLICATION

OBJECT_ID
```

Compiler SHALL preserve identity.

---

# 15. Deployment Keys

Deployment packages SHALL define:

```text
PACKAGE_ID

PACKAGE_VERSION
```

Metadata identity SHALL remain unchanged across deployments.

---

# 16. Identifier Generation

OBJECT_ID generation SHALL satisfy:

- globally unique;
- distributed generation;
- collision resistant;
- sortable (recommended);
- runtime independent.

The implementation MAY use:

- UUIDv7;
- ULID;
- Snowflake-style identifiers;
- Oracle-generated identifiers.

The chosen implementation SHALL remain transparent to business logic.

---

# 17. Identity Stability

The following identifiers SHALL NEVER change.

- OBJECT_ID
- OBJECT_CODE
- Canonical Name

The following MAY change.

- OBJECT_NAME
- DESCRIPTION

---

# 18. Identity Traceability

Identity SHALL remain traceable throughout the platform.

```text
Business Requirement

↓

Metadata Object

↓

OBJECT_ID

↓

Compiler

↓

Runtime

↓

Audit

↓

Deployment
```

---

# 19. Identity Validation

Compiler SHALL validate:

- OBJECT_ID uniqueness;
- OBJECT_CODE uniqueness;
- Canonical Name uniqueness;
- Foreign Key consistency;
- Version compatibility.

Compilation SHALL fail when identity rules are violated.

---

# 20. Cross-Repository References

Cross-domain references SHALL always use OBJECT_ID.

Example

```text
UI_PAGE

APPLICATION_ID

↓

APP_APPLICATION

OBJECT_ID
```

Canonical Names MAY be used for documentation only.

---

# 21. Reserved Identifier Ranges

The following ranges are reserved.

| Range | Purpose |
|--------|----------|
| SYS_* | Platform |
| APP_* | Application |
| UI_* | User Interface |
| DS_* | Dataset |
| WF_* | Workflow |
| SEC_* | Security |
| RT_* | Runtime |
| AUD_* | Audit |

Future ranges SHALL be registered through Architecture Governance.

---

# 22. Risks

Potential risks include:

- duplicated identifiers;
- mutable business keys;
- inconsistent foreign keys;
- orphaned metadata;
- identifier reuse.

Compiler validation and governance SHALL mitigate these risks.

---

# 23. Summary

The ODAF Key Strategy separates logical identity, business identity, runtime identity, and physical implementation.

By distinguishing OBJECT_ID, OBJECT_CODE, OBJECT_NAME, and Canonical Object Name, the platform achieves stable metadata references, deterministic compilation, reliable deployments, and long-term architectural consistency.

This strategy enables metadata to evolve independently from presentation and implementation concerns while preserving referential integrity across the entire platform.

---

# Identity Matrix

| Identifier | Scope | Mutable | Used By |
|------------|-------|---------|---------|
| OBJECT_ID | Platform | No | Oracle, Compiler, Runtime |
| OBJECT_CODE | Business | No | Business Rules |
| OBJECT_NAME | UI | Yes | Renderer, Studio |
| Canonical Name | Architecture | No | Documentation, Compiler, Plugin API |

---

# Next Document

➡ **09-Versioning-Strategy.md**

The next chapter defines metadata versioning, semantic version management, compatibility rules, deployment versions, runtime versions, and lifecycle evolution across the Oracle Metadata Repository.