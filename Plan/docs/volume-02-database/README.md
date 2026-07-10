---
document_id: DB-V2-README
title: Volume 2 – Oracle Metadata & Database Design
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - Volume 1 – Software Architecture Document
  - Volume 3 – ODAF Core Implementation
---

# Oracle Dynamic Application Framework (ODAF)

# Volume 2

# Oracle Metadata & Database Design

---

# Document Status

| Item | Value |
|------|-------|
| Document | Volume 2 |
| Status | Draft |
| Version | 1.0.0 |
| Classification | Public |
| Owner | ODAF Architecture Board |

---

# Purpose

Volume 2 defines the complete Oracle Metadata Repository specification for the Oracle Dynamic Application Framework (ODAF).

This volume transforms the architectural concepts defined in Volume 1 into a concrete Oracle implementation.

Unlike conventional application databases, the Oracle database in ODAF is not merely a storage engine.

Oracle acts as the authoritative **Platform Metadata Repository**, responsible for describing the entire application platform.

Everything that the runtime executes SHALL originate from metadata stored within this repository.

---

# Position Within ODAF

The ODAF documentation is organized into five major volumes.

| Volume | Description |
|---------|-------------|
| Volume 1 | Software Architecture Document |
| **Volume 2** | Oracle Metadata & Database Design |
| Volume 3 | ODAF Core Implementation |
| Volume 4 | ODAF Studio |
| Volume 5 | Sample ERP |

Volume 2 serves as the contractual bridge between architecture and implementation.

Every runtime component defined in Volume 3 SHALL rely upon the metadata structures defined in this volume.

---

# Vision

The Oracle repository SHALL become the single authoritative source of truth for every business application built upon ODAF.

Rather than storing only business transactions, the repository SHALL also store:

- application definitions;
- page definitions;
- UI layouts;
- datasets;
- SQL definitions;
- workflows;
- business rules;
- validation rules;
- security policies;
- reporting definitions;
- deployment metadata;
- plugin metadata;
- runtime metadata;
- governance metadata.

As a result, ODAF applications are generated dynamically from metadata rather than handwritten source code.

---

# Architectural Principle

The repository follows one fundamental principle.

> **Everything is Metadata.**

Business applications SHALL be described by metadata.

Runtime components SHALL interpret compiled metadata rather than application-specific source code.

Developers SHALL configure the repository rather than modifying runtime behavior.

---

# Repository Philosophy

The Oracle Metadata Repository is divided into several logical repositories.

```text
Oracle Metadata Repository

├── Application Repository
├── UI Repository
├── Dataset Repository
├── Workflow Repository
├── Validation Repository
├── Security Repository
├── Reporting Repository
├── Integration Repository
├── Deployment Repository
├── Runtime Repository
├── Governance Repository
├── Audit Repository
├── Knowledge Repository
└── System Repository
```

Each repository represents a bounded architectural domain.

Repositories SHALL remain loosely coupled through published metadata relationships.

---

# Metadata-Driven Runtime

Runtime execution follows the lifecycle below.

```text
Business Metadata

↓

Metadata Validation

↓

Metadata Compiler

↓

Compiled Runtime Metadata

↓

Kernel

↓

Renderer

↓

Business Application
```

Editable metadata SHALL never be executed directly.

The Runtime SHALL execute only compiled metadata.

---

# Repository Characteristics

The Oracle Metadata Repository SHALL satisfy the following characteristics.

| Characteristic | Description |
|----------------|-------------|
| Metadata Driven | All applications are metadata-defined |
| Versioned | Every object is version controlled |
| Auditable | Every change is recorded |
| Extensible | New metadata types can be added |
| Secure | Metadata is protected by RBAC |
| Deterministic | Same metadata produces same runtime |
| Traceable | Complete architecture traceability |
| Deployable | Metadata is deployable as packages |

---

# Repository Domains

The metadata repository is organized into multiple business domains.

| Domain | Purpose |
|---------|---------|
| Application | Applications, modules, menus |
| UI | Pages, tabs, panels, fields |
| Dataset | SQL, parameters, caching |
| Workflow | States, transitions, approvals |
| Validation | Business rules |
| Security | Users, roles, permissions |
| Reporting | Reports and templates |
| Notification | Email, SMS, push |
| Integration | REST, SOAP, Oracle APIs |
| Deployment | Packages and releases |
| Runtime | Compiled metadata |
| Audit | History and logging |
| Governance | ADR, reviews, approvals |
| Knowledge | Architecture references |

Each domain SHALL evolve independently while remaining architecturally consistent.

---

# Oracle Features Utilized

The repository is designed to leverage Oracle Database enterprise capabilities.

These include:

- Oracle SQL
- PL/SQL
- Packages
- Views
- Materialized Views
- Sequences
- Identity Columns (optional)
- Virtual Columns
- Constraints
- Indexes
- Partitioning (future)
- Flashback (optional)
- Scheduler
- Advanced Queuing (future)

The specification remains independent of Oracle edition whenever practical.

---

# Expected Deliverables

Completion of Volume 2 SHALL produce:

- Enterprise ERD
- Complete Oracle DDL
- Metadata Dictionary
- Naming Standards
- Security Model
- Audit Model
- Versioning Model
- PL/SQL Packages
- Compiler Repository
- Runtime Repository
- Seed Data
- Deployment Repository
- Migration Strategy

These artifacts collectively define the complete Oracle implementation of ODAF.

---

# Intended Audience

This volume is intended for:

- Oracle Database Architects
- Enterprise Architects
- Solution Architects
- Technical Leads
- Backend Developers
- PL/SQL Developers
- Runtime Developers
- DevOps Engineers
- Security Engineers
- ODAF Contributors

Readers are expected to be familiar with the concepts defined in Volume 1.

---

# Conformance

Every Oracle implementation claiming ODAF compatibility SHALL conform to this specification.

Conformance SHALL be verified according to the ODAF Conformance Model defined in Volume 1.

No implementation SHALL introduce incompatible metadata structures without an approved Architecture Decision Record (ADR).

---

# Relationship to Other Volumes

Volume 2 depends upon Volume 1 and serves as the primary dependency for all remaining volumes.

```text
Volume 1
Software Architecture

↓

Volume 2
Oracle Metadata Repository

↓

Volume 3
ODAF Core Runtime

↓

Volume 4
ODAF Studio

↓

Volume 5
Sample ERP
```

---

# Repository Governance

The Oracle Metadata Repository SHALL be governed through:

- Architecture Principles;
- Architecture Decision Records (ADR);
- Metadata Versioning;
- Change Management;
- Conformance Reviews;
- Architecture Governance.

All structural changes SHALL be approved through the governance process defined in Volume 1.

---

# Document Structure

Volume 2 is organized into the following chapters.

| Chapter | Description |
|----------|-------------|
| README | Overview of Volume 2 |
| SUMMARY | Executive summary |
| 01 | Database Architecture |
| 02 | Repository Overview |
| 03 | Domain Model |
| 04 | Metadata Architecture |
| 05 | Enterprise ERD |
| ... | Oracle Metadata Specification |
| 39 | Appendix |

Each chapter progressively refines the Oracle Metadata Repository from conceptual architecture to executable implementation.

---

# Summary

Volume 2 defines the Oracle Metadata Repository that serves as the executable foundation of the Oracle Dynamic Application Framework.

By transforming architectural concepts into metadata structures, Oracle database objects, and PL/SQL components, this volume enables ODAF to generate enterprise applications dynamically while preserving architectural consistency, traceability, governance, and long-term maintainability.

This document is normative.

All Oracle implementations SHALL conform to the specification contained within this volume.

---

# Next Document

➡ **SUMMARY.md**

The next document provides an executive summary of the Oracle Metadata & Database Design specification and introduces the major repository domains that compose the ODAF Metadata Repository.