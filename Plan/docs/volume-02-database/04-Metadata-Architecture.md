---
document_id: DB-V2-004
title: Metadata Architecture
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-003
  - DB-V2-005
  - SAD-V1-010
  - SAD-V1-014
---

# Chapter 04
# Metadata Architecture

---

# 1. Purpose

This chapter defines the metadata architecture of the Oracle Dynamic Application Framework (ODAF).

Metadata is the fundamental architectural asset of ODAF.

Every application capability, runtime behavior, deployment package, and user interface SHALL be derived from metadata stored in the Oracle Metadata Repository.

This chapter specifies:

- metadata philosophy;
- metadata taxonomy;
- metadata hierarchy;
- metadata composition;
- metadata lifecycle;
- metadata compilation model;
- runtime metadata architecture.

---

# 2. Metadata Philosophy

ODAF adopts the following principle.

> **Everything is Metadata.**

Applications SHALL NOT be implemented through handwritten business code whenever metadata can express the same behavior.

Metadata becomes the executable specification of the application.

---

# 3. Metadata Oriented Architecture (MOA)

ODAF introduces the concept of **Metadata Oriented Architecture (MOA)**.

In MOA:

- metadata defines behavior;
- compiler validates metadata;
- runtime executes compiled metadata;
- Studio edits metadata;
- governance controls metadata.

Business logic is expressed declaratively.

---

# 4. Metadata Classification

Metadata is classified into the following categories.

| Category | Description |
|-----------|-------------|
| Design Metadata | Editable application definition |
| Runtime Metadata | Compiled executable metadata |
| Operational Metadata | Runtime operational information |
| Deployment Metadata | Deployment packages |
| Governance Metadata | ADR, reviews, approvals |
| Knowledge Metadata | Standards and references |
| Historical Metadata | Audit history |

Each category has an independent lifecycle.

---

# 5. Metadata Layers

The metadata architecture consists of layered abstractions.

```text
Business Requirement

↓

Business Metadata

↓

Application Metadata

↓

Runtime Metadata

↓

Deployment Metadata

↓

Execution
```

Higher layers SHALL NOT depend on lower implementation details.

---

# 6. Metadata Hierarchy

Metadata objects are organized hierarchically.

```text
Application

└── Module

    └── Menu

        └── Page

            └── Tab

                └── Panel

                    └── Section

                        └── Field

                            └── Validation
```

Every child object SHALL have exactly one parent.

---

# 7. Universal Metadata Object (UMO)

Every Aggregate Root SHALL implement the Universal Metadata Object contract.

Logical attributes include:

| Attribute | Description |
|------------|-------------|
| OBJECT_ID | Unique identifier |
| OBJECT_CODE | Stable business code |
| OBJECT_NAME | Display name |
| OBJECT_TYPE | Metadata type |
| OBJECT_VERSION | Semantic version |
| STATUS | Lifecycle status |
| OWNER | Responsible domain |
| EFFECTIVE_FROM | Effective date |
| EFFECTIVE_UNTIL | Expiration date |
| CREATED_AT | Creation timestamp |
| CREATED_BY | Creator |
| UPDATED_AT | Last modification |
| UPDATED_BY | Modifier |

The UMO is a logical model and does not mandate identical physical table structures.

---

# 8. Metadata Composition

Metadata objects are composed rather than duplicated.

Example:

```text
Application

↓

Page

↓

Panel

↓

Field

↓

Dataset Reference

↓

Validation Reference

↓

Permission Reference
```

Composition SHALL be preferred over duplication.

---

# 9. Metadata Dependency Model

Metadata dependencies SHALL be explicit.

```text
Application

↓

Page

↓

Dataset

↓

Workflow

↓

Runtime Object
```

Circular dependencies SHALL be avoided.

---

# 10. Metadata Lifecycle

Every metadata object SHALL follow a controlled lifecycle.

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

Only approved metadata MAY be compiled.

---

# 11. Metadata Versioning

Every metadata object SHALL be versioned.

Version information includes:

- semantic version;
- compatibility version;
- effective date;
- deployment version.

Multiple metadata versions MAY coexist.

---

# 12. Metadata Validation

Before compilation, metadata SHALL be validated.

Validation includes:

- structural validation;
- referential validation;
- business validation;
- dependency validation;
- security validation;
- naming validation.

Invalid metadata SHALL NOT be compiled.

---

# 13. Metadata Compiler Model

The Metadata Compiler transforms editable metadata into executable runtime artifacts.

```text
Editable Metadata

↓

Metadata Validator

↓

Dependency Analyzer

↓

Compiler

↓

Optimization

↓

Runtime Metadata
```

Compilation SHALL be deterministic.

---

# 14. Runtime Metadata

Runtime metadata is immutable.

Characteristics:

- optimized;
- normalized;
- deployment-ready;
- read-only;
- deterministic.

Runtime SHALL execute runtime metadata only.

---

# 15. Metadata Repository Flow

```text
Studio

↓

Oracle Metadata Repository

↓

Compiler

↓

Runtime Repository

↓

Kernel

↓

Renderer

↓

Browser
```

The Runtime SHALL NOT access editable metadata during execution.

---

# 16. Metadata Traceability

Every metadata object SHALL be traceable.

```text
Requirement

↓

Architecture Principle

↓

ADR

↓

Metadata Object

↓

Oracle Table

↓

Compiler

↓

Runtime

↓

Application
```

Traceability SHALL be preserved across the platform lifecycle.

---

# 17. Metadata Ownership

Every metadata object SHALL have exactly one owner.

Ownership SHALL include:

- domain owner;
- technical owner;
- approval authority.

Ownership SHALL be auditable.

---

# 18. Metadata Security

Metadata SHALL be protected through metadata-driven authorization.

Security SHALL govern:

- editing;
- approval;
- compilation;
- deployment;
- rollback;
- archival.

Unauthorized modification SHALL be prevented.

---

# 19. Metadata Packaging

Compiled metadata SHALL be packaged into immutable deployment artifacts.

Each package SHALL include:

- metadata manifest;
- version;
- dependency list;
- checksum;
- digital signature (future).

Deployment SHALL use packages rather than individual metadata objects.

---

# 20. Metadata Optimization

The compiler MAY optimize metadata by:

- dependency resolution;
- object flattening;
- lookup caching;
- SQL normalization;
- workflow optimization;
- runtime indexing.

Optimization SHALL preserve behavior.

---

# 21. Metadata Constraints

The following architectural constraints apply.

| ID | Constraint |
|----|------------|
| MDA-001 | Metadata is the primary application definition. |
| MDA-002 | Runtime executes compiled metadata only. |
| MDA-003 | Metadata SHALL be versioned. |
| MDA-004 | Metadata SHALL be auditable. |
| MDA-005 | Metadata SHALL be validated before compilation. |
| MDA-006 | Runtime metadata SHALL be immutable. |

---

# 22. Metadata Quality Attributes

The metadata architecture SHALL support:

- consistency;
- extensibility;
- maintainability;
- traceability;
- performance;
- security;
- interoperability;
- deterministic execution.

These quality attributes guide repository evolution.

---

# 23. Risks

Potential metadata risks include:

- inconsistent definitions;
- duplicate metadata;
- cyclic dependencies;
- uncontrolled growth;
- version incompatibility;
- runtime divergence.

These risks SHALL be mitigated through validation, governance, and compiler verification.

---

# 24. Summary

The Metadata Architecture defines the conceptual foundation of ODAF.

By treating metadata as the primary representation of application behavior, ODAF separates business definition from runtime implementation.

The Metadata Compiler transforms validated metadata into optimized runtime artifacts, enabling deterministic execution, architectural consistency, and enterprise-scale maintainability.

This architecture forms the basis for the Enterprise ERD, Oracle DDL, PL/SQL Compiler, Runtime Kernel, ODAF Studio, and all subsequent platform capabilities.

---

# Metadata Catalog

| Metadata Category | Planned Oracle Prefix |
|-------------------|----------------------|
| Application | APP_ |
| User Interface | UI_ |
| Dataset | DS_ |
| Workflow | WF_ |
| Validation | VAL_ |
| Security | SEC_ |
| Reporting | RPT_ |
| Notification | NTF_ |
| Integration | INT_ |
| Runtime | RT_ |
| Deployment | DEP_ |
| Audit | AUD_ |
| Governance | GOV_ |
| Knowledge | KB_ |
| System | SYS_ |

---

# Next Document

➡ **05-ERD.md**

The next chapter defines the Enterprise Entity Relationship Diagram (ERD), including all logical entities, relationships, aggregate mappings, cardinalities, and the transformation of the domain model into the Oracle relational model.