---
document_id: DB-V2-039
title: Appendix
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0
status: Approved
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-001
  - DB-V2-038
---

# Chapter 39

# Appendix

---

# 1. Purpose

This appendix provides a consolidated reference for the Oracle Dynamic Application Framework (ODAF) Metadata Repository.

It summarizes naming conventions, repository prefixes, lifecycle states, compiler artifacts, standard metadata attributes, and architectural terminology used throughout Volume 2.

This appendix is intended as a practical reference for architects, compiler developers, database administrators, and platform implementers.

---

# 2. Repository Prefix Reference

| Prefix | Repository |
|---------|------------|
| APP_ | Application |
| UI_ | User Interface |
| DS_ | Dataset |
| WF_ | Workflow |
| VAL_ | Validation |
| RPT_ | Reporting |
| NTF_ | Notification |
| INT_ | Integration |
| DEP_ | Deployment |
| RT_ | Runtime |
| SEC_ | Security |
| AUD_ | Audit |
| GOV_ | Governance |
| KNW_ | Knowledge |
| PERF_ | Performance |
| CONF_ | Conformance |
| SEED_ | Seed |
| MIG_ | Migration |
| BKP_ | Backup |

Organizations MAY introduce additional prefixes while preserving namespace consistency.

---

# 3. Oracle Object Prefixes

| Prefix | Object |
|---------|--------|
| PK_ | Primary Key Constraint |
| FK_ | Foreign Key Constraint |
| UK_ | Unique Constraint |
| CK_ | Check Constraint |
| IDX_ | Index |
| VW_ | View |
| MV_ | Materialized View |
| SEQ_ | Sequence |
| TRG_ | Trigger |
| PKG_ | Package |
| SYN_ | Synonym |
| TYPE_ | Oracle Object Type |

All compiler-generated Oracle objects SHALL follow these conventions.

---

# 4. Standard Metadata Columns

The following attributes are mandatory for Aggregate Root entities unless explicitly exempted.

| Column | Purpose |
|---------|---------|
| OBJECT_ID | Immutable technical identifier |
| OBJECT_CODE | Business identifier |
| OBJECT_NAME | Display name |
| DESCRIPTION | Human-readable description |
| VERSION_NO | Metadata version |
| STATUS | Lifecycle state |
| CREATED_AT | Creation timestamp |
| UPDATED_AT | Last modification timestamp |
| CREATED_BY | Creator identity |
| UPDATED_BY | Last modifier identity |

---

# 5. Standard Lifecycle States

Typical metadata lifecycle:

```text
Draft
    │
    ▼
Validated
    │
    ▼
Compiled
    │
    ▼
Packaged
    │
    ▼
Deployed
    │
    ▼
Activated
    │
    ▼
Deprecated
    │
    ▼
Archived
```

Organizations MAY extend lifecycle states while preserving transition rules.

---

# 6. Compiler Artifact Reference

The Metadata Compiler Infrastructure (MCI) produces several artifact types.

| Artifact | Description |
|----------|-------------|
| Metadata Repository | Design-time metadata |
| MIR | Metadata Intermediate Representation |
| Runtime Package | Executable metadata |
| Oracle DDL | Generated physical schema |
| PL/SQL Packages | Generated service implementation |
| Deployment Package | Deployable artifact bundle |
| Seed Package | Bootstrap package |
| Backup Package | Platform backup |
| Migration Package | Platform evolution package |
| Conformance Report | Architecture compliance report |

---

# 7. Runtime Repository Reference

Standard runtime repositories include:

```text
RT_APPLICATION
RT_MODULE
RT_FEATURE
RT_VIEW
RT_DATASET
RT_WORKFLOW
RT_RULE
RT_REPORT
RT_NOTIFICATION
RT_SECURITY
RT_PACKAGE
```

Runtime repositories SHALL remain read-only after activation.

---

# 8. Platform Lifecycle Overview

```text
Metadata
        │
        ▼
Compiler
        │
        ▼
Conformance
        │
        ▼
Deployment
        │
        ▼
Runtime
        │
        ▼
Backup
        │
        ▼
Migration
        │
        ▼
Platform Evolution
```

This lifecycle summarizes the operational flow defined across Volume 2.

---

# 9. Glossary

| Term | Definition |
|------|------------|
| Aggregate Root | Primary ownership boundary in the metadata model |
| Compiler | Component that transforms metadata into executable artifacts |
| Conformance | Verification of compliance with architectural and governance rules |
| Deployment Artifact | Immutable package produced by the compiler |
| MIR | Metadata Intermediate Representation |
| Runtime Package | Executable representation of compiled metadata |
| Seed Package | Declarative bootstrap package |
| Backup Package | Immutable representation of platform state |
| Migration Package | Metadata-driven platform evolution package |
| Platform State | Complete operational state of an ODAF installation |

---

# 10. Acronyms

| Acronym | Meaning |
|----------|---------|
| ADR | Architecture Decision Record |
| API | Application Programming Interface |
| CAC | Continuous Architecture Conformance |
| CBM | Canonical Business Model |
| DDD | Domain-Driven Design |
| DDL | Data Definition Language |
| DAG | Directed Acyclic Graph |
| MCI | Metadata Compiler Infrastructure |
| MIR | Metadata Intermediate Representation |
| MSC | Metadata Supply Chain |
| ODAF | Oracle Dynamic Application Framework |
| PBAC | Policy-Based Access Control |
| PEE | Platform Evolution Engine |
| PSR | Platform State Recovery |
| SBOM | Software Bill of Materials |
| SOPA | Service-Oriented PL/SQL Architecture |
| URK | Unified Runtime Kernel |

---

# 11. Reference Architecture Summary

```text
Design-Time
    │
    ▼
Metadata Repository
    │
    ▼
Metadata Compiler Infrastructure (MCI)
    │
    ▼
Conformance Engine
    │
    ▼
Deployment Repository
    │
    ▼
Unified Runtime Kernel (URK)
    │
    ▼
Platform Services
    │
    ▼
Business Applications
```

This diagram summarizes the complete architectural flow defined in Volume 2.

---

# 12. Volume 2 Completion

Volume 2 establishes the Oracle Metadata Repository architecture that underpins the Oracle Dynamic Application Framework.

The volume defines:

- Enterprise metadata repositories;
- Canonical repository structures;
- Compiler-oriented database architecture;
- Runtime repository design;
- Deployment and bootstrap mechanisms;
- Backup, recovery, migration, and conformance processes;
- Governance and lifecycle management.

Together, these chapters define the metadata foundation required for implementing **Volume 3 – ODAF Core Implementation**, where the Metadata Compiler Infrastructure (MCI), Unified Runtime Kernel (URK), Platform Evolution Engine (PEE), Deployment Engine, and supporting platform services are implemented.