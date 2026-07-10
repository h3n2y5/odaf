---
document_id: CORE-V3-028
title: Scheduler
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-008
  - CORE-V3-012
  - CORE-V3-021
  - CORE-V3-024
  - CORE-V3-027
---

# Chapter 28

# Scheduler

---

# 1. Purpose

This chapter defines the Scheduler of the Oracle Dynamic Application Framework (ODAF).

The Scheduler executes compiler-generated Scheduling Graphs that coordinate deterministic execution of jobs, workflows, reports, integrations, maintenance tasks, and platform services.

Rather than relying on external schedulers or timer-based execution, the Scheduler executes immutable Execution Plans generated during compilation.

The Scheduler provides deterministic execution orchestration across the entire platform.

---

# 2. Design Objectives

The Scheduler SHALL:

- execute Scheduling Graphs;
- support deterministic scheduling;
- support dependency-aware execution;
- support execution windows;
- support priorities and concurrency policies;
- support multiple trigger types;
- remain implementation independent;
- expose scheduling metrics.

---

# 3. Scheduler Architecture

```text
Execution Metadata
        │
        ▼
Scheduler
        │
        ├── Scheduling Planner
        ├── Trigger Resolver
        ├── Dependency Resolver
        ├── Window Manager
        ├── Priority Manager
        ├── Concurrency Manager
        ├── Scheduler Adapter
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Execution Graph
```

The Scheduler SHALL execute compiler-generated Scheduling Graphs only.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Scheduled Execution
```

A Scheduled Execution represents one execution instance of a compiled Scheduling Graph.

---

# 5. Scheduler Meta Model

```text
Scheduled Execution

│

├── Scheduling Graph

├── Execution Plan

├── Trigger Policy

├── Dependency Graph

├── Execution Window

├── Priority Policy

├── Concurrency Policy

├── Scheduler Adapter

├── Metrics

├── Diagnostics

└── Execution Result
```

---

# 6. Scheduling Graph

The compiler SHALL generate immutable Scheduling Graphs.

Typical node types include:

- Trigger;
- Schedule;
- Dependency;
- Window;
- Priority;
- Concurrency;
- Retry;
- Deadline;
- Execution;
- Completion.

Scheduling Graphs SHALL remain immutable during execution.

---

# 7. Scheduling Planner

The Scheduling Planner SHALL generate an Execution Plan.

Planning activities MAY include:

- dependency analysis;
- trigger resolution;
- priority ordering;
- window validation;
- concurrency planning;
- retry planning.

Execution Plans SHALL remain deterministic.

---

# 8. Trigger Resolution

Supported trigger types MAY include:

- time-based;
- event-based;
- dependency completion;
- manual execution;
- API invocation;
- plugin trigger.

Trigger evaluation SHALL remain deterministic.

---

# 9. Dependency Management

The Scheduler SHALL execute dependency-aware schedules.

Example:

```text
Import Data

↓

Validate Dataset

↓

Workflow

↓

Notification
```

Dependencies SHALL be compiler-generated.

---

# 10. Execution Windows

Execution windows MAY include:

- fixed schedules;
- recurring schedules;
- maintenance windows;
- business hours;
- blackout periods.

Execution SHALL occur only inside approved windows.

---

# 11. Priority and Concurrency

Scheduling policies SHALL support:

- priority queues;
- concurrency limits;
- mutual exclusion;
- parallel execution;
- distributed execution.

Scheduling policies SHALL be compiler-generated.

---

# 12. Scheduler Adapters

Scheduling SHALL occur through Scheduler Adapters.

Supported adapters MAY include:

| Adapter | Description |
|----------|-------------|
| In-Memory | Local runtime scheduler |
| Oracle | Oracle scheduler |
| Quartz | Quartz scheduler |
| Kubernetes | Kubernetes scheduler |
| Cloud | Cloud scheduler |
| Plugin | Custom scheduler |

The Scheduler SHALL remain independent from scheduling implementations.

---

# 13. Scheduling Pipeline

Scheduling SHALL follow this sequence.

```text
Execution Metadata

↓

Scheduling Planner

↓

Trigger Resolution

↓

Dependency Resolution

↓

Window Validation

↓

Execution

↓

Execution Result
```

Execution SHALL remain deterministic.

---

# 14. Runtime Metrics

The Scheduler SHALL collect:

- scheduling latency;
- execution latency;
- waiting duration;
- dependency resolution time;
- concurrency utilization;
- deadline compliance;
- execution success rate.

Metrics SHALL support operational optimization.

---

# 15. Diagnostics

The Scheduler SHALL generate diagnostics for:

- missed schedules;
- dependency violations;
- execution conflicts;
- concurrency conflicts;
- deadline violations;
- adapter failures.

Diagnostics SHALL remain traceable.

---

# 16. Constraints

| ID | Constraint |
|----|------------|
| SCH-001 | Scheduler SHALL execute immutable Scheduling Graphs |
| SCH-002 | Trigger evaluation SHALL be compiler-generated |
| SCH-003 | Dependencies SHALL be resolved before execution |
| SCH-004 | Execution SHALL respect windows and priorities |
| SCH-005 | Runtime SHALL NOT modify Scheduling Graphs |

---

# 17. Relationships

```text
Execution Metadata

produces

Scheduling Graph

planned by

Scheduling Planner

resolved by

Trigger Resolver

validated by

Window Manager

executed by

Scheduler Adapter

produces

Execution Result
```

---

# 18. Traceability

```text
Execution Metadata

↓

Scheduling Graph

↓

Scheduled Execution

↓

Execution Result

↓

Audit
```

Every Scheduled Execution SHALL remain traceable to the originating metadata, compiler build, execution plan, trigger, and scheduling decision.

---

# 19. Risks

Potential risks include:

- missed deadlines;
- dependency deadlocks;
- priority inversion;
- scheduler overload;
- execution starvation.

These risks SHALL be mitigated through compiler validation, immutable Scheduling Graphs, deterministic planning, bounded concurrency, deadline enforcement, and runtime diagnostics.

---

# 20. Summary

The Scheduler provides deterministic execution scheduling across the ODAF platform.

By executing immutable compiler-generated Scheduling Graphs through planning, trigger resolution, dependency management, execution windows, priority policies, concurrency management, and scheduler adapters, the Scheduler separates execution intent from scheduling technology.

This architecture enables predictable orchestration, dependency-aware execution, multi-trigger scheduling, and complete traceability while preserving the principles of a Compiler-Driven Metadata Platform.

---

# Scheduler Overview

```text
Execution Metadata
        │
        ▼
Scheduler
        ├── Scheduling Planner
        ├── Trigger Resolver
        ├── Dependency Resolver
        ├── Window Manager
        ├── Priority Manager
        ├── Concurrency Manager
        ├── Scheduler Adapter
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Execution Graph
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| SCH_PLANNER | Scheduling planning |
| SCH_TRIGGER | Trigger resolution |
| SCH_DEPENDENCY | Dependency management |
| SCH_WINDOW | Execution window management |
| SCH_PRIORITY | Priority scheduling |
| SCH_CONCURRENCY | Concurrency management |
| SCH_ADAPTER | Scheduler adapter abstraction |
| SCH_METRICS | Scheduling metrics |
| SCH_DIAGNOSTICS | Scheduler diagnostics |
| SCH_RESULT | Scheduled execution result |

---

# End of Volume 3

This chapter complements the execution infrastructure by defining deterministic scheduling as a compiler-generated capability, fully aligned with the ODAF Compiler-Driven Metadata Platform.