---
document_id: DB-V2-030
title: Packages
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-029
  - DB-V2-031
  - DB-V2-021
---

# Chapter 30

# Packages

---

# 1. Purpose

This chapter defines the Oracle Package architecture used by the Oracle Dynamic Application Framework (ODAF).

Within ODAF, Oracle Packages are compiler-generated runtime service modules rather than manually developed PL/SQL artifacts.

Packages implement service contracts and encapsulate domain behavior while remaining independent from metadata definitions.

---

# 2. Design Objectives

Packages SHALL:

- implement service contracts;
- encapsulate business behavior;
- isolate infrastructure concerns;
- support compiler generation;
- remain deterministic;
- provide stable runtime APIs.

---

# 3. Package Architecture

```text
Metadata

↓

Compiler

↓

Service Contract

↓

Oracle Package

↓

Runtime
```

Oracle Packages SHALL NOT be treated as the design source.

Metadata remains the single source of truth.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Service Package
```

Each Service Package SHALL implement one logical service.

---

# 5. Package Meta Model

```text
Service Package

│

├── Service Contract

├── Operation

├── Procedure

├── Function

├── Repository Dependency

├── Runtime Mapping

└── Version
```

---

# 6. Package Layers

Compiler-generated packages SHALL be organized into layers.

```text
API Package

↓

Domain Package

↓

Repository Package

↓

Infrastructure Package
```

Each layer SHALL expose only the interfaces required by the next layer.

---

# 7. Service Contracts

A Service Contract defines the public capabilities exposed by a package.

Examples:

- Purchase Service
- Inventory Service
- Customer Service
- Financial Service
- Workflow Service

Service Contracts SHALL remain independent from Oracle implementation.

---

# 8. Operations

Operations represent executable business capabilities.

Examples include:

- Create Purchase Order
- Approve Purchase Order
- Reserve Inventory
- Close Accounting Period
- Generate Invoice

Operations SHALL be compiler-generated into PL/SQL procedures or functions.

---

# 9. API Packages

API Packages expose stable entry points to the Runtime.

Examples:

```text
PKG_API_PURCHASE

PKG_API_INVENTORY

PKG_API_FINANCE
```

API Packages SHALL contain minimal orchestration logic.

---

# 10. Domain Packages

Domain Packages implement business behavior.

Examples:

```text
PKG_PURCHASE

PKG_INVENTORY

PKG_FINANCE
```

Domain Packages SHALL orchestrate repository access, validation, workflow, and notifications.

---

# 11. Repository Packages

Repository Packages encapsulate data persistence.

Examples:

```text
PKG_PURCHASE_REPO

PKG_ITEM_REPO

PKG_CUSTOMER_REPO
```

Repository Packages SHALL isolate SQL from higher layers.

---

# 12. Infrastructure Packages

Infrastructure Packages provide shared technical services.

Examples:

- Logging
- Security
- JSON Processing
- HTTP Client
- XML Processing
- Scheduler Utilities

Infrastructure Packages SHALL remain reusable.

---

# 13. Dependency Rules

Package dependencies SHALL follow this direction:

```text
API

↓

Domain

↓

Repository

↓

Infrastructure
```

Reverse dependencies SHALL NOT be permitted.

---

# 14. Runtime Mapping

Compilation transforms:

```text
Metadata

↓

Service Contract

↓

Generated Package

↓

Runtime Execution
```

Package identity SHALL remain traceable.

---

# 15. Versioning

Generated Packages SHALL record:

- compiler version;
- metadata version;
- package version;
- generation timestamp.

Version information SHALL be available at runtime.

---

# 16. Error Handling

Compiler-generated Packages SHALL implement standardized error handling.

Recommended practices include:

- structured exceptions;
- error codes;
- diagnostic context;
- transaction-safe rollback.

Error handling SHALL remain consistent across all packages.

---

# 17. Performance

Compiler-generated Packages SHOULD optimize:

- context switching;
- SQL execution;
- bulk processing;
- caching;
- deterministic function usage.

Performance optimizations SHALL preserve functional behavior.

---

# 18. Constraints

| ID | Constraint |
|----|------------|
| PKG-001 | Packages SHALL be compiler-generated |
| PKG-002 | Metadata SHALL remain the single source of truth |
| PKG-003 | Repository Packages SHALL isolate SQL |
| PKG-004 | Package dependencies SHALL remain acyclic |
| PKG-005 | Public APIs SHALL implement Service Contracts |

---

# 19. Relationships

```text
Service Contract

owns

Operation

implemented by

Package

references

Dataset

references

Workflow

references

Validation

references

Notification

references

Integration
```

---

# 20. Traceability

```text
Metadata

↓

Compiler

↓

Generated Package

↓

Runtime

↓

Audit
```

Every generated package SHALL be traceable to the originating metadata.

---

# 21. Risks

Potential risks include:

- manual modification of generated packages;
- dependency cycles;
- excessive package size;
- runtime incompatibilities;
- inconsistent public APIs.

These risks SHALL be mitigated through compiler ownership, dependency validation, package modularization, and governance.

---

# 22. Summary

The Packages architecture defines compiler-generated Oracle service modules that implement the executable behavior of ODAF.

By separating service contracts, domain logic, repository access, and infrastructure concerns into layered packages, ODAF provides a modular, maintainable, and scalable Oracle execution model while preserving metadata as the single source of truth.

---

# Package Layer Overview

```text
Metadata
        │
        ▼
Compiler
        │
        ▼
Service Contract
        │
        ├── API Package
        ├── Domain Package
        ├── Repository Package
        └── Infrastructure Package
                │
                ▼
Runtime
```

---

# Planned Oracle Packages

| Package | Responsibility |
|----------|----------------|
| PKG_API_* | Public runtime APIs |
| PKG_* | Domain services |
| PKG_*_REPO | Repository access |
| PKG_SEC_* | Security services |
| PKG_WF_* | Workflow services |
| PKG_INT_* | Integration services |
| PKG_UTIL_* | Shared utilities |
| PKG_RT_* | Runtime services |

---

# Next Document

➡ **31-Performance-Optimization.md**

The next chapter defines performance optimization strategies, including compiler optimizations, runtime caching, SQL optimization, package optimization, metadata indexing, execution profiling, and scalability recommendations for enterprise deployments.