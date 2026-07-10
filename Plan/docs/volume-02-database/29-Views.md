---
document_id: DB-V2-029
title: Views
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-028
  - DB-V2-030
  - DB-V2-014
  - DB-V2-017
---

# Chapter 29

# Views

---

# 1. Purpose

This chapter defines the Logical View Repository of the Oracle Dynamic Application Framework (ODAF).

A View represents a logical data access abstraction over business repositories.

Views SHALL separate business semantics from physical storage and database optimization.

The Runtime Engine SHALL consume logical views rather than directly accessing business tables.

---

# 2. Design Objectives

Views SHALL:

- abstract physical storage;
- support logical data composition;
- enable compiler optimization;
- support reusable data projections;
- support security filtering;
- support analytical workloads;
- remain metadata-driven.

---

# 3. View Architecture

```text
Business Object

↓

Logical View

↓

Security Filter

↓

Optimization

↓

Physical View

↓

Dataset
```

Views SHALL remain independent of consuming applications.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Logical View
```

Each Logical View SHALL belong to exactly one Feature.

---

# 5. View Meta Model

```text
Logical View

│

├── Projection

├── Join

├── Filter

├── Security Policy

├── Optimization

├── Materialization

├── Runtime Mapping

└── Dependencies
```

---

# 6. Logical View

Logical Views define reusable business projections.

Typical attributes include:

| Attribute | Description |
|------------|------------|
| OBJECT_ID | Platform identifier |
| OBJECT_CODE | Business code |
| OBJECT_NAME | View name |
| VERSION_NO | Metadata version |
| STATUS | Lifecycle |
| VIEW_TYPE | Logical classification |

Logical Views SHALL remain independent of Oracle implementation.

---

# 7. Projection

A Projection defines the visible business attributes.

Examples include:

- document header;
- document detail;
- customer profile;
- inventory balance;
- financial summary.

Projection metadata SHALL determine exposed fields.

---

# 8. Joins

Logical Views MAY define joins.

Supported join types include:

- inner;
- left;
- right;
- full;
- cross (restricted).

Join definitions SHALL remain declarative.

---

# 9. Filters

Views MAY define reusable filters.

Examples:

- active records;
- current company;
- current organization;
- effective date;
- published version.

Filters SHALL be reusable.

---

# 10. Security Views

Logical Views MAY reference Security Policies.

Compiler-generated filters MAY enforce:

- row-level security;
- tenant isolation;
- department filtering;
- ownership filtering.

Security SHALL be transparent to consumers.

---

# 11. View Types

Supported logical view types include:

| View Type | Description |
|------------|-------------|
| Canonical | Standard business projection |
| Runtime | Optimized runtime projection |
| Reporting | Reporting projection |
| Analytics | Analytical projection |
| Materialized | Materialized implementation |
| Security | Security-filtered projection |

Additional types MAY be introduced by plugins.

---

# 12. Materialization

The Compiler MAY materialize Logical Views.

Typical criteria include:

- large datasets;
- expensive joins;
- reporting workloads;
- aggregation;
- cache optimization.

Materialization SHALL remain transparent to business metadata.

---

# 13. Optimization

Compiler optimizations MAY include:

- predicate pushdown;
- join elimination;
- projection pruning;
- materialization;
- partition pruning.

Optimization SHALL preserve logical semantics.

---

# 14. Runtime Mapping

Compilation transforms

```text
Logical View

↓

Compiler

↓

Runtime View

↓

Dataset Engine
```

Logical identity SHALL be preserved.

---

# 15. Dependencies

```text
Business Object

↓

Logical View

↓

Dataset

↓

Report

↓

API
```

Dependencies SHALL remain acyclic.

---

# 16. Physical Implementation

Compiler MAY generate:

- Oracle Views;
- Materialized Views;
- Runtime Views;
- Generated SQL;
- Cached Runtime Objects.

Implementation SHALL remain transparent.

---

# 17. Constraints

| ID | Constraint |
|----|------------|
| VIEW-001 | Every Logical View belongs to one Feature |
| VIEW-002 | Security SHALL be evaluated before exposing data |
| VIEW-003 | Logical Views SHALL remain implementation independent |
| VIEW-004 | Materialization SHALL preserve semantics |
| VIEW-005 | Dependencies SHALL remain acyclic |

---

# 18. Relationships

```text
Feature

owns

Logical View

owns

Projection

owns

Filter

references

Dataset

references

Security

references

Report
```

---

# 19. Traceability

```text
Business Object

↓

Logical View

↓

Compiler

↓

Runtime View

↓

Dataset

↓

Runtime
```

Every generated view SHALL be traceable to its originating metadata.

---

# 20. Risks

Potential risks include:

- duplicated views;
- excessive joins;
- stale materialized views;
- inconsistent filters;
- optimization regressions.

These risks SHALL be mitigated through compiler validation, dependency analysis, refresh strategies, and governance.

---

# 21. Summary

The Logical View Repository defines a metadata-driven abstraction layer between business repositories and runtime data access.

By separating logical projections from physical Oracle implementations, ODAF enables compiler-driven optimization, reusable business views, transparent security enforcement, and scalable analytical processing without exposing storage details to consuming services.

---

# Logical View Repository Model

```text
Logical View
        │
        ├── Projection
        ├── Join
        ├── Filter
        ├── Security Policy
        ├── Optimization
        ├── Materialization
        ├── Runtime Mapping
        └── Dependencies
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Object |
|----------------|-----------------------|
| Logical View | VW_LOGICAL_VIEW |
| Projection | VW_PROJECTION |
| Filter | VW_FILTER |
| Join Definition | VW_JOIN |
| Security View | VW_SECURITY |
| Materialized View | MV_RUNTIME_VIEW |
| Runtime View | RT_VIEW |
| View Dependency | VW_DEPENDENCY |

---

# Next Document

➡ **30-Repository-Implementation-Guidelines.md**

The next chapter defines implementation guidelines for Oracle repositories, including coding standards, package organization, compiler generation rules, performance recommendations, migration practices, and operational best practices for enterprise deployments.