---
document_id: CORE-V3-001
title: Architecture Overview
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - SAD-V1-009
  - DB-V2-031
  - DB-V2-032
  - CORE-V3-002
---

# Chapter 01

# Architecture Overview

---

# 1. Purpose

This chapter defines the implementation architecture of the Oracle Dynamic Application Framework (ODAF) Core.

Where Volume 1 establishes the enterprise architecture and Volume 2 defines the metadata repositories, this volume specifies how the platform compiles metadata, executes runtime services, and orchestrates the complete application lifecycle.

The ODAF Core is a **Compiler-Driven Metadata Platform** that transforms metadata into deterministic runtime artifacts executed by a unified runtime kernel.

---

# 2. Design Objectives

The ODAF Core SHALL:

- compile metadata into executable artifacts;
- separate design-time from runtime;
- provide a deterministic execution model;
- centralize runtime orchestration;
- expose platform capabilities through reusable services;
- support extensibility through plugins;
- remain technology-independent at the architectural level.

---

# 3. ODAF Core Architecture

```text
Business Intent
        │
        ▼
Metadata Repository
        │
        ▼
Metadata Compiler Infrastructure (MCI)
        │
        ▼
Metadata Intermediate Representation (MIR)
        │
        ▼
Compiler Optimization
        │
        ▼
Runtime Package
        │
        ▼
Unified Runtime Kernel (URK)
        │
        ├── Dataset Engine
        ├── Workflow Engine
        ├── Rule Engine
        ├── Rendering Engine
        ├── Security Engine
        ├── Integration Engine
        ├── Report Engine
        │
        ▼
Platform Services
        │
        ├── Deployment
        ├── Bootstrap
        ├── Recovery
        ├── Migration
        ├── Conformance
        ├── Performance
        └── Knowledge
        │
        ▼
Business Applications
```

The architecture is compiler-driven. Runtime execution SHALL consume compiled artifacts rather than design-time metadata.

---

# 4. Core Layer Model

The implementation architecture is organized into five logical layers.

```text
Business Layer
        ▲
        │
Platform Services
        ▲
        │
Unified Runtime Kernel
        ▲
        │
Metadata Compiler Infrastructure
        ▲
        │
Metadata Repository
```

Dependencies SHALL always flow downward.

Lower layers SHALL NOT depend on higher layers.

---

# 5. Major Subsystems

The ODAF Core consists of five primary subsystems.

| Subsystem | Responsibility |
|------------|----------------|
| Metadata Compiler Infrastructure (MCI) | Metadata compilation |
| Unified Runtime Kernel (URK) | Runtime execution |
| Platform Services | Platform lifecycle and orchestration |
| Infrastructure Services | Shared runtime capabilities |
| Operations | Deployment, monitoring, and platform management |

Each subsystem SHALL expose well-defined service boundaries.

---

# 6. Metadata Compiler Infrastructure

The Metadata Compiler Infrastructure (MCI) transforms metadata into executable runtime artifacts.

Primary responsibilities include:

- parsing;
- validation;
- semantic analysis;
- dependency resolution;
- intermediate representation (MIR);
- optimization;
- backend generation.

Compilation SHALL be deterministic and reproducible.

---

# 7. Unified Runtime Kernel

The Unified Runtime Kernel (URK) executes compiled runtime packages.

Core responsibilities include:

- runtime context management;
- service resolution;
- execution orchestration;
- object graph navigation;
- runtime cache management;
- transaction coordination.

The runtime SHALL execute compiled metadata only.

---

# 8. Platform Services

Platform Services provide reusable enterprise capabilities.

Examples include:

- deployment;
- bootstrap;
- migration;
- recovery;
- conformance;
- performance;
- knowledge management.

Platform Services SHALL remain independent from individual business applications.

---

# 9. Infrastructure Services

Infrastructure Services provide shared technical capabilities.

Examples include:

- plugin framework;
- event bus;
- scheduler;
- observability;
- telemetry.

These services SHALL support both compiler and runtime operations.

---

# 10. Execution Model

Runtime execution SHALL follow a deterministic pipeline.

```text
Request
        │
        ▼
Resolve Context
        │
        ▼
Locate Runtime Package
        │
        ▼
Resolve Service
        │
        ▼
Execute Object Graph
        │
        ▼
Generate Response
        │
        ▼
Audit
```

No runtime execution SHALL directly consume design-time metadata.

---

# 11. Architectural Principles

The implementation follows these principles.

- Metadata First
- Compiler Driven
- Runtime Optimized
- Immutable Runtime Packages
- Service-Oriented Runtime
- Deterministic Execution
- Plugin-Based Extensibility
- Traceable Lifecycle
- Separation of Design-Time and Runtime

These principles SHALL guide all implementation decisions.

---

# 12. Runtime Isolation

Design-time repositories and runtime repositories SHALL remain isolated.

```text
Design-Time Metadata
        │
        ▼
Compiler
        │
        ▼
Runtime Package
        │
        ▼
Runtime Repository
```

Direct access from runtime to design-time metadata SHALL NOT be permitted.

---

# 13. Extensibility Model

ODAF SHALL support extensibility through plugins.

Extension points MAY include:

- compiler passes;
- backend generators;
- runtime services;
- rendering components;
- integrations;
- deployment strategies.

Plugins SHALL comply with platform conformance rules.

---

# 14. Constraints

| ID | Constraint |
|----|------------|
| CORE-001 | Metadata SHALL remain the single source of truth |
| CORE-002 | Runtime SHALL execute compiled artifacts only |
| CORE-003 | Runtime Packages SHALL be immutable |
| CORE-004 | Layer dependencies SHALL remain unidirectional |
| CORE-005 | Compiler output SHALL be deterministic |

---

# 15. Relationships

```text
Metadata Repository

produces

Compiler Input

compiled by

Metadata Compiler Infrastructure

produces

Runtime Package

executed by

Unified Runtime Kernel

consumed by

Platform Services

supports

Business Applications
```

---

# 16. Traceability

```text
Business Intent

↓

Metadata

↓

Compilation

↓

Runtime Package

↓

Execution

↓

Audit
```

Every runtime execution SHALL be traceable to its originating metadata and compiler build.

---

# 17. Risks

Potential risks include:

- runtime/design-time coupling;
- non-deterministic compilation;
- dependency cycles;
- oversized runtime packages;
- uncontrolled plugin behavior.

These risks SHALL be mitigated through compiler validation, dependency analysis, immutable runtime artifacts, plugin governance, and architectural conformance.

---

# 18. Summary

The ODAF Core architecture defines the implementation blueprint for the platform.

By combining the Metadata Compiler Infrastructure (MCI), Unified Runtime Kernel (URK), Platform Services, and Infrastructure Services into a compiler-driven execution model, ODAF establishes a deterministic, extensible, and enterprise-grade platform for metadata-defined applications.

This chapter provides the architectural foundation for all implementation details described in the remainder of Volume 3.

---

# ODAF Core Architecture Summary

```text
Business Applications
        ▲
        │
Platform Services
        ▲
        │
Unified Runtime Kernel
        ▲
        │
Metadata Compiler Infrastructure
        ▲
        │
Metadata Repository
```

---

# Next Document

➡ **02-Core-Kernel.md**

The next chapter defines the Unified Runtime Kernel, including kernel responsibilities, execution lifecycle, kernel services, runtime orchestration, object graph management, service dispatching, and the internal execution model of the ODAF Core.