---
document_id: DB-V2-007
title: Universal Metadata Object (UMO)
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-006
  - DB-V2-008
  - SAD-V1-014
---

# Chapter 07

# Universal Metadata Object (UMO)

---

# 1. Purpose

This chapter defines the Universal Metadata Object (UMO), the logical metadata contract implemented by every Aggregate Root within the Oracle Dynamic Application Framework (ODAF).

UMO provides a common semantic model that enables consistent metadata management across all repositories.

UMO is a logical specification.

It does not require every Oracle table to have an identical physical structure.

---

# 2. Motivation

Without a common metadata contract:

- every metadata object behaves differently;
- compiler implementation becomes complex;
- Studio requires custom editors;
- deployment becomes inconsistent;
- auditing becomes fragmented.

UMO eliminates these inconsistencies.

---

# 3. Design Goals

UMO SHALL provide:

- common identity;
- common lifecycle;
- common versioning;
- common auditing;
- common ownership;
- common deployment behavior;
- common governance.

---

# 4. UMO Architecture

```text
                    Universal Metadata Object
                              │
    ┌───────────────┬───────────────┬───────────────┐
    ▼               ▼               ▼               ▼
Application       Dataset       Workflow       Security
    │               │               │               │
 Page           SQL Definition     State          Role
    │               │               │               │
 Field         Parameter        Transition     Permission
```

Every Aggregate Root SHALL conform to the UMO contract.

---

# 5. UMO Contract

Every Aggregate Root SHALL logically expose the following attributes.

| Attribute | Description |
|------------|-------------|
| OBJECT_ID | Globally unique identifier |
| OBJECT_CODE | Stable business code |
| OBJECT_NAME | Display name |
| OBJECT_TYPE | Metadata type |
| OBJECT_VERSION | Semantic version |
| STATUS | Lifecycle state |
| OWNER_ID | Responsible owner |
| EFFECTIVE_FROM | Activation date |
| EFFECTIVE_UNTIL | Expiration date |
| CREATED_AT | Creation timestamp |
| CREATED_BY | Creator |
| UPDATED_AT | Last modification timestamp |
| UPDATED_BY | Last modifier |

These attributes define the logical metadata contract.

---

# 6. Canonical Identity

Every metadata object SHALL have two identities.

## Physical Identity

Example

```text
APP_APPLICATION
```

## Canonical Identity

Example

```text
APP.APPLICATION
```

Canonical identities SHALL be globally unique.

---

# 7. Metadata Identity

Each metadata object SHALL expose the following identifiers.

| Identifier | Scope |
|------------|------|
| OBJECT_ID | Internal platform |
| OBJECT_CODE | Business identifier |
| Canonical Name | Global logical identifier |
| Oracle Table | Physical implementation |

Each identifier has a distinct responsibility.

---

# 8. Lifecycle Contract

Every UMO SHALL implement the standard lifecycle.

```text
Draft

↓

Review

↓

Approved

↓

Compiled

↓

Deployed

↓

Active

↓

Deprecated

↓

Archived
```

Lifecycle transitions SHALL be validated by the Metadata Compiler.

---

# 9. Version Contract

Every UMO SHALL support:

- semantic version;
- compatibility version;
- deployment version;
- runtime version.

Multiple versions MAY coexist.

---

# 10. Ownership Contract

Each UMO SHALL identify:

- business owner;
- technical owner;
- repository owner;
- approval authority.

Ownership SHALL be auditable.

---

# 11. Audit Contract

Every UMO SHALL support audit information.

Minimum logical audit attributes include:

- creation;
- modification;
- approval;
- deployment;
- archival.

Audit history SHALL remain immutable.

---

# 12. Security Contract

Each UMO SHALL define metadata permissions.

Permission categories include:

- view;
- create;
- update;
- delete;
- approve;
- compile;
- deploy;
- administer.

Permissions SHALL be evaluated by the Security Engine.

---

# 13. Deployment Contract

Every UMO SHALL support deployment.

Deployment metadata includes:

- package identifier;
- deployment version;
- deployment timestamp;
- deployment status.

Deployment SHALL be metadata-driven.

---

# 14. Traceability Contract

Every UMO SHALL participate in complete architecture traceability.

```text
Business Requirement

↓

Architecture Driver

↓

Architecture Principle

↓

ADR

↓

Metadata Object

↓

Compiler

↓

Runtime

↓

Application
```

No Aggregate Root SHALL exist outside the traceability model.

---

# 15. Runtime Contract

Compiled Runtime Metadata SHALL retain the logical identity of its originating UMO.

Example

```text
Design Metadata

APP.APPLICATION

↓

Runtime Metadata

RT.APPLICATION
```

Logical identity SHALL remain unchanged throughout compilation.

---

# 16. Metadata Relationships

UMO relationships SHALL use explicit semantics.

Supported relationships include:

| Relationship | Description |
|--------------|-------------|
| owns | Ownership |
| references | Logical reference |
| depends_on | Dependency |
| extends | Inheritance |
| composes | Composition |
| generates | Compiler output |
| deploys | Deployment target |

Relationship semantics SHALL be preserved by the compiler.

---

# 17. Validation Rules

Every UMO SHALL satisfy:

- unique OBJECT_ID;
- unique Canonical Name;
- valid lifecycle state;
- valid owner;
- compatible version;
- complete audit information.

Validation SHALL occur before compilation.

---

# 18. Physical Mapping

The UMO is mapped into Oracle through domain-specific tables.

Example

| UMO | Oracle Table |
|-----|--------------|
| Application | APP_APPLICATION |
| Page | UI_PAGE |
| Dataset | DS_DATASET |
| Workflow | WF_WORKFLOW |
| Role | SEC_ROLE |

The physical model MAY introduce additional implementation attributes.

---

# 19. Quality Attributes

The UMO SHALL provide:

- consistency;
- extensibility;
- interoperability;
- traceability;
- maintainability;
- deterministic compilation;
- deployment stability.

---

# 20. Risks

Potential risks include:

- inconsistent implementations;
- missing metadata attributes;
- duplicate identities;
- invalid lifecycle transitions;
- incompatible versions.

These risks SHALL be mitigated through compiler validation and governance.

---

# 21. Summary

The Universal Metadata Object establishes the common semantic contract shared by every Aggregate Root in ODAF.

Rather than defining a physical database inheritance model, UMO defines a logical contract governing identity, ownership, lifecycle, versioning, auditing, deployment, security, and traceability.

By requiring every metadata domain to conform to this contract, ODAF achieves consistent metadata management, simpler compiler implementation, reusable Studio components, deterministic runtime behavior, and enterprise-scale governance.

---

# UMO Logical Model

```text
Universal Metadata Object
        │
        ├── Identity
        ├── Version
        ├── Lifecycle
        ├── Ownership
        ├── Audit
        ├── Security
        ├── Deployment
        └── Traceability
```

---

# UMO Catalog

| Domain | Aggregate Root | Canonical Name |
|----------|----------------|----------------|
| Application | Application | APP.APPLICATION |
| User Interface | Page | UI.PAGE |
| Dataset | Dataset | DS.DATASET |
| Workflow | Workflow | WF.WORKFLOW |
| Validation | Validation Rule | VAL.VALIDATION_RULE |
| Security | Role | SEC.ROLE |
| Reporting | Report | RPT.REPORT |
| Notification | Notification | NTF.NOTIFICATION |
| Integration | Integration | INT.INTEGRATION |
| Runtime | Runtime Model | RT.RUNTIME_MODEL |
| Deployment | Deployment Package | DEP.DEPLOYMENT_PACKAGE |
| Audit | Audit Log | AUD.AUDIT_LOG |
| Governance | Change Request | GOV.CHANGE_REQUEST |
| Knowledge | Knowledge Item | KB.KNOWLEDGE_ITEM |
| System | System Configuration | SYS.SYSTEM_CONFIGURATION |

---

# Next Document

➡ **08-Key-Strategy.md**

The next chapter defines the global key strategy used by ODAF, including OBJECT_ID generation, surrogate keys, business keys, canonical identifiers, cross-repository references, and globally unique identity management.