---
document_id: DB-V2-017
title: Reporting Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-016
  - DB-V2-018
  - DB-V2-014
  - DB-V2-013
---

# Chapter 17

# Reporting Metadata

---

# 1. Purpose

This chapter defines the Reporting Repository of the Oracle Dynamic Application Framework (ODAF).

A Report represents a metadata-defined analytical presentation of business information.

Reports SHALL remain independent of:

- data providers;
- rendering technologies;
- output formats.

The Reporting Repository enables a single report definition to generate multiple output representations.

---

# 2. Design Objectives

Reporting Metadata SHALL:

- separate analytics from presentation;
- support multiple renderers;
- support reusable templates;
- support scheduling and distribution;
- support interactive dashboards;
- support AI-assisted reporting;
- remain metadata-driven.

---

# 3. Reporting Architecture

```text
Business Capability

↓

Report

↓

Dataset

↓

Transformation

↓

Template

↓

Renderer

↓

Output
```

The Reporting Repository SHALL remain provider-independent.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Report
```

Every Report SHALL belong to exactly one Feature.

---

# 5. Reporting Meta Model

```text
Report

│

├── Dataset

├── Parameter

├── Template

├── Section

├── Band

├── Component

├── Renderer

├── Distribution

├── Schedule

└── Runtime Mapping
```

---

# 6. Report

A Report defines analytical content.

Typical attributes include:

| Attribute | Description |
|------------|------------|
| OBJECT_ID | Platform identifier |
| OBJECT_CODE | Business code |
| OBJECT_NAME | Report name |
| VERSION | Report version |
| STATUS | Lifecycle |
| DEFAULT_RENDERER | Preferred renderer |

---

# 7. Dataset Binding

Reports SHALL consume Dataset Metadata.

Reports SHALL NOT contain SQL.

The Dataset Repository remains responsible for data acquisition.

---

# 8. Parameters

Reports MAY define parameters.

Examples

- Company
- Date Range
- Fiscal Year
- Currency
- Warehouse
- Supplier

Parameters SHALL be resolved before execution.

---

# 9. Template

Templates define report appearance.

Supported logical template types include:

- Tabular
- Dashboard
- Summary
- Invoice
- Label
- Matrix
- Chart
- Pivot

Templates SHALL be reusable.

---

# 10. Sections

Templates SHALL contain Sections.

Examples

- Header
- Detail
- Summary
- Footer
- Appendix

Sections SHALL remain renderer-independent.

---

# 11. Bands

Sections MAY contain Bands.

Examples

- Page Header
- Group Header
- Detail Band
- Totals
- Signature
- Watermark

Bands organize report layout.

---

# 12. Report Components

Reports SHALL use reusable Components.

Examples

- Text
- Table
- Chart
- Image
- Barcode
- QR Code
- KPI Card
- Pivot Grid

Components SHALL reuse the UI Component model where appropriate.

---

# 13. Renderers

ODAF SHALL support multiple renderers.

| Renderer | Output |
|-----------|--------|
| HTML | Browser |
| PDF | Portable Document |
| EXCEL | Spreadsheet |
| CSV | Data Export |
| JSON | API Output |
| XML | Structured Exchange |
| EMAIL | Email Body |
| AI | AI Narrative |
| FUTURE | Plugin Renderer |

Renderers SHALL implement a common rendering contract.

---

# 14. Scheduling

Reports MAY be scheduled.

Supported schedules include:

- On Demand
- Hourly
- Daily
- Weekly
- Monthly
- Cron Expression
- Event Trigger

Scheduling SHALL be metadata-defined.

---

# 15. Distribution

Reports MAY be distributed through:

- Browser
- Email
- Shared Folder
- REST API
- Message Queue
- Notification Service

Distribution SHALL be configurable through metadata.

---

# 16. Aggregation

Reports MAY define analytical operations.

Examples

- Sum
- Average
- Count
- Min
- Max
- Group
- Pivot

Aggregation SHALL remain declarative.

---

# 17. AI Reporting

Reports MAY request AI-generated insights.

Examples include:

- executive summary;
- anomaly detection;
- trend explanation;
- recommendation;
- natural language summary.

AI output SHALL complement, not replace, the underlying analytical data.

---

# 18. Reporting Pipeline

Runtime SHALL execute the following pipeline.

```text
Request

↓

Dataset

↓

Transformation

↓

Aggregation

↓

Template

↓

Renderer

↓

Distribution

↓

Output
```

Each stage SHALL be traceable.

---

# 19. Runtime Mapping

Compilation transforms

```text
RPT_REPORT

↓

Compiler

↓

RT_REPORT

↓

Reporting Engine
```

Logical report identity SHALL be preserved.

---

# 20. Constraints

| ID | Constraint |
|-----|------------|
| RPT-001 | Every Report belongs to one Feature |
| RPT-002 | Reports SHALL consume Datasets |
| RPT-003 | SQL SHALL NOT exist inside Reports |
| RPT-004 | Renderers SHALL implement the rendering contract |
| RPT-005 | Templates SHALL remain reusable |

---

# 21. Relationships

```text
Feature

owns

Report

owns

Template

owns

Section

owns

Band

owns

Component

references

Dataset

references

Security

references

Notification
```

---

# 22. Traceability

```text
Business Capability

↓

Report

↓

Dataset

↓

Renderer

↓

Runtime Report

↓

Business Output
```

---

# 23. Risks

Potential risks include:

- duplicated templates;
- renderer inconsistencies;
- oversized datasets;
- excessive report complexity;
- incompatible plugins.

These risks SHALL be mitigated through compiler validation, reusable templates, renderer conformance testing, and governance.

---

# 24. Summary

The Reporting Repository defines a metadata-driven analytical presentation model for ODAF.

By separating reports into datasets, templates, layouts, renderers, scheduling, and distribution, the platform supports reusable reporting artifacts independent of data providers and output technologies.

This architecture enables interactive dashboards, printable documents, machine-readable exports, and future AI-assisted reporting through a unified metadata model.

---

# Reporting Repository Model

```text
Report
        │
        ├── Dataset
        ├── Parameter
        ├── Template
        ├── Section
        ├── Band
        ├── Component
        ├── Renderer
        ├── Schedule
        ├── Distribution
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Report | RPT_REPORT |
| Report Template | RPT_TEMPLATE |
| Report Section | RPT_SECTION |
| Report Band | RPT_BAND |
| Report Component | RPT_COMPONENT |
| Report Parameter | RPT_PARAMETER |
| Report Renderer | RPT_RENDERER |
| Report Schedule | RPT_SCHEDULE |
| Report Distribution | RPT_DISTRIBUTION |
| Runtime Report | RT_REPORT |

---

# Next Document

➡ **18-Notification-Metadata.md**

The next chapter defines the Notification Repository, including notification definitions, channels, templates, recipients, delivery policies, retry strategies, event subscriptions, and metadata-driven messaging across the ODAF platform.