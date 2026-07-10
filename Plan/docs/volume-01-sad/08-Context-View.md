---
document_id: SAD-V1-008
title: Context View
volume: Volume 1 – Software Architecture Document
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - SAD-V1-007
  - SAD-V1-009
---

# Chapter 08
# Context View

---

# 1. Purpose

This chapter defines the architectural context of the Oracle Dynamic Application Framework (ODAF).

The Context View identifies:

- system boundaries;
- external actors;
- external systems;
- primary interactions;
- trust boundaries;
- integration points.

This chapter follows the **C4 Model – Level 1 (System Context)**.

---

# 2. Scope

The Context View describes ODAF as a single logical system.

Internal implementation details are intentionally omitted.

Container-level and component-level architecture are defined in later chapters.

---

# 3. Objectives

The objectives of the Context View are to:

- define the boundary of ODAF;
- identify all external actors;
- identify external systems;
- describe interaction flows;
- establish integration responsibilities;
- define trust boundaries.

---

# 4. System Under Design

The system under design is:

> **Oracle Dynamic Application Framework (ODAF)**

ODAF is an enterprise metadata-driven application platform responsible for designing, deploying, executing, and managing business applications through metadata.

ODAF SHALL be treated as a single logical system within this architectural view.

---

# 5. External Actors

The following actors interact directly with ODAF.

| Actor | Description |
|--------|-------------|
| Business User | Uses generated enterprise applications |
| System Administrator | Manages platform configuration |
| Business Analyst | Defines application metadata |
| Developer | Extends platform capabilities |
| Enterprise Architect | Governs platform architecture |
| DevOps Engineer | Deploys and operates ODAF |
| Auditor | Reviews audit trails and compliance |

---

# 6. External Systems

ODAF interacts with multiple external systems.

| External System | Purpose |
|-----------------|---------|
| Oracle Database | Metadata repository and business data |
| LDAP / Active Directory | Authentication |
| Identity Provider (OIDC/SAML) | Single Sign-On |
| SMTP Server | Email notification |
| SMS Gateway | SMS notification |
| REST Services | External integration |
| SOAP Services | Legacy integration |
| Message Broker | Asynchronous messaging |
| Object Storage | File repository |
| Monitoring Platform | Metrics and health monitoring |
| Source Control (Git) | Version management |
| CI/CD Platform | Automated deployment |

---

# 7. System Context Diagram

```mermaid
flowchart LR

subgraph Users

BU[Business User]

SA[System Administrator]

BA[Business Analyst]

DEV[Developer]

EA[Enterprise Architect]

OPS[DevOps Engineer]

AUD[Auditor]

end

subgraph External Systems

DB[(Oracle Database)]

LDAP[(LDAP / Active Directory)]

OIDC[(Identity Provider)]

SMTP[(SMTP Server)]

MQ[(Message Broker)]

REST[(External REST API)]

SOAP[(SOAP Service)]

STORE[(Object Storage)]

MON[(Monitoring Platform)]

GIT[(Git Repository)]

CICD[(CI/CD Pipeline)]

end

subgraph ODAF

ODAF[Oracle Dynamic Application Framework]

end

BU --> ODAF
SA --> ODAF
BA --> ODAF
DEV --> ODAF
EA --> ODAF
OPS --> ODAF
AUD --> ODAF

ODAF --> DB
ODAF --> LDAP
ODAF --> OIDC
ODAF --> SMTP
ODAF --> MQ
ODAF --> REST
ODAF --> SOAP
ODAF --> STORE
ODAF --> MON
ODAF --> GIT
ODAF --> CICD
```

---

# 8. Trust Boundaries

The platform defines the following trust boundaries.

```text
Internet
    │
    ▼
Reverse Proxy
    │
    ▼
ODAF Runtime
    │
    ▼
Oracle Database
```

Communication across trust boundaries SHALL be authenticated and encrypted whenever practical.

---

# 9. Primary Interaction Flows

The primary interactions supported by ODAF include:

1. User Authentication
2. Metadata Authoring
3. Application Execution
4. Dataset Access
5. Workflow Execution
6. Report Generation
7. Notification Delivery
8. Deployment
9. Monitoring
10. Auditing

---

# 10. Context Responsibilities

Within the architectural context, ODAF is responsible for:

- metadata management;
- application execution;
- workflow orchestration;
- authorization;
- auditing;
- rendering;
- API exposure;
- integration orchestration.

ODAF is **not** responsible for:

- enterprise identity management;
- corporate email infrastructure;
- external ERP systems;
- enterprise message brokers;
- third-party monitoring systems.

---

# 11. Integration Principles

ODAF SHALL integrate with external systems through well-defined interfaces.

Supported integration styles include:

- REST
- SOAP
- JDBC / Oracle Client
- LDAP
- OIDC
- Message Queue
- File Exchange

Direct database coupling with external applications SHOULD be avoided.

---

# 12. Context Constraints

The following constraints apply to the Context View.

| ID | Constraint |
|----|------------|
| CV-001 | ODAF SHALL remain independent of external identity providers. |
| CV-002 | External integrations SHALL occur through published interfaces. |
| CV-003 | Business applications SHALL remain isolated from infrastructure concerns. |
| CV-004 | Oracle Database SHALL remain the authoritative metadata repository. |

---

# 13. Context Assumptions

This architectural view assumes:

- Oracle Database is available.
- Authentication services are operational.
- Network connectivity exists.
- External integrations expose stable interfaces.
- Deployment infrastructure is available.

Changes to these assumptions MAY require architectural review.

---

# 14. Risks

Potential risks include:

- unavailable identity provider;
- Oracle database outage;
- failed external integrations;
- network partition;
- incompatible API versions;
- infrastructure failures.

These risks are addressed through redundancy, monitoring, retry strategies, and operational procedures.

---

# 15. Traceability

The Context View is related to:

- Chapter 04 — Stakeholders
- Chapter 05 — Architecture Drivers
- Chapter 07 — Constraints
- Chapter 09 — Solution Strategy
- Chapter 11 — Runtime View
- Chapter 12 — Deployment View

---

# 16. Summary

The Context View defines the external environment in which ODAF operates.

It establishes the system boundary, identifies all external actors and systems, defines trust boundaries, and specifies the integration context that governs the remainder of the architecture.

Subsequent chapters progressively refine the internal structure of ODAF while preserving the context established in this chapter.

---

# Next Document

➡ **09-Solution-Strategy.md**

The next chapter describes the overall architectural strategy adopted by ODAF, including Metadata-First Architecture, Compiler-Based Runtime, Separation of Concerns, Layered Architecture, and the rationale behind the major architectural decisions.