---
document_id: DB-V2-034
title: Seed Data
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-033
  - DB-V2-035
---

# Chapter 34

# Seed Data

---

# 1. Purpose

This chapter defines the Seed Repository of the Oracle Dynamic Application Framework (ODAF).

Seed Data initializes a newly created ODAF environment with the minimum set of metadata, configuration, security objects, and reference information required for platform operation.

Seed Data is not merely a collection of SQL INSERT statements. It represents the declarative bootstrap state of the platform.

---

# 2. Design Objectives

The Seed Repository SHALL:

- bootstrap a new platform installation;
- initialize mandatory metadata;
- support repeatable installation;
- support idempotent execution;
- support versioned seed packages;
- remain compiler-generated.

---

# 3. Bootstrap Architecture

```text
Bootstrap Package

↓

Platform Seed

↓

Security Seed

↓

Metadata Seed

↓

Knowledge Seed

↓

Business Seed

↓

Runtime Activation
```

Bootstrap SHALL be deterministic and repeatable.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Seed Package
```

A Seed Package contains all artifacts required for a bootstrap operation.

---

# 5. Seed Meta Model

```text
Seed Package

│

├── Manifest

├── Platform Seed

├── Security Seed

├── Metadata Seed

├── Knowledge Seed

├── Business Seed

├── Sample Data

├── Dependency Graph

└── Runtime Mapping
```

---

# 6. Platform Seed

Platform Seed initializes the core platform repositories.

Examples include:

- Platform Configuration;
- Compiler Configuration;
- Runtime Configuration;
- Default Locales;
- System Parameters.

Platform Seed SHALL execute before all other seed categories.

---

# 7. Security Seed

Security Seed initializes platform security.

Examples include:

- default identities;
- administrator account;
- roles;
- permissions;
- security policies.

Security Seed SHALL establish a minimally secure environment.

---

# 8. Metadata Seed

Metadata Seed initializes the design-time repository.

Examples include:

- applications;
- modules;
- features;
- views;
- datasets;
- workflows;
- validation rules.

Metadata Seed SHALL establish the minimum executable metadata model.

---

# 9. Knowledge Seed

Knowledge Seed initializes reusable platform knowledge.

Examples include:

- business patterns;
- workflow templates;
- UI templates;
- AI prompts;
- best practices.

Knowledge Seed SHALL support future application generation.

---

# 10. Business Seed

Business Seed initializes mandatory business reference data.

Examples include:

- currencies;
- countries;
- organizations;
- units of measure;
- fiscal calendars;
- tax codes.

Business Seed SHALL remain configurable by deployment.

---

# 11. Sample Data

Sample Data MAY be provided for:

- demonstrations;
- tutorials;
- training;
- testing;
- reference implementations.

Sample Data SHALL remain optional.

---

# 12. Seed Dependencies

Seed execution SHALL respect dependency ordering.

Example:

```text
Platform

↓

Security

↓

Metadata

↓

Knowledge

↓

Business

↓

Sample Data
```

The compiler SHALL resolve dependency order automatically.

---

# 13. Idempotent Execution

Seed execution SHALL be idempotent.

Supported behaviors include:

- insert if missing;
- merge if existing;
- update compatible versions;
- skip identical objects.

Repeated execution SHALL NOT create duplicates.

---

# 14. Seed Manifest

Every Seed Package SHALL contain a Manifest.

Typical contents include:

- package identifier;
- package version;
- metadata version;
- dependency list;
- required platform version;
- checksum;
- generation timestamp.

The Manifest SHALL be validated before execution.

---

# 15. Seed Installation

Seed installation SHALL support:

- fresh installation;
- environment initialization;
- platform upgrade;
- module installation;
- optional sample installation.

Installation SHALL be fully traceable.

---

# 16. Runtime Mapping

Compilation transforms:

```text
Metadata

↓

Seed Package

↓

Bootstrap

↓

Runtime Repository
```

Seed identity SHALL remain preserved.

---

# 17. Constraints

| ID | Constraint |
|----|------------|
| SEED-001 | Seed Packages SHALL be idempotent |
| SEED-002 | Platform Seed SHALL execute first |
| SEED-003 | Seed execution SHALL respect dependency order |
| SEED-004 | Every Seed Package SHALL contain a Manifest |
| SEED-005 | Sample Data SHALL remain optional |

---

# 18. Relationships

```text
Seed Package

owns

Manifest

owns

Platform Seed

owns

Security Seed

owns

Metadata Seed

owns

Knowledge Seed

owns

Business Seed

references

Deployment Artifact

references

Runtime Package
```

---

# 19. Traceability

```text
Metadata

↓

Seed Package

↓

Bootstrap

↓

Runtime

↓

Audit
```

Every initialized object SHALL be traceable to its originating Seed Package.

---

# 20. Risks

Potential risks include:

- duplicate initialization;
- invalid dependency ordering;
- incompatible platform versions;
- incomplete bootstrap;
- obsolete sample data.

These risks SHALL be mitigated through idempotent execution, dependency analysis, manifest validation, and compiler-generated seed packages.

---

# 21. Summary

The Seed Repository defines the bootstrap architecture of ODAF.

By treating initialization as a metadata-driven, versioned, and compiler-generated process, ODAF enables repeatable platform installation, deterministic environment provisioning, controlled upgrades, and optional sample application deployment.

This approach transforms Seed Data from simple initialization scripts into a reusable and governed platform bootstrap capability.

---

# Seed Repository Overview

```text
Seed Package
        │
        ├── Platform Seed
        ├── Security Seed
        ├── Metadata Seed
        ├── Knowledge Seed
        ├── Business Seed
        ├── Sample Data
        ├── Manifest
        └── Dependency Graph
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Seed Package | SEED_PACKAGE |
| Seed Manifest | SEED_MANIFEST |
| Platform Seed | SEED_PLATFORM |
| Security Seed | SEED_SECURITY |
| Metadata Seed | SEED_METADATA |
| Knowledge Seed | SEED_KNOWLEDGE |
| Business Seed | SEED_BUSINESS |
| Sample Data | SEED_SAMPLE |
| Seed Dependency | SEED_DEPENDENCY |
| Seed Execution History | SEED_HISTORY |

---

# Next Document

➡ **35-Database-Lifecycle.md**

The next chapter defines the complete lifecycle of the ODAF database, including repository creation, initialization, upgrades, migrations, activation, maintenance, archival, recovery, and retirement.