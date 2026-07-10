---
document_id: DB-V2-031
title: Compiler Repository
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-021
  - DB-V2-025
  - DB-V2-030
---

# Chapter 31

# Compiler Repository

---

# 1. Purpose

This chapter defines the Compiler Repository of the Oracle Dynamic Application Framework (ODAF).

The ODAF Compiler transforms design-time metadata into optimized runtime artifacts.

Unlike traditional code generators, the Compiler performs semantic analysis, dependency resolution, optimization, and target generation before producing executable Runtime Packages.

The Compiler is the primary transformation engine of the platform.

---

# 2. Design Objectives

The Compiler SHALL:

- compile metadata rather than interpret it;
- validate semantic correctness;
- optimize runtime execution;
- generate deterministic runtime artifacts;
- support multiple compilation targets;
- preserve metadata traceability.

---

# 3. Compiler Architecture

```text
Metadata Repository

↓

Parser

↓

Validator

↓

Semantic Analyzer

↓

Dependency Resolver

↓

Metadata IR (MIR)

↓

Optimizer

↓

Target Generator

↓

Runtime Package
```

Compilation SHALL be deterministic.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Compilation Unit
```

A Compilation Unit represents the smallest independently compilable metadata boundary.

Typical units include:

- Application
- Module
- Feature
- Shared Library

---

# 5. Compiler Meta Model

```text
Compilation Unit

│

├── Parser
├── Validator
├── Semantic Model
├── Dependency Graph
├── MIR
├── Optimizer
├── Target Generator
├── Build Artifact
└── Runtime Package
```

---

# 6. Parser

The Parser converts repository metadata into an internal abstract representation.

Responsibilities include:

- metadata loading;
- syntax validation;
- object discovery;
- reference resolution.

The Parser SHALL NOT perform optimization.

---

# 7. Validator

Validation SHALL verify:

- metadata completeness;
- mandatory attributes;
- reference integrity;
- repository consistency;
- governance policies.

Compilation SHALL stop when mandatory validation fails.

---

# 8. Semantic Analyzer

The Semantic Analyzer interprets metadata meaning rather than structure.

Responsibilities include:

- aggregate validation;
- lifecycle validation;
- security consistency;
- workflow correctness;
- dataset compatibility.

Semantic errors SHALL prevent compilation.

---

# 9. Dependency Resolver

Dependencies SHALL be represented as a Directed Acyclic Graph (DAG).

Compiler SHALL detect:

- cyclic dependencies;
- missing references;
- version conflicts;
- invalid cross-domain references.

Dependency analysis SHALL precede optimization.

---

# 10. Metadata Intermediate Representation (MIR)

The Compiler SHALL generate a Metadata Intermediate Representation (MIR).

MIR SHALL represent metadata independently from:

- Oracle;
- PL/SQL;
- Runtime;
- deployment technology.

Typical MIR elements include:

- Nodes;
- Edges;
- Symbols;
- Types;
- Expressions;
- Contracts.

---

# 11. Optimizer

Compiler optimizations MAY include:

- dead metadata elimination;
- dependency pruning;
- rule inlining;
- view optimization;
- workflow simplification;
- dataset optimization;
- constant propagation.

Optimization SHALL preserve observable behavior.

---

# 12. Target Generator

The Compiler SHALL support multiple compilation targets.

Examples include:

| Target | Description |
|---------|-------------|
| Oracle Runtime | Primary runtime target |
| Oracle DDL | Physical repository generation |
| PL/SQL Packages | Service implementation |
| REST Specification | API generation |
| Documentation | Technical documentation |
| Future Backends | Extensible targets |

Target generation SHALL be plugin-based.

---

# 13. Build Artifacts

Compilation SHALL produce immutable artifacts.

Examples:

- Runtime Package;
- Generated DDL;
- Generated Packages;
- Runtime Metadata;
- Deployment Manifest;
- Build Report.

Artifacts SHALL be versioned.

---

# 14. Incremental Compilation

Compiler SHOULD support incremental compilation.

Only changed Compilation Units and their affected dependencies SHALL be recompiled.

Dependency analysis SHALL determine the recompilation scope.

---

# 15. Runtime Package Generation

Compilation SHALL generate Runtime Packages containing:

- Runtime Metadata;
- Runtime Graph;
- Dependency Graph;
- Runtime Manifest;
- Checksums.

Runtime Packages SHALL be immutable.

---

# 16. Compiler Pipeline

```text
Metadata

↓

Parse

↓

Validate

↓

Semantic Analysis

↓

Dependency Resolution

↓

MIR

↓

Optimization

↓

Target Generation

↓

Packaging
```

Each compilation stage SHALL produce deterministic output.

---

# 17. Constraints

| ID | Constraint |
|----|------------|
| CMP-001 | Compilation SHALL be deterministic |
| CMP-002 | MIR SHALL be implementation independent |
| CMP-003 | Cyclic dependencies SHALL be rejected |
| CMP-004 | Runtime Packages SHALL be immutable |
| CMP-005 | Compiler SHALL preserve metadata traceability |

---

# 18. Relationships

```text
Compilation Unit

owns

Semantic Model

owns

Dependency Graph

owns

MIR

owns

Optimizer

owns

Target Generator

produces

Runtime Package
```

---

# 19. Traceability

```text
Metadata

↓

Compilation Unit

↓

MIR

↓

Target

↓

Runtime Package

↓

Deployment
```

Every generated artifact SHALL be traceable to the originating metadata.

---

# 20. Risks

Potential risks include:

- excessive compilation time;
- cyclic dependencies;
- optimizer regressions;
- inconsistent target generation;
- compiler/runtime version mismatch.

These risks SHALL be mitigated through dependency analysis, incremental compilation, regression testing, compiler versioning, and deterministic build processes.

---

# 21. Summary

The Compiler Repository defines the transformation architecture at the heart of ODAF.

By introducing parsing, semantic analysis, dependency resolution, metadata intermediate representation (MIR), optimization, and target generation, ODAF treats metadata compilation as a true compiler process rather than simple code generation.

This architecture enables deterministic builds, multiple compilation targets, runtime optimization, and long-term extensibility while preserving metadata as the single source of truth.

---

# Compiler Architecture Overview

```text
Metadata Repository
        │
        ▼
Parser
        │
        ▼
Validator
        │
        ▼
Semantic Analyzer
        │
        ▼
Dependency Resolver
        │
        ▼
Metadata IR (MIR)
        │
        ▼
Optimizer
        │
        ▼
Target Generator
        │
        ▼
Runtime Package
```

---

# Planned Compiler Components

| Component | Responsibility |
|-----------|----------------|
| CMP_PARSER | Metadata parsing |
| CMP_VALIDATOR | Structural validation |
| CMP_SEMANTIC | Semantic analysis |
| CMP_DEPENDENCY | Dependency resolution |
| CMP_MIR | Intermediate representation |
| CMP_OPTIMIZER | Metadata optimization |
| CMP_TARGET | Target generation |
| CMP_PACKAGER | Runtime package assembly |

---

# Next Document

➡ **32-Repository-Versioning.md**

The next chapter defines repository versioning, metadata evolution, compatibility rules, semantic versioning, migration strategies, dependency version resolution, and long-term lifecycle management across the ODAF platform.