---
document_id: DB-V2-005
title: Metadata Meta Model
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-004
  - DB-V2-006
  - SAD-V1-014
---

# Chapter 05

# Metadata Meta Model

---

# 1. Purpose

This chapter defines the Metadata Meta Model used by the Oracle Dynamic Application Framework (ODAF).

The Metadata Meta Model is the formal specification describing how application metadata is structured, composed, related, validated, compiled, deployed, and executed.

The Meta Model is technology-independent.

Oracle tables, PL/SQL packages, Runtime objects, and Studio editors SHALL all be derived from this specification.

---

# 2. Definition

The Metadata Meta Model defines the language used to describe enterprise applications.

Rather than programming applications directly, developers construct metadata objects conforming to this model.

The Metadata Compiler transforms these metadata objects into executable runtime metadata.

---

# 3. Architectural Position

The Metadata Meta Model occupies the central position within ODAF.

```text
Business Requirements

↓

Business Capability

↓

Metadata Meta Model

↓

Metadata Objects

↓

Oracle Repository

↓

Compiler

↓

Runtime Metadata

↓

Kernel

↓

Enterprise Application
```

Every runtime behavior SHALL ultimately originate from the Metadata Meta Model.

---

# 4. Design Goals

The Metadata Meta Model SHALL satisfy the following goals.

| Goal | Description |
|-------|-------------|
| Declarative | Applications are defined rather than programmed |
| Deterministic | Identical metadata produces identical runtime behavior |
| Extensible | New metadata types may be introduced |
| Versioned | Every object supports semantic versioning |
| Auditable | Every change is traceable |
| Reusable | Metadata is reusable across applications |
| Technology Independent | Independent of runtime language |
| Compiler Friendly | Optimized for compilation |

---

# 5. Meta Levels

The Metadata Meta Model defines five abstraction levels.

| Level | Description |
|---------|-------------|
| L0 | Business Requirement |
| L1 | Metadata Type |
| L2 | Metadata Object |
| L3 | Runtime Metadata |
| L4 | Runtime Execution |

Example:

```text
Business Requirement

↓

Page

↓

Customer Form

↓

Compiled Page

↓

Browser Screen
```

---

# 6. Metadata Taxonomy

Metadata is classified into the following major families.

```text
Application

Presentation

Dataset

Workflow

Validation

Security

Reporting

Integration

Deployment

Runtime

Audit

Governance

Knowledge

System
```

Each family owns a well-defined semantic meaning.

---

# 7. Metadata Object Model

Every metadata object consists of:

```text
Metadata Type

↓

Metadata Instance

↓

Metadata Properties

↓

Metadata Relationships

↓

Metadata Constraints

↓

Metadata Lifecycle
```

Metadata SHALL NOT exist without a defined Metadata Type.

---

# 8. Metadata Type

A Metadata Type defines the blueprint of a metadata object.

Examples include:

- Application
- Module
- Page
- Dataset
- Workflow
- Validation Rule
- Report
- Role

Metadata Types are analogous to classes in object-oriented systems.

---

# 9. Metadata Object

A Metadata Object is a concrete instance of a Metadata Type.

Example:

```text
Metadata Type

Page

↓

Metadata Object

Customer Maintenance
```

Objects SHALL inherit the semantics of their Metadata Type.

---

# 10. Metadata Properties

Every Metadata Object contains properties.

Examples include:

- name;
- code;
- description;
- owner;
- version;
- status;
- lifecycle state;
- configuration values.

Properties MAY be primitive or composite.

---

# 11. Metadata Relationships

Metadata objects SHALL communicate through explicit relationships.

Relationship types include:

| Relationship | Description |
|--------------|-------------|
| owns | Parent owns child |
| references | Logical reference |
| depends_on | Dependency |
| extends | Inheritance |
| implements | Interface realization |
| composes | Composition |
| generates | Compiler output |

---

# 12. Metadata Composition

Composition SHALL be preferred over duplication.

Example:

```text
Application

owns

Page

owns

Panel

owns

Field

references

Dataset

references

Validation
```

Reusable metadata SHALL be referenced rather than copied.

---

# 13. Metadata Constraints

Every Metadata Type SHALL define constraints.

Constraint categories include:

- mandatory properties;
- uniqueness;
- referential integrity;
- cardinality;
- lifecycle compatibility;
- version compatibility.

The Metadata Compiler SHALL validate these constraints.

---

# 14. Metadata Cardinality

Relationships SHALL define explicit cardinalities.

Examples include:

| Relationship | Cardinality |
|--------------|-------------|
| Application → Module | 1..N |
| Module → Page | 1..N |
| Page → Tab | 0..N |
| Panel → Field | 1..N |
| Field → Dataset | 1 |
| Field → Validation | 0..N |

Undefined cardinalities SHALL NOT be permitted.

---

# 15. Metadata Lifecycle

Metadata SHALL follow the standard lifecycle.

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

Only approved metadata MAY enter the compilation process.

---

# 16. Metadata Compilation

Compilation transforms Design Metadata into Runtime Metadata.

```text
Design Metadata

↓

Validation

↓

Dependency Analysis

↓

Metadata Intermediate Representation (MIR)

↓

Optimization

↓

Runtime Metadata
```

Compilation SHALL be deterministic.

---

# 17. Metadata Ownership

Each Metadata Object SHALL have:

- business owner;
- technical owner;
- repository owner;
- approval authority.

Ownership SHALL be stored as metadata.

---

# 18. Metadata Dependency Rules

Dependencies SHALL satisfy the following rules.

- No circular dependency.
- Parent SHALL exist before child.
- Referenced object SHALL be compatible.
- Runtime SHALL depend only on compiled metadata.
- Design metadata SHALL NOT depend on runtime metadata.

---

# 19. Universal Metadata Object

Every Aggregate Root SHALL implement the Universal Metadata Object (UMO) contract.

Logical attributes include:

- Object Identifier
- Business Code
- Name
- Version
- Status
- Owner
- Effective Period
- Audit Information

The UMO provides a consistent semantic foundation across all metadata domains.

---

# 20. Meta Model Traceability

Every metadata element SHALL participate in architecture traceability.

```text
Requirement

↓

Architecture Principle

↓

Metadata Type

↓

Metadata Object

↓

Oracle Object

↓

Compiler

↓

Runtime

↓

Application
```

---

# 21. Meta Model Quality Attributes

The Metadata Meta Model SHALL support:

- consistency;
- extensibility;
- maintainability;
- interoperability;
- traceability;
- deterministic execution;
- compiler optimization;
- long-term evolution.

---

# 22. Risks

Potential risks include:

- uncontrolled metadata growth;
- semantic inconsistency;
- circular dependencies;
- incompatible extensions;
- insufficient validation.

These risks SHALL be mitigated through governance, compiler validation, and architecture review.

---

# 23. Summary

The Metadata Meta Model defines the formal language used by ODAF to describe enterprise applications.

It establishes the concepts, abstractions, relationships, lifecycle, constraints, and semantics that govern every metadata object stored in the Oracle Metadata Repository.

All Oracle database objects, runtime artifacts, deployment packages, and Studio editors SHALL conform to this Meta Model.

The Meta Model therefore serves as the semantic foundation of the entire ODAF platform.

---

# Metadata Meta Model Layers

```text
Business Requirement
        │
        ▼
Metadata Type
        │
        ▼
Metadata Object
        │
        ▼
Metadata Repository
        │
        ▼
Metadata Compiler
        │
        ▼
Metadata Intermediate Representation (MIR)
        │
        ▼
Runtime Metadata
        │
        ▼
Kernel
        │
        ▼
Application
```

---

# Next Document

➡ **06-Naming-Standards.md**

The next chapter defines enterprise naming conventions for metadata objects, Oracle tables, views, packages, sequences, constraints, indexes, APIs, and runtime artifacts to ensure consistency across the ODAF platform.