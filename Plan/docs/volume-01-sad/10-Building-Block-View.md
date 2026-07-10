---
document_id: SAD-V1-010
title: Building Block View
volume: Volume 1 – Software Architecture Document
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - SAD-V1-009
  - SAD-V1-011
---

# Chapter 10
# Building Block View

---

# 1. Purpose

This chapter defines the logical decomposition of the Oracle Dynamic Application Framework (ODAF) into architectural building blocks.

A building block represents a cohesive architectural capability that encapsulates a well-defined responsibility.

Each building block SHALL expose published interfaces and SHALL remain independently evolvable.

This chapter defines logical architecture only.

Implementation details are defined in later volumes.

---

# 2. Scope

This chapter defines:

- logical building blocks;
- responsibilities;
- relationships;
- dependencies;
- ownership;
- interfaces;
- architectural boundaries.

---

# 3. Building Block Philosophy

ODAF follows the principle of **high cohesion** and **low coupling**.

Every building block SHALL have:

- one primary responsibility;
- published interfaces;
- explicit dependencies;
- independent lifecycle;
- version compatibility.

Business functionality SHALL emerge through collaboration between building blocks.

---

# 4. Building Block Overview

The ODAF platform consists of the following architectural building blocks.

| ID | Building Block | Responsibility |
|----|----------------|----------------|
| BB-01 | Metadata Repository | Stores metadata |
| BB-02 | Metadata Compiler | Validates and compiles metadata |
| BB-03 | Runtime Engine | Executes compiled metadata |
| BB-04 | Renderer Engine | Generates user interfaces |
| BB-05 | Dataset Engine | Data access |
| BB-06 | Workflow Engine | Workflow execution |
| BB-07 | Security Engine | Authentication and authorization |
| BB-08 | Audit Engine | Audit logging |
| BB-09 | Validation Engine | Business validation |
| BB-10 | Notification Engine | Email, SMS, Push |
| BB-11 | Reporting Engine | Reporting services |
| BB-12 | Integration Engine | External integration |
| BB-13 | Deployment Manager | Metadata deployment |
| BB-14 | Plugin Manager | Platform extensions |
| BB-15 | ODAF Studio | Metadata authoring |

---

# 5. High-Level Building Block Diagram

```mermaid
flowchart TB

Studio

↓

MetadataRepository

↓

Compiler

↓

Runtime

Runtime --> Renderer
Runtime --> Dataset
Runtime --> Workflow
Runtime --> Security
Runtime --> Validation
Runtime --> Audit
Runtime --> Notification
Runtime --> Reporting
Runtime --> Integration
Runtime --> Plugin

Deployment --> Compiler
```

---

# 6. Metadata Repository

## Responsibility

Acts as the authoritative repository for all application metadata.

### Responsibilities

- applications;
- modules;
- pages;
- datasets;
- workflows;
- permissions;
- layouts;
- reports;
- dashboards.

### Dependencies

None.

### Owned By

Database Architecture Team.

---

# 7. Metadata Compiler

## Responsibility

Transforms editable metadata into immutable runtime artifacts.

### Responsibilities

- validation;
- dependency analysis;
- optimization;
- code generation;
- runtime graph generation.

### Depends On

Metadata Repository.

---

# 8. Runtime Engine

## Responsibility

Executes compiled runtime models.

### Responsibilities

- request lifecycle;
- transaction management;
- execution orchestration;
- service coordination.

### Depends On

- Compiler
- Dataset
- Workflow
- Renderer
- Security

---

# 9. Renderer Engine

## Responsibility

Transforms runtime objects into presentation artifacts.

Supported targets MAY include:

- HTML;
- JSON;
- PDF;
- Mobile Views.

Renderer SHALL NOT contain business logic.

---

# 10. Dataset Engine

## Responsibility

Provides canonical access to business data.

Responsibilities include:

- CRUD;
- filtering;
- pagination;
- sorting;
- optimistic locking;
- transaction coordination.

Dataset SHALL encapsulate SQL execution.

---

# 11. Workflow Engine

## Responsibility

Executes business workflows.

Responsibilities include:

- state transitions;
- approvals;
- escalations;
- timers;
- workflow history.

---

# 12. Security Engine

## Responsibility

Centralizes authentication and authorization.

Responsibilities include:

- authentication;
- authorization;
- role evaluation;
- object permission;
- session management.

---

# 13. Audit Engine

## Responsibility

Records business activity.

Responsibilities include:

- data modification history;
- login history;
- permission changes;
- deployment history.

Audit records SHALL be immutable.

---

# 14. Validation Engine

## Responsibility

Evaluates metadata-driven validation rules.

Validation SHALL occur before business execution.

Supported validation categories include:

- field validation;
- record validation;
- dataset validation;
- workflow validation.

---

# 15. Notification Engine

## Responsibility

Delivers platform notifications.

Supported channels include:

- Email;
- SMS;
- Push Notification;
- Webhook.

---

# 16. Reporting Engine

## Responsibility

Produces business reports.

Supported formats MAY include:

- PDF;
- Excel;
- CSV;
- JSON.

---

# 17. Integration Engine

## Responsibility

Coordinates communication with external systems.

Supported mechanisms include:

- REST;
- SOAP;
- Message Queue;
- File Exchange;
- Database Gateway.

---

# 18. Deployment Manager

## Responsibility

Deploys compiled metadata into runtime environments.

Responsibilities include:

- packaging;
- validation;
- versioning;
- rollback;
- activation.

---

# 19. Plugin Manager

## Responsibility

Loads and manages platform extensions.

Plugins SHALL communicate only through published extension points.

Core components SHALL remain independent of plugins.

---

# 20. ODAF Studio

## Responsibility

Provides the primary development environment.

Capabilities include:

- metadata designer;
- workflow designer;
- permission designer;
- deployment manager;
- metadata versioning.

ODAF Studio SHALL remain independent from Runtime.

---

# 21. Dependency Rules

Building blocks SHALL communicate only through published interfaces.

Circular dependencies SHALL NOT exist.

Business modules SHALL depend on services rather than implementations.

---

# 22. Building Block Ownership

| Building Block | Owner |
|----------------|-------|
| Metadata Repository | Database Architect |
| Compiler | Compiler Team |
| Runtime | Runtime Team |
| Renderer | UI Team |
| Dataset | Backend Team |
| Workflow | Workflow Team |
| Security | Security Team |
| Audit | Platform Team |
| Validation | Platform Team |
| Notification | Integration Team |
| Reporting | Reporting Team |
| Integration | Integration Team |
| Deployment | DevOps Team |
| Plugin | Platform Team |
| Studio | Studio Team |

---

# 23. Traceability

Every implementation artifact SHALL belong to one and only one building block.

Example:

```text
APP_PAGE
      │
      ▼
Metadata Repository
      │
      ▼
Metadata Compiler
      │
      ▼
Runtime Engine
      │
      ▼
Renderer
```

This traceability SHALL be maintained across all implementation layers.

---

# 24. Risks

Improper decomposition may result in:

- tight coupling;
- duplicated functionality;
- unclear ownership;
- reduced maintainability;
- difficult testing.

The Architecture Board SHALL review any proposed changes to the building block structure.

---

# 25. Summary

The Building Block View defines the logical decomposition of ODAF into cohesive architectural capabilities.

Each building block has a clearly defined responsibility, explicit dependencies, and published interfaces, providing the structural foundation for the Runtime View, Deployment View, and Core Implementation described in subsequent volumes.

---

# Next Document

➡ **11-Runtime-View.md**

The next chapter defines the runtime execution model of ODAF, including request processing, execution lifecycle, orchestration, component interaction, and runtime responsibilities.