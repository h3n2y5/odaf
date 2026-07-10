---
document_id: CORE-V3-005
title: Metadata Intermediate Representation
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-003
  - CORE-V3-004
  - CORE-V3-006
---

# Chapter 05

# Metadata Intermediate Representation (MIR)

---

# 1. Purpose

This chapter defines the Metadata Intermediate Representation (MIR) of the Oracle Dynamic Application Framework (ODAF).

MIR is the canonical, implementation-independent representation of enterprise metadata used internally by the Metadata Compiler Infrastructure (MCI).

Rather than generating runtime artifacts directly from metadata repositories, the compiler transforms validated Semantic Metadata Models (SMM) into MIR, where optimization, dependency analysis, validation, and backend generation occur.

MIR is the execution-neutral representation of the ODAF platform.

---

# 2. Design Objectives

The Metadata Intermediate Representation SHALL:

- represent metadata independently from implementation technologies;
- remain immutable after construction;
- model metadata as a directed graph;
- support deterministic optimization;
- enable multiple compiler backends;
- preserve semantic correctness;
- remain fully traceable to the originating metadata.

---

# 3. MIR Architecture

```text
Semantic Metadata Model (SMM)
        │
        ▼
MIR Builder
        │
        ▼
Metadata Intermediate Representation
        │
        ├── Node Graph
        ├── Edge Graph
        ├── Symbol Table
        ├── Type System
        ├── Dependency Graph
        └── Execution Graph
                │
                ▼
Optimizer
                │
                ▼
Backend
```

The MIR SHALL remain independent from Oracle, SQL, PL/SQL, or runtime implementation details.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Metadata Graph
```

A Metadata Graph represents one complete compilation unit in executable form.

---

# 5. MIR Meta Model

```text
Metadata Graph

│

├── Node

├── Edge

├── Symbol

├── Type

├── Dependency

├── Execution Graph

├── Metadata Attributes

└── Compiler Metadata
```

---

# 6. MIR Nodes

Every metadata object SHALL be represented as a Node.

Examples include:

- Application
- Module
- Feature
- Dataset
- Workflow
- Rule
- View
- Integration
- Report
- Security Policy

Nodes SHALL possess immutable identities.

---

# 7. MIR Edges

Edges define relationships between nodes.

Typical edge types include:

- owns;
- references;
- depends_on;
- executes;
- validates;
- secures;
- extends.

Edges SHALL form a Directed Acyclic Graph (DAG) unless explicitly permitted otherwise.

---

# 8. Symbol Table

The MIR SHALL maintain a Symbol Table.

Each symbol SHALL contain:

- identifier;
- canonical name;
- namespace;
- type;
- visibility;
- version.

Symbol resolution SHALL be completed before optimization begins.

---

# 9. Type System

The MIR SHALL define a platform-wide type system.

Typical metadata types include:

- Application;
- Module;
- Feature;
- Dataset;
- Workflow;
- Rule;
- View;
- Integration;
- Notification;
- Report.

Compiler plugins MAY introduce additional metadata types.

---

# 10. Dependency Graph

The MIR SHALL expose an explicit dependency graph.

Example:

```text
Application

↓

Feature

↓

Dataset

↓

Workflow

↓

Rule
```

The compiler SHALL use dependency graphs for:

- validation;
- optimization;
- incremental compilation;
- deployment planning.

---

# 11. Execution Graph

The Execution Graph models executable behavior independently from runtime implementation.

Example:

```text
Request

↓

Security

↓

Validation

↓

Workflow

↓

Dataset

↓

Integration

↓

Rendering

↓

Response
```

The Runtime Kernel SHALL execute compiler-generated execution graphs.

---

# 12. Immutability

After MIR construction:

- Nodes SHALL NOT change.
- Edges SHALL NOT change.
- Symbols SHALL NOT change.
- Types SHALL NOT change.

Optimization SHALL create new MIR versions rather than mutating existing objects.

---

# 13. Compiler Metadata

Each MIR SHALL retain compiler metadata including:

- compilation identifier;
- compiler version;
- metadata version;
- optimization profile;
- build timestamp;
- diagnostics.

Compiler metadata SHALL support complete traceability.

---

# 14. Optimization Boundary

Compiler optimizations SHALL operate exclusively on MIR.

Typical optimization activities include:

- graph simplification;
- dead metadata elimination;
- dependency pruning;
- execution graph optimization;
- symbol resolution optimization;
- type normalization.

The Semantic Metadata Model SHALL remain unchanged.

---

# 15. Constraints

| ID | Constraint |
|----|------------|
| MIR-001 | MIR SHALL remain implementation independent |
| MIR-002 | MIR SHALL be immutable |
| MIR-003 | Optimization SHALL preserve semantic meaning |
| MIR-004 | Symbol resolution SHALL complete before optimization |
| MIR-005 | Every MIR Node SHALL remain traceable to source metadata |

---

# 16. Relationships

```text
Semantic Metadata Model

transformed into

Metadata Graph

contains

Nodes

connected by

Edges

resolved through

Symbol Table

typed by

Type System

optimized by

Compiler Passes

generated into

Runtime Package
```

---

# 17. Traceability

```text
Metadata

↓

Semantic Metadata Model

↓

Metadata Graph

↓

Optimization

↓

Backend

↓

Runtime Package
```

Every MIR object SHALL be traceable to its originating metadata object.

---

# 18. Risks

Potential risks include:

- graph inconsistencies;
- unresolved symbols;
- cyclic dependencies;
- optimizer regressions;
- excessive graph complexity.

These risks SHALL be mitigated through immutable MIR construction, dependency validation, deterministic compiler passes, graph verification, and compiler diagnostics.

---

# 19. Summary

The Metadata Intermediate Representation (MIR) is the canonical internal representation of enterprise metadata within ODAF.

By modeling metadata as immutable graphs composed of nodes, edges, symbols, types, and execution relationships, MIR provides a technology-independent foundation for optimization, backend generation, runtime planning, and future compiler evolution.

This design allows the Metadata Compiler Infrastructure to evolve independently of Oracle-specific implementations while maintaining deterministic behavior and complete metadata traceability.

---

# Metadata Intermediate Representation Overview

```text
Semantic Metadata Model
        │
        ▼
Metadata Graph
        ├── Nodes
        ├── Edges
        ├── Symbol Table
        ├── Type System
        ├── Dependency Graph
        └── Execution Graph
                │
                ▼
Compiler Optimizer
                │
                ▼
Compiler Backend
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| MIR_GRAPH | Metadata graph management |
| MIR_NODE | Immutable node model |
| MIR_EDGE | Graph relationship model |
| MIR_SYMBOL | Symbol table |
| MIR_TYPE | Metadata type system |
| MIR_DEPENDENCY | Dependency graph |
| MIR_EXECUTION | Execution graph |
| MIR_BUILDER | MIR construction |

---

# Next Document

➡ **06-Compiler-Optimizer.md**

The next chapter defines the Compiler Optimizer, including optimization passes, graph transformations, dependency pruning, execution graph optimization, cost models, and compiler optimization strategies that transform MIR into highly optimized runtime artifacts.