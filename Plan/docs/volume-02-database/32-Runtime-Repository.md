---
document_id: DB-V2-032
title: Runtime Repository
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-021
  - DB-V2-031
  - DB-V2-033
---

# Chapter 32

# Runtime Repository

---

# 1. Purpose

This chapter defines the Runtime Repository of the Oracle Dynamic Application Framework (ODAF).

The Runtime Repository contains the compiled, executable representation of metadata produced by the ODAF Compiler.

Unlike design-time repositories, Runtime Repositories are optimized exclusively for execution and SHALL NOT be edited directly.

---

# 2. Design Objectives

The Runtime Repository SHALL:

- store compiled metadata;
- support deterministic execution;
- minimize runtime database access;
- support in-memory object graphs;
- enable hot activation of new Runtime Packages;
- remain immutable after activation.

---

# 3. Runtime Architecture

```text
Metadata Repository

↓

Compiler

↓

Runtime Repository

↓

Runtime Kernel

↓

Execution
```

The Runtime Repository SHALL be the only metadata source used during request execution.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Runtime Package
```

Each Runtime Package represents one immutable deployment of compiled metadata.

---

# 5. Runtime Meta Model

```text
Runtime Package

│

├── Runtime Application

├── Runtime Module

├── Runtime Feature

├── Runtime View

├── Runtime Dataset

├── Runtime Workflow

├── Runtime Rule

├── Runtime Service Registry

├── Runtime Context

└── Runtime Cache
```

---

# 6. Runtime Package

A Runtime Package encapsulates all executable metadata.

Typical attributes include:

| Attribute | Description |
|------------|-------------|
| PACKAGE_ID | Unique package identifier |
| PACKAGE_VERSION | Package version |
| BUILD_ID | Compiler build identifier |
| ACTIVATION_TIME | Activation timestamp |
| STATUS | Active / Inactive |
| CHECKSUM | Package checksum |

Runtime Packages SHALL be immutable.

---

# 7. Runtime Repository Objects

Typical runtime repositories include:

```text
RT_APPLICATION

RT_MODULE

RT_FEATURE

RT_VIEW

RT_COMPONENT

RT_DATASET

RT_WORKFLOW

RT_RULE

RT_REPORT

RT_NOTIFICATION

RT_INTEGRATION
```

Runtime repositories SHALL remain read-only.

---

# 8. Runtime Object Graph

The Runtime Repository SHALL expose metadata as an in-memory object graph.

```text
Application

↓

Module

↓

Feature

↓

View

↓

Dataset

↓

Workflow

↓

Rule
```

The Runtime Kernel SHALL navigate object relationships without querying design-time repositories.

---

# 9. Runtime Service Registry

The Runtime SHALL register executable services.

Examples include:

- Dataset Service
- Workflow Service
- Validation Service
- Notification Service
- Integration Service
- Rendering Service
- Security Service

The Service Registry SHALL support dependency resolution and lifecycle management.

---

# 10. Runtime Context

Each execution SHALL create a Runtime Context.

Typical context attributes include:

- User;
- Roles;
- Tenant;
- Company;
- Organization;
- Language;
- Time Zone;
- Session;
- Transaction;
- Request Identifier.

Runtime Context SHALL be request-scoped and immutable.

---

# 11. Runtime Cache

The Runtime Repository SHALL support:

- Metadata Cache;
- Object Graph Cache;
- Dataset Cache;
- Security Cache;
- Rule Cache;
- Workflow Cache.

Cache invalidation SHALL occur only through Runtime Package activation or explicit cache policies.

---

# 12. Runtime Execution Model

Runtime execution SHALL follow this sequence:

```text
Request

↓

Resolve Context

↓

Locate Runtime Package

↓

Resolve Service

↓

Execute Object Graph

↓

Generate Response

↓

Audit
```

Execution SHALL remain deterministic.

---

# 13. Runtime Activation

Deployment SHALL activate Runtime Packages explicitly.

Supported activation modes include:

- Immediate;
- Scheduled;
- Blue/Green Switch;
- Canary (future).

Only one Runtime Package SHALL be active for a given application context unless explicitly configured otherwise.

---

# 14. Runtime Hot Swap

The Runtime SHALL support hot replacement of Runtime Packages.

```text
Runtime Package v1

↓

Activate Runtime Package v2

↓

Refresh Cache

↓

Continue Processing
```

In-flight requests SHALL complete using the previously active package.

---

# 15. Runtime Recovery

The Runtime SHALL support recovery from activation failures.

Recovery mechanisms MAY include:

- automatic rollback;
- package validation;
- cache reconstruction;
- dependency verification.

Recovery SHALL preserve runtime consistency.

---

# 16. Runtime Mapping

Compilation transforms:

```text
Metadata

↓

MIR

↓

Runtime Repository

↓

Runtime Package

↓

Runtime Kernel
```

Metadata identity SHALL remain traceable.

---

# 17. Constraints

| ID | Constraint |
|----|------------|
| RTR-001 | Runtime Repositories SHALL be immutable after activation |
| RTR-002 | Runtime SHALL never access design-time metadata during execution |
| RTR-003 | Runtime Context SHALL be request scoped |
| RTR-004 | Runtime Packages SHALL be versioned |
| RTR-005 | Runtime Graph SHALL remain internally consistent |

---

# 18. Relationships

```text
Runtime Package

owns

Runtime Repository

owns

Object Graph

owns

Service Registry

owns

Runtime Context

references

Deployment Package

references

Compiler Build

references

Audit
```

---

# 19. Traceability

```text
Metadata

↓

Compiler

↓

Runtime Repository

↓

Runtime Package

↓

Execution

↓

Audit
```

Every runtime execution SHALL be traceable to its originating compiler build and metadata version.

---

# 20. Risks

Potential risks include:

- stale runtime caches;
- inconsistent object graphs;
- failed runtime activation;
- package incompatibility;
- memory pressure.

These risks SHALL be mitigated through immutable runtime packages, activation validation, cache lifecycle management, dependency verification, and runtime monitoring.

---

# 21. Summary

The Runtime Repository defines the executable metadata layer of ODAF.

By isolating compiled metadata from design-time repositories, organizing execution through immutable Runtime Packages, object graphs, service registries, execution contexts, and runtime caches, ODAF provides a deterministic, scalable, and high-performance execution environment.

This repository forms the operational foundation of the Runtime Kernel introduced in Volume 3.

---

# Runtime Repository Overview

```text
Runtime Package
        │
        ├── Runtime Repository
        ├── Runtime Object Graph
        ├── Runtime Service Registry
        ├── Runtime Context
        ├── Runtime Cache
        └── Runtime Kernel
                │
                ▼
            Request Execution
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Runtime Package | RT_PACKAGE |
| Runtime Application | RT_APPLICATION |
| Runtime Module | RT_MODULE |
| Runtime Feature | RT_FEATURE |
| Runtime View | RT_VIEW |
| Runtime Dataset | RT_DATASET |
| Runtime Workflow | RT_WORKFLOW |
| Runtime Rule | RT_RULE |
| Runtime Service Registry | RT_SERVICE |
| Runtime Context | RT_CONTEXT |
| Runtime Cache | RT_CACHE |

---

# Next Document

➡ **33-Repository-Lifecycle.md**

The next chapter defines the lifecycle of metadata repositories, including creation, modification, validation, compilation, deployment, activation, retirement, archival, and repository governance throughout the complete ODAF lifecycle.