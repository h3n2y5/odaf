---
document_id: CORE-V3-004
title: Compiler Frontend
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-003
  - CORE-V3-005
  - DB-V2-031
---

# Chapter 04

# Compiler Frontend

---

# 1. Purpose

This chapter defines the Compiler Frontend of the Oracle Dynamic Application Framework (ODAF).

The Compiler Frontend is responsible for transforming heterogeneous enterprise metadata into a validated Semantic Metadata Model (SMM), which serves as the canonical input to the Metadata Intermediate Representation (MIR).

Unlike traditional language compilers, the ODAF Compiler Frontend processes metadata repositories rather than source code.

---

# 2. Design Objectives

The Compiler Frontend SHALL:

- load metadata from supported sources;
- normalize heterogeneous metadata into a canonical representation;
- validate structural correctness;
- resolve cross-object references;
- construct a Semantic Metadata Model (SMM);
- produce deterministic compiler diagnostics.

---

# 3. Frontend Architecture

```text
Metadata Sources
        │
        ▼
Metadata Loader
        │
        ▼
Metadata Normalizer
        │
        ▼
Parser
        │
        ▼
Reference Resolver
        │
        ▼
Semantic Metadata Model (SMM)
        │
        ▼
Metadata IR (MIR)
```

The Frontend SHALL perform semantic preparation but SHALL NOT perform runtime optimization.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Semantic Metadata Model
```

The Semantic Metadata Model represents the canonical in-memory view of all validated metadata prior to MIR generation.

---

# 5. Frontend Meta Model

```text
Semantic Metadata Model

│

├── Metadata Source

├── Metadata Loader

├── Metadata Normalizer

├── Parser

├── Symbol Table

├── Reference Resolver

├── Diagnostics

└── Compiler Context
```

---

# 6. Metadata Sources

The Compiler Frontend SHALL support multiple metadata sources.

Typical sources include:

- Oracle Metadata Repository;
- JSON documents;
- YAML documents;
- REST services;
- Plugin repositories;
- Future repository providers.

All sources SHALL be converted into a common canonical representation.

---

# 7. Metadata Loader

The Metadata Loader SHALL:

- discover metadata objects;
- load metadata into memory;
- validate source accessibility;
- preserve metadata identity;
- detect duplicate definitions.

Loading SHALL remain independent from semantic validation.

---

# 8. Metadata Normalizer

The Metadata Normalizer converts source-specific metadata into canonical compiler objects.

Examples include:

```text
APP_*  → Application

MOD_*  → Module

FEAT_* → Feature

DS_*   → Dataset

WF_*   → Workflow
```

Normalization SHALL eliminate repository-specific variations before parsing.

---

# 9. Parser

The Parser SHALL validate metadata structure.

Responsibilities include:

- required attributes;
- structural completeness;
- syntax validation;
- object discovery;
- metadata classification.

The Parser SHALL NOT resolve semantic dependencies.

---

# 10. Symbol Table

The Frontend SHALL maintain a Symbol Table.

The Symbol Table SHALL contain:

- metadata identifiers;
- canonical names;
- object categories;
- namespaces;
- visibility;
- versions.

The Symbol Table SHALL support efficient reference resolution.

---

# 11. Reference Resolver

The Reference Resolver SHALL resolve:

- Application references;
- Module references;
- Feature references;
- Dataset references;
- Workflow references;
- Rule references;
- UI references;
- Integration references.

Unresolved references SHALL generate compiler diagnostics.

---

# 12. Semantic Metadata Model (SMM)

The Frontend SHALL construct a Semantic Metadata Model.

The SMM SHALL represent:

- object identity;
- ownership;
- relationships;
- types;
- lifecycle;
- constraints;
- dependencies.

The SMM SHALL remain independent from Oracle implementation.

---

# 13. Compiler Diagnostics

The Frontend SHALL produce standardized diagnostics.

Supported diagnostic categories include:

| Level | Description |
|---------|-------------|
| Error | Compilation cannot continue |
| Warning | Compilation continues with risk |
| Information | Informational message |
| Hint | Suggested improvement |

Every diagnostic SHALL include:

- diagnostic identifier;
- severity;
- affected metadata object;
- source location;
- human-readable description.

---

# 14. Compiler Context

The Compiler Context SHALL maintain shared compilation state.

Typical contents include:

- active compilation unit;
- symbol table;
- diagnostics;
- compiler options;
- loaded metadata;
- compilation profile.

The Compiler Context SHALL remain isolated for each compilation session.

---

# 15. Constraints

| ID | Constraint |
|----|------------|
| FRT-001 | All metadata SHALL be normalized before parsing |
| FRT-002 | Semantic Metadata Model SHALL be deterministic |
| FRT-003 | All references SHALL be resolved before MIR generation |
| FRT-004 | Diagnostics SHALL be reproducible |
| FRT-005 | Metadata sources SHALL remain implementation independent |

---

# 16. Relationships

```text
Metadata Source

loaded by

Metadata Loader

normalized by

Metadata Normalizer

parsed by

Parser

resolved by

Reference Resolver

produces

Semantic Metadata Model

consumed by

Metadata IR
```

---

# 17. Traceability

```text
Metadata Source

↓

Loader

↓

Normalizer

↓

Parser

↓

Semantic Metadata Model

↓

Metadata IR
```

Every semantic object SHALL be traceable to its originating metadata source.

---

# 18. Risks

Potential risks include:

- inconsistent metadata sources;
- duplicate object definitions;
- unresolved references;
- namespace conflicts;
- non-deterministic diagnostics.

These risks SHALL be mitigated through canonical normalization, symbol table validation, deterministic diagnostics, and strict reference resolution.

---

# 19. Summary

The Compiler Frontend transforms heterogeneous metadata into a unified Semantic Metadata Model.

By separating metadata loading, normalization, parsing, symbol management, reference resolution, and diagnostics into distinct stages, ODAF establishes a modern compiler frontend specifically designed for enterprise metadata rather than programming languages.

This architecture provides the validated semantic foundation required for Metadata Intermediate Representation (MIR) generation and all subsequent compiler stages.

---

# Compiler Frontend Overview

```text
Metadata Sources
        │
        ▼
Metadata Loader
        │
        ▼
Metadata Normalizer
        │
        ▼
Parser
        │
        ▼
Symbol Table
        │
        ▼
Reference Resolver
        │
        ▼
Semantic Metadata Model (SMM)
        │
        ▼
Metadata IR (MIR)
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| FRT_LOADER | Metadata loading |
| FRT_NORMALIZER | Canonical normalization |
| FRT_PARSER | Structural parsing |
| FRT_SYMBOL | Symbol table management |
| FRT_RESOLVER | Reference resolution |
| FRT_DIAGNOSTICS | Compiler diagnostics |
| FRT_CONTEXT | Compilation context |
| FRT_SMM | Semantic Metadata Model |

---

# Next Document

➡ **05-Metadata-IR.md**

The next chapter defines the Metadata Intermediate Representation (MIR), including its object model, graph representation, execution semantics, optimization boundaries, and its role as the implementation-independent intermediate form used throughout the Metadata Compiler Infrastructure.