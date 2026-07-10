---
document_id: STUDIO-V4-007
title: Workflow Designer
volume: Volume 4 – ODAF Studio
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-08
last_updated: 2026-07-08

related_documents:
  - STUDIO-V4-005
  - STUDIO-V4-006
  - CORE-V3-012
  - CORE-V3-013
  - CORE-V3-015
---

# Chapter 7

# Workflow Designer

---

# 1. Purpose

This chapter defines the Workflow Designer of the Oracle Dynamic Application Framework (ODAF).

The Workflow Designer is not a flowchart editor, BPMN designer, approval builder, or visual automation canvas.

Instead, it is a compiler-aware process modeling environment for composing workflow metadata that becomes compiler-generated Executable Process Graphs.

The Workflow Designer enables users to model business processes semantically, validate them deterministically, simulate execution scenarios, and compile them into runtime-executable workflow graphs.

---

# 2. Design Objectives

The Workflow Designer SHALL:

- model business process intent as metadata;
- compose compiler-verifiable workflow graphs;
- support semantic process nodes;
- support policy-driven workflow behavior;
- support simulation and scenario testing;
- support compensation modeling;
- remain technology independent.

---

# 3. Workflow Designer Architecture

```text
Business Process Intent
        │
        ▼
Workflow Designer
        │
        ├── Process Intent Interpreter
        ├── Workflow Metadata Composer
        ├── Semantic Process Modeler
        ├── Policy Modeler
        ├── Compensation Modeler
        ├── Simulation Engine
        ├── AI Workflow Assistant
        └── Compiler Bridge
                │
                ▼
Workflow Metadata
                │
                ▼
Compiler
                │
                ▼
Executable Process Graph
                │
                ▼
Workflow Engine
```

The Workflow Designer SHALL manipulate workflow metadata rather than runtime implementation constructs.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Workflow Model
```

A Workflow Model represents a semantic business process definition composed of metadata, policies, actors, decisions, compensation rules, and compiler-verifiable process relationships.

---

# 5. Workflow Designer Meta Model

```text
Workflow Model

│

├── Process Intent

├── Semantic Process Graph

├── Actor Model

├── Decision Model

├── Policy Model

├── Compensation Graph

├── Scenario Catalog

├── Simulation Result

├── Compiler Diagnostics

└── Workflow History
```

---

# 6. Semantic Process Graph

The Workflow Designer SHALL model workflows as semantic process graphs.

Typical node types MAY include:

- Start Event;
- Activity;
- Approval;
- Decision;
- Rule Evaluation;
- Notification;
- Integration Call;
- Human Task;
- Timer;
- Escalation;
- Compensation;
- Completion.

Nodes SHALL represent business semantics rather than drawing shapes.

---

# 7. Process Relationships

Edges in the Workflow Designer SHALL represent process semantics.

Relationship types MAY include:

- triggers;
- requires;
- approves;
- rejects;
- escalates;
- compensates;
- waits for;
- completes;
- invokes;
- notifies.

Relationship semantics SHALL be compiler-verifiable.

---

# 8. Actor Model

The Workflow Designer SHALL support explicit actors.

Actors MAY include:

- requester;
- approver;
- reviewer;
- administrator;
- system actor;
- external actor;
- delegated actor.

Actors SHALL be resolved through security and identity metadata.

---

# 9. Policy Model

Workflow behavior SHALL be governed by metadata-defined policies.

Policies MAY include:

- approval policy;
- timeout policy;
- escalation policy;
- delegation policy;
- retry policy;
- compensation policy;
- audit policy.

Policies SHALL be compiler-verifiable.

---

# 10. Decision and Rule Integration

Workflow decisions SHALL be integrated with Rule Graphs.

Examples MAY include:

- amount-based approval;
- risk-based routing;
- organization-based escalation;
- status-based transition;
- compliance-based blocking.

Decision logic SHALL NOT be embedded as implementation code inside workflows.

---

# 11. Compensation Modeling

The Workflow Designer SHALL support compensation.

Compensation MAY model:

- rollback activities;
- release reserved resources;
- reverse integration calls;
- cancel notifications;
- restore previous state.

Compensation SHALL be represented as a compiler-generated Compensation Graph.

---

# 12. Simulation

The Workflow Designer SHALL support scenario simulation.

Simulation scenarios MAY include:

- normal approval;
- rejection;
- timeout;
- escalation;
- compensation;
- integration failure;
- authorization failure.

Simulation SHALL execute against metadata and compiled graph semantics before deployment.

---

# 13. AI-Assisted Workflow Modeling

Artificial Intelligence SHALL assist workflow modeling.

AI MAY support:

- generating workflow drafts from intent;
- identifying missing actors;
- suggesting approval policies;
- detecting process risks;
- proposing escalation paths;
- generating simulation scenarios.

AI SHALL produce metadata proposals rather than direct runtime logic.

---

# 14. Compiler Integration

Every workflow modification SHALL be validated by the compiler.

Compiler feedback MAY include:

- semantic diagnostics;
- unresolved actors;
- invalid policies;
- unreachable nodes;
- cyclic dependencies;
- missing compensation;
- security violations.

Compiler validation SHALL remain deterministic.

---

# 15. Workflow Designer Lifecycle

Every workflow modeling activity SHALL follow a deterministic lifecycle.

```text
Intent

↓

Model Process

↓

Define Actors

↓

Define Policies

↓

Validate

↓

Simulate

↓

Compile

↓

Deploy
```

---

# 16. Constraints

| ID | Constraint |
|----|------------|
| WFD-001 | Workflow Designer SHALL model workflow metadata only |
| WFD-002 | Workflow graphs SHALL be compiler-verifiable |
| WFD-003 | Decision logic SHALL reside in Rule Graphs |
| WFD-004 | Compensation SHALL be modeled explicitly |
| WFD-005 | Workflow Designer SHALL remain technology independent |

---

# 17. Relationships

```text
Business Process Intent

interpreted by

Workflow Designer

composed into

Workflow Metadata

integrated with

Rule Graph

Notification Graph

Security Graph

compiled into

Executable Process Graph
```

---

# 18. Traceability

```text
Business Process Intent

↓

Workflow Model

↓

Workflow Metadata

↓

Executable Process Graph

↓

Workflow Execution

↓

Audit
```

Every workflow SHALL remain traceable from process intent through runtime execution and audit records.

---

# 19. Risks

Potential risks include:

- ambiguous business intent;
- missing actors;
- hidden decision logic;
- incomplete compensation;
- invalid escalation policies;
- overcomplicated workflow graphs.

These risks SHALL be mitigated through semantic modeling, compiler validation, explicit policy modeling, simulation, AI-assisted analysis, and governance.

---

# 20. Summary

The Workflow Designer defines a compiler-aware process modeling environment for ODAF Studio.

Rather than functioning as a BPMN or flowchart editor, the Workflow Designer composes semantic workflow metadata that can be validated, simulated, compiled, and executed as an immutable Executable Process Graph.

This architecture enables deterministic business process modeling, policy-driven workflow behavior, explicit compensation, AI-assisted design, and complete traceability while preserving the principles of a Compiler-Driven Metadata Platform.

---

# Architect Note AN-0099 — Executable Process Graph Composer (EPGC)

The ODAF Workflow Designer formally adopts the **Executable Process Graph Composer (EPGC)** architectural model.

```text
Business Process Intent
        │
        ▼
Executable Process Graph Composer
        │
        ├── Process Intent Model
        ├── Semantic Process Graph
        ├── Actor Graph
        ├── Policy Graph
        ├── Decision Graph
        ├── Compensation Graph
        ├── Scenario Graph
        └── Compiler Diagnostics
                │
                ▼
Workflow Metadata
                │
                ▼
Compiler
                │
                ▼
Executable Process Graph
                │
                ▼
Workflow Engine
```

The **Executable Process Graph Composer (EPGC)** establishes that workflows in ODAF are not visual automations or procedural scripts. They are compiler-verifiable business process graphs composed from semantic metadata, policy metadata, rule metadata, security metadata, and compensation metadata.

This allows ODAF Studio to model workflows as deterministic architectural artifacts that can be simulated, validated, deployed, observed, and evolved throughout the platform lifecycle.