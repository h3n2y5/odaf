---
document_id: CORE-V3-011
title: Dataset Engine
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-008
  - CORE-V3-010
  - CORE-V3-012
  - DB-V2-014
---

# Chapter 11

# Dataset Engine

---

# 1. Purpose

This chapter defines the Dataset Engine of the Oracle Dynamic Application Framework (ODAF).

The Dataset Engine executes compiler-generated Logical Data Graphs using execution strategies selected during compilation and refined by runtime policies.

A Dataset is a logical representation of business data.

It is **not** tied to SQL, database tables, views, or any specific persistence technology.

---

# 2. Design Objectives

The Dataset Engine SHALL:

- execute logical datasets;
- remain independent from storage technologies;
- support multiple execution strategies;
- execute compiler-generated data graphs;
- support adaptive caching;
- provide deterministic execution;
- expose execution metrics.

---

# 3. Dataset Engine Architecture

```text
Execution Graph
        │
        ▼
Dataset Engine
        │
        ├── Dataset Planner
        ├── Strategy Selector
        ├── Data Graph Executor
        ├── Runtime Cache
        ├── Runtime Adapter
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Data Source
```

The Dataset Engine SHALL execute Logical Data Graphs rather than physical queries.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Dataset Execution
```

A Dataset Execution represents one complete execution of a compiler-generated Logical Data Graph.

---

# 5. Dataset Meta Model

```text
Dataset Execution

│

├── Logical Data Graph

├── Execution Strategy

├── Dataset Planner

├── Runtime Adapter

├── Cache Policy

├── Metrics

├── Diagnostics

└── Execution Result
```

---

# 6. Logical Data Graph

The Dataset Engine SHALL execute Logical Data Graphs.

Example:

```text
Customer

↓

Orders

↓

Order Lines

↓

Inventory

↓

Warehouse
```

Logical Data Graphs SHALL remain immutable.

---

# 7. Dataset Planner

The Dataset Planner SHALL generate execution plans.

Typical planning activities include:

- dependency analysis;
- graph ordering;
- execution batching;
- strategy selection;
- cache evaluation.

The planner SHALL remain deterministic.

---

# 8. Execution Strategies

Supported execution strategies MAY include:

| Strategy | Description |
|----------|-------------|
| Direct Query | Execute directly against the data source |
| Materialized Data | Read from a precomputed representation |
| Runtime Cache | Use cached execution results |
| REST Service | Retrieve data through a service endpoint |
| Graph Execution | Execute a graph traversal |
| Hybrid | Combine multiple strategies |

The compiler SHALL assign a preferred strategy, while runtime MAY choose an equivalent strategy according to policy.

---

# 9. Runtime Adapter

The Dataset Engine SHALL access data through Runtime Adapters.

Possible adapters include:

- Oracle Adapter;
- REST Adapter;
- Graph Adapter;
- File Adapter;
- Message Adapter;
- Plugin Adapter.

The Dataset Engine SHALL remain unaware of implementation details.

---

# 10. Runtime Cache

The Dataset Engine SHALL support compiler-defined cache policies.

Typical policies include:

- No Cache;
- Request Scope;
- Session Scope;
- Shared Cache;
- Distributed Cache.

Each cache policy MAY define:

- expiration;
- invalidation;
- refresh;
- consistency.

---

# 11. Execution Pipeline

Dataset execution SHALL follow this sequence.

```text
Execution Graph

↓

Dataset Planner

↓

Execution Strategy

↓

Runtime Adapter

↓

Execution Result

↓

Cache Update

↓

Metrics
```

Execution SHALL remain deterministic.

---

# 12. Runtime Metrics

The Dataset Engine SHALL collect metrics including:

- execution duration;
- records processed;
- cache hit ratio;
- adapter latency;
- graph complexity;
- execution cost.

Metrics SHALL support runtime optimization and diagnostics.

---

# 13. Diagnostics

The Dataset Engine SHALL produce diagnostics for:

- execution failures;
- cache misses;
- adapter failures;
- strategy fallbacks;
- timeout events.

Diagnostics SHALL be traceable to the originating Dataset Execution.

---

# 14. Constraints

| ID | Constraint |
|----|------------|
| DSE-001 | Dataset Engine SHALL execute Logical Data Graphs only |
| DSE-002 | Runtime Adapters SHALL isolate implementation details |
| DSE-003 | Execution SHALL remain deterministic |
| DSE-004 | Cache policies SHALL be compiler-defined |
| DSE-005 | Logical Data Graphs SHALL remain immutable |

---

# 15. Relationships

```text
Execution Graph

contains

Logical Data Graph

planned by

Dataset Planner

uses

Execution Strategy

executed by

Runtime Adapter

produces

Execution Result

recorded by

Metrics Collector
```

---

# 16. Traceability

```text
Metadata

↓

Logical Dataset

↓

Logical Data Graph

↓

Dataset Execution

↓

Execution Result

↓

Audit
```

Every Dataset Execution SHALL remain traceable to its originating metadata and compiler build.

---

# 17. Risks

Potential risks include:

- inefficient execution strategies;
- excessive graph depth;
- cache inconsistency;
- adapter failures;
- execution bottlenecks.

These risks SHALL be mitigated through compiler planning, deterministic execution strategies, runtime metrics, adaptive caching policies, and runtime diagnostics.

---

# 18. Summary

The Dataset Engine provides a technology-independent mechanism for executing business data access within ODAF.

By representing datasets as immutable Logical Data Graphs, separating planning from execution, and delegating physical access to Runtime Adapters, the Dataset Engine enables compiler-driven optimization while remaining independent of Oracle or any other storage technology.

This architecture supports multiple execution strategies, adaptive caching, and future extensibility without changing business metadata.

---

# Dataset Engine Overview

```text
Execution Graph
        │
        ▼
Dataset Engine
        ├── Dataset Planner
        ├── Strategy Selector
        ├── Logical Data Graph Executor
        ├── Runtime Cache
        ├── Runtime Adapter
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Data Source
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| DSE_PLANNER | Dataset planning |
| DSE_STRATEGY | Strategy selection |
| DSE_GRAPH | Logical Data Graph execution |
| DSE_CACHE | Runtime cache management |
| DSE_ADAPTER | Runtime adapter abstraction |
| DSE_METRICS | Dataset execution metrics |
| DSE_DIAGNOSTICS | Execution diagnostics |
| DSE_RESULT | Result materialization |

---

# Next Document

➡ **12-Workflow-Engine.md**

The next chapter defines the Workflow Engine, including workflow graphs, orchestration, execution scheduling, compensation handling, parallel execution, and compiler-generated workflow plans that coordinate business processes across the ODAF Runtime.