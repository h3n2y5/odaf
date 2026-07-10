---
document_id: STUDIO-V4-012
title: Report Designer
volume: Volume 4 – ODAF Studio
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-08
last_updated: 2026-07-08

related_documents:
  - STUDIO-V4-008
  - STUDIO-V4-009
  - STUDIO-V4-010
  - STUDIO-V4-011
  - CORE-V3-017
  - CORE-V3-025
---

# Chapter 12

# Report Designer

---

# 1. Purpose

This chapter defines the Report Designer of the Oracle Dynamic Application Framework (ODAF).

The Report Designer is not a report layout editor, document generator, dashboard builder, or business intelligence authoring tool.

Instead, it is a compiler-aware environment for composing Business Insight Metadata that becomes compiler-generated Insight Graphs.

Reports represent business insight and decision support rather than document formatting.

Presentation formats are projections of semantic reporting metadata.

---

# 2. Design Objectives

The Report Designer SHALL:

- model business insight semantically;
- compose compiler-verifiable report metadata;
- support semantic metrics and dimensions;
- support explainable analytics;
- support multiple report projections;
- support AI-assisted insight modeling;
- remain presentation technology independent.

---

# 3. Report Designer Architecture

```text
Business Question
        │
        ▼
Report Designer
        │
        ├── Insight Composer
        ├── Metric Modeler
        ├── Dimension Engine
        ├── Insight Graph Engine
        ├── Narrative Engine
        ├── Projection Engine
        ├── AI Insight Assistant
        ├── Compiler Bridge
        └── Diagnostics Engine
                │
                ▼
Business Insight Metadata
                │
                ▼
Compiler
                │
                ▼
Insight Graph
                │
                ▼
Projection Adapters
                │
                ▼
Dashboard │ PDF │ Excel │ HTML │ Mobile │ API
```

The Report Designer SHALL manipulate Business Insight Metadata rather than report layouts.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Insight Model
```

An Insight Model represents the semantic definition of business information required to answer a business question through metrics, dimensions, trends, narratives, governance, and visualization metadata.

---

# 5. Report Designer Meta Model

```text
Insight Model

│

├── Insight Graph

├── Metric Graph

├── Dimension Graph

├── Trend Graph

├── Forecast Graph

├── Narrative Graph

├── Visualization Graph

├── Projection Catalog

├── Compiler Diagnostics

└── Report History
```

---

# 6. Insight Graph

Every report SHALL be represented as an Insight Graph.

Insight nodes MAY include:

- metric;
- dimension;
- aggregation;
- comparison;
- trend;
- anomaly;
- forecast;
- recommendation;
- narrative.

Insight Graphs SHALL remain compiler-verifiable.

---

# 7. Semantic Metrics

Metrics SHALL represent business meaning rather than calculations.

Metric semantics MAY include:

- business definition;
- aggregation policy;
- unit of measure;
- currency;
- ownership;
- time semantics;
- validation policy;
- governance policy.

Metrics SHALL remain reusable across reports.

---

# 8. Dimensions and Analysis

Dimensions SHALL describe analytical perspectives.

Dimensions MAY include:

- organization;
- geography;
- supplier;
- customer;
- product;
- time;
- business unit;
- project.

Dimensions SHALL remain independent from storage structures.

---

# 9. Narrative Analytics

Reports SHALL support semantic narratives.

Narratives MAY include:

- trend explanation;
- anomaly explanation;
- contributing factors;
- evidence;
- recommendations;
- business interpretation.

Narratives SHALL be generated from metadata and runtime evidence.

---

# 10. Forecasting and Trends

Insight Models MAY include analytical projections.

Supported analyses MAY include:

- trend analysis;
- seasonality;
- forecasting;
- comparative analysis;
- variance analysis;
- exception analysis.

Forecast definitions SHALL remain compiler-governed.

---

# 11. Interactive Projection

The same Insight Model MAY be projected into multiple representations.

Supported projections MAY include:

- dashboards;
- printable reports;
- spreadsheets;
- APIs;
- conversational summaries;
- mobile views.

Projection SHALL preserve semantic insight.

---

# 12. AI-Assisted Insight Modeling

Artificial Intelligence SHALL assist report modeling.

AI MAY support:

- report generation from business questions;
- metric suggestions;
- visualization recommendations;
- narrative generation;
- anomaly detection;
- forecasting recommendations.

AI SHALL generate metadata proposals rather than presentation layouts.

---

# 13. Compiler Integration

Every report modification SHALL invoke continuous compiler validation.

Compiler diagnostics MAY include:

- inconsistent metrics;
- invalid dimensions;
- aggregation conflicts;
- missing lineage;
- security violations;
- unsupported projections.

Compiler validation SHALL remain deterministic.

---

# 14. Insight Lifecycle

Every Insight Model SHALL follow a deterministic lifecycle.

```text
Business Question

↓

Insight Model

↓

Validate

↓

Compile

↓

Insight Graph

↓

Project

↓

Deliver

↓

Observe

↓

Evolve
```

---

# 15. Constraints

| ID | Constraint |
|----|------------|
| RPT-001 | Report Designer SHALL manipulate Business Insight Metadata only |
| RPT-002 | Insight Graphs SHALL originate from compiler-generated metadata |
| RPT-003 | Metrics SHALL remain semantically defined |
| RPT-004 | Layouts SHALL be projections only |
| RPT-005 | Report Designer SHALL remain presentation technology independent |

---

# 16. Relationships

```text
Business Question

interpreted by

Report Designer

composed into

Business Insight Metadata

compiled into

Insight Graph

projected through

Projection Adapters

consumed by

Business Users
```

---

# 17. Traceability

```text
Business Question

↓

Insight Model

↓

Insight Metadata

↓

Insight Graph

↓

Runtime Report

↓

Business Decision

↓

Audit
```

Every report SHALL remain traceable from the original business question through runtime delivery and business decision support.

---

# 18. Risks

Potential risks include:

- ambiguous business questions;
- inconsistent metrics;
- misleading visualizations;
- duplicated analytical logic;
- unsupported projections.

These risks SHALL be mitigated through semantic insight modeling, compiler validation, reusable metrics, AI-assisted guidance, metadata governance, and architectural traceability.

---

# 19. Summary

The Report Designer defines a compiler-aware environment for modeling business insight within ODAF Studio.

Rather than functioning as a report layout designer or dashboard builder, the Report Designer composes Business Insight Metadata that becomes compiler-generated Insight Graphs.

This architecture enables semantic analytics, reusable business metrics, explainable narratives, AI-assisted insight generation, multiple runtime projections, and complete traceability while preserving the principles of a Compiler-Driven Metadata Platform.

---

# Architect Note AN-0108 — Business Insight Composer (BIC)

The ODAF Report Designer formally adopts the **Business Insight Composer (BIC)** architectural model.

```text
Business Question
        │
        ▼
Business Insight Composer
        │
        ├── Insight Graph
        ├── Metric Graph
        ├── Dimension Graph
        ├── Trend Graph
        ├── Forecast Graph
        ├── Narrative Graph
        ├── Visualization Graph
        ├── Projection Graph
        └── Compiler Diagnostics
                │
                ▼
Business Insight Metadata
                │
                ▼
Compiler
                │
                ▼
Insight Graph
                │
                ▼
Projection Adapters
                │
                ▼
Dashboard │ PDF │ Excel │ HTML │ Mobile │ API
```

The **Business Insight Composer (BIC)** establishes that the Report Designer is **not a report authoring tool**, but a compiler-aware environment for modeling business insight. Every report originates from semantic metadata, is compiled into Insight Graphs, and is projected into multiple presentation formats while preserving analytical meaning, governance, explainability, and architectural consistency.