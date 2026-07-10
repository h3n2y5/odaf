---
document_id: DB-V2-014
title: Dataset Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-013
  - DB-V2-015
  - DB-V2-005
---

# Chapter 14

# Dataset Metadata

---

# 1. Purpose

This chapter defines the Dataset Repository of the Oracle Dynamic Application Framework (ODAF).

A Dataset represents a logical source of business information.

Datasets SHALL describe **what data is required**, rather than **how data is retrieved**.

The implementation technology used to retrieve the data is delegated to a Dataset Provider.

---

# 2. Design Objectives

Dataset Metadata SHALL:

- separate business data from implementation;
- support multiple data providers;
- enable metadata-driven queries;
- support transformation and caching;
- enforce security policies;
- provide deterministic runtime execution.

---

# 3. Dataset Architecture

```text
Business Capability
        │
        ▼
Dataset
        │
        ▼
Schema
        │
        ▼
Provider
        │
        ▼
Transformation
        │
        ▼
Cache
        │
        ▼
Runtime Result
```

Datasets SHALL remain independent of rendering technology.

---

# 4. Aggregate Root

The Aggregate Root of the Dataset Repository is:

```text
Dataset
```

Every Dataset SHALL belong to exactly one Feature.

---

# 5. Dataset Meta Model

A Dataset consists of:

```text
Dataset
        │
        ├── Schema
        ├── Provider
        ├── Parameter
        ├── Filter
        ├── Sort
        ├── Transformation
        ├── Cache Policy
        ├── Security Policy
        └── Runtime Mapping
```

---

# 6. Dataset

A Dataset defines a logical collection of business data.

Typical attributes include:

| Attribute | Description |
|-----------|-------------|
| OBJECT_ID | Unique identifier |
| OBJECT_CODE | Business code |
| OBJECT_NAME | Display name |
| DESCRIPTION | Functional description |
| PROVIDER_TYPE | Dataset provider |
| STATUS | Lifecycle state |

A Dataset SHALL NOT expose implementation-specific details to consumers.

---

# 7. Dataset Schema

The Dataset Schema defines the logical structure of the returned data.

Schema metadata includes:

- columns;
- data types;
- nullability;
- primary identifier;
- relationships;
- calculated fields.

Consumers SHALL depend on the schema, not on provider-specific formats.

---

# 8. Dataset Providers

ODAF supports multiple provider types.

| Provider | Description |
|----------|-------------|
| ORACLE_SQL | Oracle SQL query |
| PLSQL | Stored procedure or function |
| REST | REST API |
| GRAPHQL | GraphQL endpoint |
| JSON | JSON document |
| CSV | CSV file |
| EXCEL | Excel workbook |
| XML | XML source |
| MEMORY | In-memory provider |
| AI | AI Knowledge Provider |

Additional providers MAY be introduced through plugins.

---

# 9. Parameters

Datasets MAY define parameters.

Examples include:

- company;
- site;
- document number;
- date range;
- language;
- currency.

Parameter resolution SHALL occur before provider execution.

---

# 10. Filters

Filters define logical restrictions applied to a Dataset.

Examples include:

- status;
- organization;
- effective date;
- ownership.

Filters SHALL remain declarative.

---

# 11. Sorting

Datasets MAY define default sorting rules.

Sorting metadata includes:

- column;
- direction;
- priority.

Runtime MAY allow user-defined sorting where permitted.

---

# 12. Transformations

Transformations modify provider results without changing the underlying data source.

Examples include:

- calculated columns;
- formatting;
- aggregation;
- grouping;
- projection;
- lookup enrichment.

Transformations SHALL be metadata-driven.

---

# 13. Cache Policy

Datasets MAY define caching behavior.

Supported policies include:

- no cache;
- session cache;
- application cache;
- distributed cache;
- scheduled refresh.

Cache invalidation SHALL be deterministic.

---

# 14. Security Policy

Datasets SHALL reference metadata-driven security policies.

Security MAY enforce:

- execution rights;
- row-level filtering;
- column masking;
- tenant isolation.

Security SHALL be evaluated before provider execution.

---

# 15. Dataset Pipeline

Runtime SHALL process datasets through the following pipeline.

```text
Request

↓

Parameter Resolver

↓

Security Evaluation

↓

Provider Execution

↓

Transformation

↓

Cache Update

↓

Runtime Result
```

Every stage SHALL be traceable.

---

# 16. Runtime Mapping

During compilation:

```text
DS_DATASET

↓

Compiler

↓

RT_DATASET

↓

Runtime Engine
```

Logical identity SHALL be preserved.

---

# 17. Relationships

```text
Feature

owns

Dataset

owns

Schema

owns

Parameter

owns

Transformation

references

Security Policy

references

Provider
```

Relationships SHALL remain acyclic.

---

# 18. Constraints

The following constraints apply.

| ID | Constraint |
|----|------------|
| DS-001 | Every Dataset belongs to one Feature. |
| DS-002 | Every Dataset defines one Schema. |
| DS-003 | Every Dataset uses one Provider. |
| DS-004 | Dataset consumers SHALL depend on the logical schema only. |
| DS-005 | Security SHALL be evaluated before provider execution. |

---

# 19. Traceability

```text
Business Capability

↓

Feature

↓

Dataset

↓

Provider

↓

Runtime Dataset

↓

Business Result
```

---

# 20. Risks

Potential risks include:

- provider-specific coupling;
- schema drift;
- inconsistent transformations;
- stale caches;
- unauthorized access;
- excessive query complexity.

These risks SHALL be mitigated through validation, compiler analysis, caching policies, and governance.

---

# 21. Summary

The Dataset Repository defines a provider-independent model for business data access within ODAF.

By separating logical datasets from provider implementations, the platform enables Oracle SQL, REST services, GraphQL endpoints, files, AI knowledge sources, and future providers to participate in a unified metadata architecture.

Datasets expose consistent schemas, declarative transformations, metadata-driven security, and deterministic execution pipelines, ensuring portability, maintainability, and long-term extensibility.

---

# Dataset Repository Model

```text
Dataset
        │
        ├── Schema
        ├── Provider
        ├── Parameter
        ├── Filter
        ├── Sort
        ├── Transformation
        ├── Cache Policy
        ├── Security Policy
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Dataset | DS_DATASET |
| Dataset Schema | DS_SCHEMA |
| Dataset Column | DS_COLUMN |
| Dataset Provider | DS_PROVIDER |
| Dataset Parameter | DS_PARAMETER |
| Dataset Filter | DS_FILTER |
| Dataset Sort | DS_SORT |
| Dataset Transformation | DS_TRANSFORMATION |
| Dataset Cache Policy | DS_CACHE_POLICY |
| Dataset Security | DS_SECURITY |

---

# Next Document

➡ **15-Workflow-Metadata.md**

The next chapter defines the Workflow Repository, including workflow definitions, states, transitions, actions, approvals, timers, escalations, execution policies, and the metadata model used to orchestrate business processes throughout the ODAF platform.