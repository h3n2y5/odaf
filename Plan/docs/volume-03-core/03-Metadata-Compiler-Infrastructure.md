---
document_id: CORE-V3-003
title: Metadata Compiler Infrastructure
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-001
  - CORE-V3-002
  - CORE-V3-004
  - DB-V2-031
---

# Chapter 03

# Metadata Compiler Infrastructure

---

# 1. Purpose

This chapter defines the Metadata Compiler Infrastructure (MCI) of the Oracle Dynamic Application Framework (ODAF).

The Metadata Compiler Infrastructure transforms enterprise metadata into deterministic runtime artifacts through a multi-stage compilation pipeline.

Unlike traditional code generators, the MCI performs semantic analysis, dependency resolution, optimization, and backend generation before producing executable Runtime Packages.

The MCI is the primary transformation engine of the ODAF platform.

---

# 2. Design Objectives

The Metadata Compiler Infrastructure SHALL:

- compile metadata rather than interpret it;
- provide deterministic compilation;
- separate frontend and backend responsibilities;
- support extensible compiler passes;
- generate immutable runtime artifacts;
- support multiple compilation targets;
- preserve complete traceability.

---

# 3. Compiler Architecture

```text
Metadata Repository
        │
        ▼
Compiler Frontend
        │
        ▼
Metadata Intermediate Representation (MIR)
        │
        ▼
Pass Manager
        │
        ▼
Optimization Passes
        │
        ▼
Compiler Backend
        │
        ▼
Packager
        │
        ▼
Runtime Package
```

Compilation SHALL be deterministic and reproducible.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Compilation Unit
```

A Compilation Unit represents the smallest independently compilable metadata boundary.

Typical examples include:

- Application
- Module
- Feature
- Shared Library
- Platform Extension

---

# 5. Compiler Meta Model

```text
Compilation Unit

│

├── Frontend

├── Semantic Model

├── Dependency Graph

├── Metadata IR

├── Pass Manager

├── Backend

├── Build Artifact

└── Runtime Package
```

---

# 6. Compiler Frontend

The Compiler Frontend converts metadata into a validated semantic model.

Responsibilities include:

- metadata loading;
- syntax validation;
- semantic validation;
- dependency discovery;
- type resolution;
- object resolution.

The Frontend SHALL NOT perform runtime optimization.

---

# 7. Metadata Intermediate Representation (MIR)

The Compiler SHALL produce a Metadata Intermediate Representation (MIR).

MIR SHALL abstract metadata independently from:

- Oracle;
- PL/SQL;
- Runtime implementation;
- deployment technology.

Typical MIR elements include:

- Nodes;
- Edges;
- Symbols;
- Types;
- Contracts;
- Expressions;
- Execution Graphs.

---

# 8. Pass Manager

The Pass Manager SHALL coordinate compiler passes.

Example pipeline:

```text
Pass 1

↓

Validation

↓

Semantic Normalization

↓

Dependency Resolution

↓

Optimization

↓

Backend Preparation
```

Additional passes MAY be contributed by plugins.

---

# 9. Optimization Passes

Compiler optimizations MAY include:

- dead metadata elimination;
- dependency pruning;
- workflow simplification;
- dataset optimization;
- projection pruning;
- constant propagation;
- execution graph optimization.

Every optimization SHALL preserve semantic correctness.

---

# 10. Compiler Backend

The Compiler Backend transforms MIR into target-specific artifacts.

Supported targets MAY include:

| Target | Description |
|---------|-------------|
| Oracle Runtime | Executable runtime |
| Oracle DDL | Physical schema |
| PL/SQL | Runtime packages |
| REST/OpenAPI | API specification |
| Documentation | Technical documentation |
| Future Backends | Extensible targets |

Backends SHALL remain independent from the frontend.

---

# 11. Build Artifacts

Compilation SHALL produce immutable build artifacts.

Typical artifacts include:

- Runtime Package;
- Deployment Package;
- Seed Package;
- Migration Package;
- Backup Package;
- Build Manifest;
- Conformance Report.

Artifacts SHALL be versioned and traceable.

---

# 12. Compilation Pipeline

The Metadata Compiler Infrastructure SHALL execute the following stages.

```text
Metadata

↓

Frontend

↓

Semantic Model

↓

Metadata IR

↓

Pass Manager

↓

Optimization

↓

Backend

↓

Packager

↓

Runtime Package
```

Every stage SHALL generate deterministic output.

---

# 13. Extensibility

The MCI SHALL support compiler extensions.

Extension points MAY include:

- parser extensions;
- validation rules;
- optimization passes;
- backend generators;
- packaging strategies;
- diagnostics.

Extensions SHALL comply with platform governance.

---

# 14. Constraints

| ID | Constraint |
|----|------------|
| MCI-001 | Compilation SHALL be deterministic |
| MCI-002 | MIR SHALL remain implementation independent |
| MCI-003 | Backend SHALL NOT modify semantic meaning |
| MCI-004 | Build artifacts SHALL be immutable |
| MCI-005 | Compiler passes SHALL be ordered deterministically |

---

# 15. Relationships

```text
Compilation Unit

owns

Frontend

produces

Metadata IR

processed by

Pass Manager

optimized by

Optimization Passes

generated by

Backend

packaged as

Runtime Package
```

---

# 16. Traceability

```text
Metadata

↓

Compilation Unit

↓

Metadata IR

↓

Backend

↓

Runtime Package

↓

Deployment
```

Every compiler artifact SHALL be traceable to the originating metadata version and compiler build.

---

# 17. Risks

Potential risks include:

- non-deterministic compilation;
- optimizer regressions;
- backend inconsistencies;
- invalid extension passes;
- compilation bottlenecks.

These risks SHALL be mitigated through compiler validation, pass ordering, immutable artifacts, regression testing, and conformance verification.

---

# 18. Summary

The Metadata Compiler Infrastructure is the transformation core of ODAF.

By separating frontend processing, semantic modeling, metadata intermediate representation, optimization passes, backend generation, and packaging into independent compiler stages, ODAF establishes a modern compiler architecture for enterprise metadata.

This architecture enables deterministic builds, multiple backend targets, extensible optimization, and long-term platform evolution while preserving metadata as the single source of truth.

---

# Metadata Compiler Infrastructure Overview

```text
Metadata Repository
        │
        ▼
Compiler Frontend
        │
        ▼
Metadata Intermediate Representation (MIR)
        │
        ▼
Pass Manager
        │
        ▼
Optimization Passes
        │
        ▼
Compiler Backend
        │
        ▼
Packager
        │
        ▼
Runtime Package
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| MCI_FRONTEND | Metadata parsing and validation |
| MCI_SEMANTIC | Semantic analysis |
| MCI_MIR | Intermediate representation |
| MCI_PASS | Compiler pass orchestration |
| MCI_OPTIMIZER | Optimization passes |
| MCI_BACKEND | Target generation |
| MCI_PACKAGER | Runtime packaging |
| MCI_DIAGNOSTICS | Compiler diagnostics |

---

# Next Document

➡ **04-Compiler-Frontend.md**

The next chapter defines the Compiler Frontend, including metadata parsing, syntax validation, semantic validation, symbol resolution, diagnostics, and the construction of the initial semantic model used throughout the Metadata Compiler Infrastructure.