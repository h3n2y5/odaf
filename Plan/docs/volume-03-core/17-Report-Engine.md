---
document_id: CORE-V3-017
title: Report Engine
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
  - CORE-V3-014
  - CORE-V3-016
  - CORE-V3-018
  - DB-V2-017
---

# Chapter 17

# Report Engine

---

# 1. Purpose

This chapter defines the Report Engine of the Oracle Dynamic Application Framework (ODAF).

The Report Engine executes compiler-generated Report Graphs that compose, calculate, format, visualize, and deliver business reports.

Rather than generating reports directly from SQL or templates, the Runtime Kernel executes immutable Rendering Plans generated during compilation.

The Report Engine is responsible for report composition, aggregation, grouping, visualization, scheduling, rendering, and export orchestration.

---

# 2. Design Objectives

The Report Engine SHALL:

- execute Report Graphs;
- support deterministic report generation;
- support multiple visualization types;
- support multiple output formats;
- support scheduled and event-driven execution;
- remain implementation independent;
- expose runtime metrics.

---

# 3. Report Engine Architecture

```text
Business Request
        │
        ▼
Report Engine
        │
        ├── Report Planner
        ├── Composition Pipeline
        ├── Aggregation Engine
        ├── Visualization Engine
        ├── Layout Engine
        ├── Renderer Adapter
        ├── Scheduler
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Rendered Output
```

The Report Engine SHALL execute compiler-generated Report Graphs only.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Report Execution
```

A Report Execution represents one execution instance of a compiled Report Graph.

---

# 5. Report Meta Model

```text
Report Execution

│

├── Report Graph

├── Rendering Plan

├── Composition Pipeline

├── Visualization Graph

├── Layout Graph

├── Output Format

├── Schedule Policy

├── Renderer Adapter

├── Metrics

├── Diagnostics

└── Report Result
```

---

# 6. Report Graph

The compiler SHALL generate immutable Report Graphs.

Typical node types include:

- Dataset;
- Join;
- Filter;
- Aggregation;
- Grouping;
- Calculation;
- Visualization;
- Layout;
- Export;
- Delivery.

Report Graphs SHALL remain immutable during execution.

---

# 7. Report Planner

The Report Planner SHALL generate a Rendering Plan.

Planning activities MAY include:

- execution ordering;
- dataset optimization;
- aggregation planning;
- visualization planning;
- layout optimization;
- export planning.

Rendering Plans SHALL remain deterministic.

---

# 8. Composition Pipeline

The Composition Pipeline SHALL support:

- dataset composition;
- joins;
- filtering;
- grouping;
- aggregation;
- calculations;
- formatting.

Pipeline stages SHALL execute in compiler-defined order.

---

# 9. Visualization Engine

Visualization SHALL be represented as graph nodes.

Supported visualization types MAY include:

| Visualization | Description |
|--------------|-------------|
| Table | Tabular report |
| Pivot | Pivot analysis |
| Matrix | Matrix report |
| Chart | Graphical chart |
| KPI | Key Performance Indicator |
| Dashboard | Composite dashboard |
| Custom | Plugin visualization |

Visualization SHALL remain independent from rendering technology.

---

# 10. Layout Engine

The Layout Engine SHALL support:

- printable layout;
- responsive layout;
- dashboard layout;
- multi-column layout;
- page layout;
- nested layout.

Layouts SHALL be compiler-generated.

---

# 11. Renderer Adapters

Report output SHALL occur through Renderer Adapters.

Supported adapters MAY include:

| Output | Description |
|---------|-------------|
| PDF | Portable document |
| Excel | Spreadsheet |
| CSV | Comma-separated values |
| HTML | Browser rendering |
| JSON | Structured data |
| Printer | Direct printing |
| Plugin | Custom renderer |

The Report Engine SHALL remain independent from output technologies.

---

# 12. Scheduling

Reports MAY execute:

- on demand;
- scheduled;
- event-driven;
- workflow-driven;
- notification-triggered.

Scheduling SHALL follow compiler-generated policies.

---

# 13. Execution Pipeline

Report execution SHALL follow this sequence.

```text
Report Request

↓

Report Planner

↓

Composition Pipeline

↓

Visualization Engine

↓

Layout Engine

↓

Renderer Adapter

↓

Report Result

↓

Metrics
```

Execution SHALL remain deterministic.

---

# 14. Runtime Metrics

The Report Engine SHALL collect:

- execution duration;
- dataset processing time;
- aggregation cost;
- rendering duration;
- output size;
- visualization complexity.

Metrics SHALL support optimization and operational monitoring.

---

# 15. Diagnostics

The Report Engine SHALL generate diagnostics for:

- rendering failures;
- dataset failures;
- visualization errors;
- export failures;
- layout conflicts;
- scheduling failures.

Diagnostics SHALL remain traceable.

---

# 16. Constraints

| ID | Constraint |
|----|------------|
| RPT-001 | Report Engine SHALL execute immutable Report Graphs |
| RPT-002 | Visualization SHALL remain renderer independent |
| RPT-003 | Renderer access SHALL occur only through Renderer Adapters |
| RPT-004 | Scheduling SHALL follow compiler-generated policies |
| RPT-005 | Execution SHALL remain deterministic |

---

# 17. Relationships

```text
Business Request

triggers

Report Graph

planned by

Report Planner

executed through

Composition Pipeline

rendered by

Renderer Adapter

produces

Report Result

recorded by

Metrics Collector
```

---

# 18. Traceability

```text
Report Metadata

↓

Report Graph

↓

Report Execution

↓

Report Result

↓

Audit
```

Every Report Execution SHALL remain traceable to the originating metadata, compiler build, and triggering business request.

---

# 19. Risks

Potential risks include:

- excessive report complexity;
- visualization incompatibilities;
- rendering failures;
- scheduling conflicts;
- oversized outputs.

These risks SHALL be mitigated through compiler validation, deterministic rendering plans, immutable Report Graphs, renderer abstraction, runtime diagnostics, and operational monitoring.

---

# 20. Summary

The Report Engine provides deterministic, technology-independent reporting capabilities within ODAF.

By executing immutable compiler-generated Report Graphs through dedicated planning, composition pipelines, visualization engines, layout engines, renderer adapters, and scheduling policies, the Report Engine separates reporting intent from rendering technologies.

This architecture enables scalable reporting, multi-format export, reusable report definitions, deterministic execution, and compiler-driven optimization across the ODAF platform.

---

# Report Engine Overview

```text
Business Request
        │
        ▼
Report Engine
        ├── Report Planner
        ├── Composition Pipeline
        ├── Aggregation Engine
        ├── Visualization Engine
        ├── Layout Engine
        ├── Renderer Adapter
        ├── Scheduler
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
PDF / Excel / CSV / HTML / JSON / Printer / Plugin
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| RPT_PLANNER | Report planning |
| RPT_COMPOSITION | Data composition pipeline |
| RPT_AGGREGATION | Aggregation execution |
| RPT_VISUALIZATION | Visualization graph execution |
| RPT_LAYOUT | Layout execution |
| RPT_RENDERER | Renderer adapter abstraction |
| RPT_SCHEDULER | Scheduled and event-driven execution |
| RPT_METRICS | Reporting metrics |
| RPT_DIAGNOSTICS | Reporting diagnostics |
| RPT_RESULT | Report result management |

---

# Next Document

➡ **18-Security-Engine.md**

The next chapter defines the Security Engine, including authentication, authorization, policy graphs, access evaluation, security enforcement, cryptographic services, audit integration, and compiler-generated security policies that provide deterministic platform-wide protection.