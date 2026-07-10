---
document_id: CORE-V3-012
title: Workflow Engine
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-008
  - CORE-V3-011
  - CORE-V3-013
  - DB-V2-015
---

# Chapter 12

# Workflow Engine

---

# 1. Purpose

This chapter defines the Workflow Engine of the Oracle Dynamic Application Framework (ODAF).

The Workflow Engine executes compiler-generated Workflow Graphs that describe business process orchestration.

Rather than interpreting workflow definitions at runtime, the Workflow Engine executes immutable execution graphs generated during compilation.

The Workflow Engine is responsible for orchestration, execution sequencing, branching, synchronization, compensation, and workflow lifecycle management.

---

# 2. Design Objectives

The Workflow Engine SHALL:

- execute Workflow Graphs;
- orchestrate business processes;
- support deterministic execution;
- support parallel execution;
- support compensation;
- remain technology independent;
- expose execution metrics.

---

# 3. Workflow Engine Architecture

```text
Execution Graph
        │
        ▼
Workflow Engine
        │
        ├── Workflow Planner
        ├── Workflow Orchestrator
        ├── Workflow Scheduler
        ├── Graph Executor
        ├── Compensation Manager
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Runtime Services
```

The Workflow Engine SHALL execute Workflow Graphs rather than workflow definitions.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Workflow Execution
```

A Workflow Execution represents one execution instance of a compiled Workflow Graph.

---

# 5. Workflow Meta Model

```text
Workflow Execution

│

├── Workflow Graph

├── Execution Plan

├── Workflow Scheduler

├── Orchestrator

├── Compensation Graph

├── Metrics

├── Diagnostics

└── Execution Result
```

---

# 6. Workflow Graph

The compiler SHALL generate immutable Workflow Graphs.

Typical node types include:

- Start;
- Activity;
- Decision;
- Parallel Branch;
- Join;
- Compensation;
- End.

Workflow Graphs SHALL remain immutable during execution.

---

# 7. Workflow Planner

The Workflow Planner SHALL prepare the execution plan.

Planning activities MAY include:

- dependency ordering;
- branch analysis;
- join analysis;
- compensation planning;
- execution priority.

The execution plan SHALL remain deterministic.

---

# 8. Workflow Orchestrator

The Workflow Orchestrator SHALL coordinate execution.

Responsibilities include:

- node scheduling;
- branch activation;
- synchronization;
- completion detection;
- error propagation.

Business logic SHALL remain outside the orchestrator.

---

# 9. Workflow Scheduler

The Workflow Scheduler SHALL determine execution order.

Example:

```text
Workflow Graph

↓

Ready Queue

↓

Executing

↓

Completed
```

Independent branches MAY execute concurrently.

---

# 10. Parallel Execution

The Workflow Engine SHALL support parallel branches.

Example:

```text
Validate

↓

Fork

↓

Approve

Generate Report

Notify

↓

Join

↓

Complete
```

Parallel execution SHALL preserve deterministic results.

---

# 11. Compensation Management

The Workflow Engine SHALL support compensation.

Example:

```text
Reserve Inventory

↓

Generate Invoice

↓

Payment Failed

↓

Compensation Graph

↓

Release Inventory
```

Compensation SHALL execute according to compiler-generated compensation graphs.

---

# 12. Execution Pipeline

Workflow execution SHALL follow this sequence.

```text
Workflow Graph

↓

Planner

↓

Orchestrator

↓

Scheduler

↓

Graph Execution

↓

Execution Result

↓

Metrics
```

Execution SHALL remain deterministic.

---

# 13. Runtime Metrics

The Workflow Engine SHALL collect:

- workflow duration;
- executed nodes;
- parallel branch count;
- compensation count;
- execution depth;
- orchestration latency.

Metrics SHALL support runtime optimization.

---

# 14. Diagnostics

The Workflow Engine SHALL generate diagnostics for:

- execution failures;
- compensation failures;
- deadlock detection;
- timeout events;
- invalid graph transitions.

Diagnostics SHALL remain traceable.

---

# 15. Constraints

| ID | Constraint |
|----|------------|
| WFE-001 | Workflow Engine SHALL execute immutable Workflow Graphs |
| WFE-002 | Business logic SHALL NOT reside inside the engine |
| WFE-003 | Parallel execution SHALL remain deterministic |
| WFE-004 | Compensation SHALL follow the compiler-generated graph |
| WFE-005 | Runtime SHALL NOT modify Workflow Graphs |

---

# 16. Relationships

```text
Execution Graph

contains

Workflow Graph

planned by

Workflow Planner

executed by

Workflow Orchestrator

scheduled by

Workflow Scheduler

produces

Workflow Result

recorded by

Metrics Collector
```

---

# 17. Traceability

```text
Workflow Metadata

↓

Workflow Graph

↓

Workflow Execution

↓

Execution Result

↓

Audit
```

Every Workflow Execution SHALL remain traceable to the originating metadata and compiler build.

---

# 18. Risks

Potential risks include:

- cyclic workflow graphs;
- deadlocks;
- excessive graph depth;
- failed compensation;
- synchronization errors.

These risks SHALL be mitigated through compiler validation, graph verification, deterministic scheduling, compensation planning, and runtime diagnostics.

---

# 19. Summary

The Workflow Engine provides deterministic execution of business process orchestration within ODAF.

By executing immutable compiler-generated Workflow Graphs through dedicated planning, orchestration, scheduling, and compensation mechanisms, the Workflow Engine separates business process definition from runtime implementation while remaining independent of Oracle-specific technologies.

This architecture enables scalable orchestration, safe parallel execution, and predictable business process automation across the ODAF platform.

---

# Workflow Engine Overview

```text
Execution Graph
        │
        ▼
Workflow Engine
        ├── Workflow Planner
        ├── Workflow Orchestrator
        ├── Workflow Scheduler
        ├── Graph Executor
        ├── Compensation Manager
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Runtime Services
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| WFE_PLANNER | Workflow planning |
| WFE_ORCHESTRATOR | Workflow orchestration |
| WFE_SCHEDULER | Workflow scheduling |
| WFE_GRAPH | Workflow graph execution |
| WFE_COMPENSATION | Compensation execution |
| WFE_METRICS | Workflow metrics |
| WFE_DIAGNOSTICS | Workflow diagnostics |
| WFE_RESULT | Workflow execution result |

---

# Next Document

➡ **13-Rule-Engine.md**

The next chapter defines the Rule Engine, including rule evaluation, decision graphs, policy execution, expression evaluation, rule optimization, and compiler-generated rule plans that provide deterministic business rule execution within the ODAF Runtime.