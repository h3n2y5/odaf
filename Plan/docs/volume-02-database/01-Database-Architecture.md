---
document_id: DB-V2-001
title: Database Architecture
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-README
  - DB-V2-SUMMARY
  - DB-V2-002
  - SAD-V1-009
  - SAD-V1-010
---

# Chapter 01
# Database Architecture

---

# 1. Purpose

This chapter defines the logical and physical database architecture of the Oracle Metadata Repository.

The repository serves as the authoritative metadata platform supporting every Oracle Dynamic Application Framework (ODAF) implementation.

This chapter establishes:

- repository architecture;
- architectural layers;
- repository boundaries;
- database responsibilities;
- metadata ownership;
- storage strategy.

This chapter is normative.

---

# 2. Architectural Vision

Unlike traditional enterprise applications where the database stores only business transactions, the Oracle Metadata Repository stores the complete definition of the application platform.

Oracle therefore becomes:

> **The Single Source of Truth for the ODAF Platform.**

Every executable business application SHALL originate from metadata stored inside the repository.

---

# 3. Architectural Principles

The repository SHALL satisfy the following principles.

| ID | Principle |
|----|-----------|
| DBP-001 | Metadata First |
| DBP-002 | Single Source of Truth |
| DBP-003 | Everything Versioned |
| DBP-004 | Audit Everything |
| DBP-005 | Repository Driven Runtime |
| DBP-006 | Compiler Before Runtime |
| DBP-007 | Metadata as Code |
| DBP-008 | Domain Isolation |
| DBP-009 | Immutable Runtime Metadata |
| DBP-010 | Complete Traceability |

These principles complement the Architecture Principles defined in Volume 1.

---

# 4. Architectural Responsibilities

The Oracle Metadata Repository SHALL provide the following capabilities.

| Capability | Description |
|------------|-------------|
| Metadata Storage | Store every application definition |
| Runtime Repository | Store compiled runtime metadata |
| Security Repository | Store authorization metadata |
| Workflow Repository | Store workflow definitions |
| Audit Repository | Store immutable history |
| Deployment Repository | Store deployment packages |
| Knowledge Repository | Store architectural knowledge |
| Governance Repository | Store ADR and governance information |

Oracle SHALL NOT contain application-specific business logic that belongs to the runtime engine.

---

# 5. Repository Layers

The repository is organized into logical architectural layers.

```text
Knowledge Layer
        │
Governance Layer
        │
Application Layer
        │
Presentation Layer
        │
Business Layer
        │
Runtime Layer
        │
Deployment Layer
        │
System Layer
```

Each layer has clearly defined responsibilities.

Cross-layer dependencies SHALL be minimized.

---

# 6. Repository Domains

The repository consists of independent metadata domains.

```text
Oracle Metadata Repository

├── Application Domain
├── UI Domain
├── Dataset Domain
├── Workflow Domain
├── Validation Domain
├── Security Domain
├── Reporting Domain
├── Notification Domain
├── Integration Domain
├── Runtime Domain
├── Deployment Domain
├── Audit Domain
├── Governance Domain
├── Knowledge Domain
└── System Domain
```

Each domain SHALL own its metadata.

---

# 7. Repository Boundaries

The repository distinguishes between editable metadata and executable metadata.

```text
Editable Metadata

↓

Compiler

↓

Compiled Runtime Metadata

↓

ODAF Kernel

↓

Execution
```

The Runtime SHALL execute only compiled metadata.

Editable metadata SHALL NEVER be executed directly.

---

# 8. Metadata Classification

Metadata is classified into five categories.

| Category | Purpose |
|-----------|---------|
| Design Metadata | Editable definitions |
| Runtime Metadata | Compiled runtime model |
| Operational Metadata | Runtime operation |
| Governance Metadata | Architecture governance |
| Historical Metadata | Audit and history |

Each category SHALL have independent lifecycle management.

---

# 9. Physical Architecture

A reference deployment consists of:

```text
Browser

↓

ODAF Runtime

↓

Oracle Metadata Repository

↓

Business Data Repository
```

The Metadata Repository MAY reside within the same Oracle database as business data or within a dedicated metadata database.

The architectural model SHALL support both deployment options.

---

# 10. Storage Model

Metadata SHALL be stored as normalized relational structures.

The following object types are expected.

- tables;
- views;
- sequences;
- constraints;
- indexes;
- packages;
- procedures;
- functions;
- triggers;
- materialized views (optional).

Object organization SHALL follow repository domains.

---

# 11. Repository Services

The repository provides services to the runtime through metadata.

```text
Metadata Repository

↓

Metadata Compiler

↓

Compiled Repository

↓

Kernel

↓

Renderer

↓

Browser
```

Repository services SHALL remain independent of presentation technology.

---

# 12. Metadata Lifecycle

Metadata SHALL follow a controlled lifecycle.

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

Historical versions SHALL remain recoverable.

---

# 13. Data Ownership

Each metadata object SHALL have a single owner.

Ownership SHALL include:

- responsible domain;
- version;
- author;
- approver;
- deployment status.

Ownership information SHALL be stored as metadata.

---

# 14. Repository Integrity

Repository integrity SHALL be enforced through:

- primary keys;
- foreign keys;
- unique constraints;
- check constraints;
- metadata validation;
- compiler validation.

Invalid metadata SHALL NOT be compiled.

---

# 15. Version Management

Every metadata object SHALL support versioning.

Minimum version attributes include:

- object version;
- effective date;
- expiration date;
- deployment version;
- compatibility version.

Runtime SHALL execute a consistent metadata version.

---

# 16. Security Model

Repository access SHALL be secured using metadata-driven authorization.

Security SHALL protect:

- metadata editing;
- deployment;
- runtime access;
- governance;
- administration.

Security SHALL follow the RBAC model defined in Volume 1.

---

# 17. Performance Strategy

The repository SHALL optimize:

- metadata lookup;
- dependency resolution;
- compiler performance;
- runtime loading;
- deployment operations.

Compiled metadata SHALL minimize runtime database access.

---

# 18. High Availability

The architecture SHALL support:

- Oracle Data Guard;
- Oracle RAC (optional);
- online backup;
- point-in-time recovery;
- metadata replication.

High availability SHALL not require changes to metadata structures.

---

# 19. Architectural Constraints

The following constraints apply.

| ID | Constraint |
|----|------------|
| DBC-001 | Oracle is the authoritative metadata repository. |
| DBC-002 | Runtime executes compiled metadata only. |
| DBC-003 | Every metadata object is versioned. |
| DBC-004 | Every structural change is auditable. |
| DBC-005 | Metadata domains remain isolated. |
| DBC-006 | Repository integrity is mandatory. |

---

# 20. Traceability

Every repository object SHALL be traceable.

```text
Architecture Driver

↓

Architecture Principle

↓

Metadata Domain

↓

Oracle Object

↓

PL/SQL Package

↓

Runtime Service

↓

Application

↓

Audit Record
```

Repository traceability SHALL be maintained throughout the platform lifecycle.

---

# 21. Risks

Potential architectural risks include:

- metadata inconsistency;
- repository fragmentation;
- excessive coupling;
- invalid version combinations;
- unauthorized modifications;
- compiler/runtime mismatch.

These risks SHALL be mitigated through governance, validation, and automated verification.

---

# 22. Summary

The Oracle Metadata Repository is the architectural foundation of ODAF.

Rather than functioning solely as a relational database, Oracle acts as the platform's authoritative metadata repository, supporting application definition, runtime execution, deployment, governance, auditing, and long-term platform evolution.

Every subsequent chapter in this volume progressively refines this architecture into concrete metadata domains, Oracle objects, DDL, PL/SQL packages, and deployment structures.

---

# Metadata Catalog

This chapter introduces the following conceptual repository domains.

| Repository Domain | Oracle Objects (Planned) |
|-------------------|--------------------------|
| Application Repository | APP_* |
| UI Repository | UI_* |
| Dataset Repository | DS_* |
| Workflow Repository | WF_* |
| Validation Repository | VAL_* |
| Security Repository | SEC_* |
| Reporting Repository | RPT_* |
| Notification Repository | NTF_* |
| Integration Repository | INT_* |
| Runtime Repository | RT_* |
| Deployment Repository | DEP_* |
| Audit Repository | AUD_* |
| Governance Repository | GOV_* |
| Knowledge Repository | KB_* |
| System Repository | SYS_* |

---

# Next Document

➡ **02-Repository-Overview.md**

The next chapter defines each repository domain in detail, including responsibilities, ownership, relationships, and the metadata boundaries between domains.