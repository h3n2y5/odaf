---
document_id: DB-V2-021
title: Runtime Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-020
  - DB-V2-022
  - DB-V2-015
  - DB-V2-016
  - DB-V2-013
---

# Chapter 21

# Runtime Metadata

---

# 1. Purpose

This chapter defines the Runtime Repository of the Oracle Dynamic Application Framework (ODAF).

The Runtime Repository contains compiled metadata optimized for execution.

Runtime SHALL execute compiled metadata rather than design-time metadata.

The Runtime Repository forms the execution foundation of the ODAF Kernel.

---

# 2. Design Objectives

Runtime Metadata SHALL:

- eliminate design-time dependencies;
- provide deterministic execution;
- optimize metadata loading;
- minimize database access;
- support hot reload;
- support runtime caching;
- preserve metadata identity.

---

# 3. Runtime Architecture

```text
Design Metadata

↓

Compiler

↓

Metadata IR (MIR)

↓

Optimizer

↓

Runtime IR (RIR)

↓

Runtime Repository

↓

Runtime Engine
```

Runtime SHALL consume only compiled runtime metadata.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Runtime Package
```

Every Runtime Package corresponds to one Deployment Package.

---

# 5. Runtime Meta Model

```text
Runtime Package

│

├── Runtime Object Graph

├── Runtime Context

├── Runtime Cache

├── Runtime Component

├── Runtime Dataset

├── Runtime Workflow

├── Runtime Rule

├── Runtime Renderer

└── Runtime Mapping
```

---

# 6. Runtime Package

A Runtime Package represents the compiled executable form of metadata.

Typical attributes include:

| Attribute | Description |
|------------|------------|
| PACKAGE_ID | Deployment Package |
| PACKAGE_VERSION | Runtime version |
| BUILD_ID | Compiler build |
| STATUS | Active / Inactive |
| CHECKSUM | Runtime checksum |

Runtime Packages SHALL be immutable.

---

# 7. Runtime Object Graph

Runtime SHALL organize metadata as an object graph.

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

The graph SHALL be loaded into memory.

---

# 8. Runtime Repository

Runtime SHALL use only RT_* objects.

Examples

```text
RT_APPLICATION

RT_VIEW

RT_DATASET

RT_WORKFLOW

RT_RULE

RT_REPORT

RT_NOTIFICATION
```

Design repositories SHALL NOT be accessed during normal execution.

---

# 9. Runtime Context

Runtime Context SHALL contain execution information.

Typical attributes include:

- User
- Role
- Language
- Timezone
- Tenant
- Company
- Site
- Currency
- Session
- Transaction

Context SHALL be immutable during a request.

---

# 10. Runtime Cache

Runtime SHALL support:

- Metadata Cache
- Dataset Cache
- Security Cache
- Rule Cache
- Workflow Cache
- Renderer Cache

Cache policies SHALL be configurable.

---

# 11. Runtime Components

Compiled Components SHALL reference only runtime objects.

```text
RT_VIEW

↓

RT_COMPONENT

↓

Renderer
```

No design metadata SHALL remain.

---

# 12. Runtime Dataset

Compiled datasets SHALL reference:

- provider;
- schema;
- transformations;
- security;
- cache.

Dataset execution SHALL remain deterministic.

---

# 13. Runtime Workflow

Compiled workflows SHALL execute Directed Graphs.

Workflow instances SHALL reference Runtime Metadata only.

---

# 14. Runtime Rule

Compiled validation rules SHALL be executable without reparsing metadata.

Rules SHALL be optimized during compilation.

---

# 15. Runtime Renderer

Renderers SHALL consume Runtime Components.

Examples

- HTML
- PDF
- Mobile
- JSON
- AI Renderer

Renderers SHALL remain independent from metadata repositories.

---

# 16. Runtime Pipeline

```text
Request

↓

Resolve Context

↓

Load Runtime Graph

↓

Evaluate Security

↓

Execute Rules

↓

Execute Workflow

↓

Execute Dataset

↓

Render

↓

Audit
```

Execution SHALL remain deterministic.

---

# 17. Hot Reload

The Runtime SHALL support metadata activation without process restart.

```text
Deployment

↓

Activation

↓

Cache Refresh

↓

Runtime Switch
```

Requests already in progress SHALL complete using the previously active Runtime Package.

New requests SHALL use the newly activated Runtime Package.

---

# 18. Runtime Mapping

Compilation transforms

```text
Design Metadata

↓

Runtime Metadata

↓

Runtime Package

↓

Execution
```

Logical identity SHALL be preserved.

---

# 19. Constraints

| ID | Constraint |
|----|------------|
| RT-001 | Runtime SHALL use compiled metadata only |
| RT-002 | Runtime Packages SHALL be immutable |
| RT-003 | Runtime Context SHALL be request scoped |
| RT-004 | Design repositories SHALL NOT be accessed during execution |
| RT-005 | Runtime Graph SHALL remain internally consistent |

---

# 20. Relationships

```text
Runtime Package

owns

Runtime Object Graph

owns

Runtime Context

owns

Runtime Cache

references

Deployment Package

references

Security

references

Audit
```

---

# 21. Traceability

```text
Metadata

↓

Compiler

↓

Runtime Package

↓

Execution

↓

Audit
```

Every runtime execution SHALL be traceable to the originating metadata version.

---

# 22. Risks

Potential risks include:

- stale runtime cache;
- inconsistent runtime graphs;
- invalid package activation;
- memory exhaustion;
- version mismatch.

These risks SHALL be mitigated through compiler validation, package integrity checks, cache invalidation, runtime monitoring, and deployment governance.

---

# 23. Summary

The Runtime Repository defines the executable representation of metadata within ODAF.

By compiling design-time metadata into optimized runtime packages, object graphs, caches, and execution contexts, ODAF eliminates runtime dependency on design repositories while providing deterministic execution, high performance, hot reload capability, and traceable runtime behavior.

This architecture forms the execution foundation of the ODAF Kernel and prepares the platform for advanced optimization techniques introduced in Volume 3.

---

# Runtime Repository Model

```text
Runtime Package
        │
        ├── Runtime Object Graph
        ├── Runtime Context
        ├── Runtime Cache
        ├── Runtime Component
        ├── Runtime Dataset
        ├── Runtime Workflow
        ├── Runtime Rule
        ├── Runtime Renderer
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Runtime Package | RT_PACKAGE |
| Runtime Application | RT_APPLICATION |
| Runtime View | RT_VIEW |
| Runtime Component | RT_COMPONENT |
| Runtime Dataset | RT_DATASET |
| Runtime Workflow | RT_WORKFLOW |
| Runtime Rule | RT_RULE |
| Runtime Cache | RT_CACHE |
| Runtime Context | RT_CONTEXT |
| Runtime Report | RT_REPORT |

---

# Next Document

➡ **22-Repository-Physical-Model.md**

The next chapter defines the physical Oracle implementation of the Metadata Repository, including schemas, tablespaces, indexing strategy, partitioning, storage model, naming conventions, and optimization guidelines for enterprise-scale deployments.