---
document_id: STUDIO-V4-005
title: Visual Designer
volume: Volume 4 – ODAF Studio
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-08
last_updated: 2026-07-08

related_documents:
  - STUDIO-V4-001
  - STUDIO-V4-004
  - META-V2-001
  - CORE-V3-005
  - CORE-V3-014
---

# Chapter 5

# Visual Designer

---

# 1. Purpose

This chapter defines the Visual Designer of the Oracle Dynamic Application Framework (ODAF).

The Visual Designer is not a graphical user interface builder, drag-and-drop editor, or visual programming canvas.

Instead, it is a metadata-aware modeling environment that composes and evolves the Metadata Universe through visual abstractions.

Visual artifacts are projections of metadata rather than persistent implementation assets.

The compiler remains the authoritative producer of executable platform capabilities.

---

# 2. Design Objectives

The Visual Designer SHALL:

- model metadata visually;
- compose compiler-verifiable metadata;
- support semantic modeling;
- support intent-driven modeling;
- maintain complete round-trip fidelity;
- remain technology independent;
- expose graph-aware visualization.

---

# 3. Visual Designer Architecture

```text
Business Intent
        │
        ▼
Visual Designer
        │
        ├── Intent Interpreter
        ├── Metadata Composer
        ├── Semantic Modeler
        ├── Property Model
        ├── Graph Projection Engine
        ├── Visualization Engine
        ├── AI Assistant
        └── Round-Trip Manager
                │
                ▼
Metadata Universe
                │
                ▼
Compiler
                │
                ▼
Compiled Graph Universe
```

The Visual Designer SHALL manipulate metadata rather than implementation artifacts.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Visual Metadata Model
```

A Visual Metadata Model represents a visual composition of metadata that can be deterministically transformed into compiler-verifiable metadata artifacts without information loss.

---

# 5. Visual Designer Meta Model

```text
Visual Metadata Model

│

├── Intent Model

├── Metadata Projection

├── Semantic Model

├── Property Model

├── Visualization Graph

├── Round-Trip Mapping

├── AI Context

├── Validation State

└── Designer Session
```

---

# 6. Metadata Composition

The Visual Designer SHALL compose metadata rather than implementation artifacts.

Metadata composition MAY generate:

- datasets;
- workflows;
- rules;
- UI metadata;
- reports;
- integrations;
- security policies;
- deployment metadata.

The Metadata Universe SHALL remain the single source of truth.

---

# 7. Semantic Modeling

The Studio SHALL interpret business meaning rather than graphical layout.

Semantic modeling MAY recognize:

- approval processes;
- master data;
- business documents;
- organizational structures;
- event-driven interactions;
- analytical models.

Semantic interpretation SHALL enrich the Metadata Universe.

---

# 8. Intent-Driven Modeling

Business intent MAY be expressed using natural language.

Examples include:

- "Create a two-level purchasing approval."
- "Add customer credit validation."
- "Generate a supplier onboarding workflow."
- "Create an inventory dashboard."

The Intent Interpreter SHALL transform business intent into metadata proposals.

---

# 9. Metadata Projection

Visual models SHALL be projections of metadata.

Projection SHALL support:

- visual reconstruction;
- semantic visualization;
- graph visualization;
- capability visualization;
- dependency visualization.

Visual representations SHALL never become the authoritative source.

---

# 10. Universal Property Model

Every visual element SHALL expose metadata-defined properties.

Property categories MAY include:

- semantic properties;
- validation properties;
- runtime properties;
- compiler properties;
- governance properties;
- presentation properties.

Properties SHALL remain compiler-verifiable.

---

# 11. Round-Trip Fidelity

The Visual Designer SHALL preserve complete round-trip fidelity.

The following transformation SHALL always be reversible:

```text
Metadata

↓

Visual Projection

↓

Metadata
```

No metadata SHALL be lost through visualization or editing.

---

# 12. AI-Assisted Modeling

Artificial Intelligence SHALL assist in:

- metadata generation;
- semantic completion;
- model refinement;
- architectural recommendations;
- validation guidance;
- documentation generation.

AI SHALL operate on metadata semantics rather than implementation details.

---

# 13. Designer Lifecycle

Every design activity SHALL follow a deterministic lifecycle.

```text
Intent

↓

Compose

↓

Validate

↓

Project

↓

Compile

↓

Observe

↓

Refine
```

---

# 14. Constraints

| ID | Constraint |
|----|------------|
| VDS-001 | Visual models SHALL represent metadata only |
| VDS-002 | Metadata SHALL remain the authoritative source |
| VDS-003 | Round-trip transformations SHALL be lossless |
| VDS-004 | Semantic interpretation SHALL precede compilation |
| VDS-005 | Visual Designer SHALL remain technology independent |

---

# 15. Relationships

```text
Business Intent

interpreted by

Intent Interpreter

composed into

Metadata Universe

projected by

Visualization Engine

compiled by

Compiler

executed by

Runtime Platform
```

---

# 16. Traceability

```text
Business Intent

↓

Intent Model

↓

Metadata Universe

↓

Compiled Graph Universe

↓

Runtime Platform
```

Every visual modeling decision SHALL remain traceable from business intent through runtime execution.

---

# 17. Risks

Potential risks include:

- semantic ambiguity;
- inconsistent visual projections;
- incomplete intent interpretation;
- metadata divergence;
- visualization complexity.

These risks SHALL be mitigated through compiler validation, deterministic metadata projection, semantic analysis, round-trip guarantees, AI-assisted guidance, and architectural governance.

---

# 18. Summary

The Visual Designer defines a metadata-centric modeling environment for the ODAF Studio.

Rather than functioning as a graphical editor for implementation artifacts, the Visual Designer composes the Metadata Universe through semantic, intent-driven, and graph-aware modeling.

By treating visual representations as projections of authoritative metadata, the Studio enables deterministic compilation, lossless round-trip transformations, AI-assisted modeling, and complete architectural traceability while preserving the principles of a Compiler-Driven Metadata Platform.