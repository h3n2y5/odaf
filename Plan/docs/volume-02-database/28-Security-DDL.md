---
document_id: DB-V2-028
title: Security DDL
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-027
  - DB-V2-029
  - DB-V2-011
  - DB-V2-016
  - DB-V2-022
---

# Chapter 28

# Security DDL

---

# 1. Purpose

This chapter defines the physical Oracle implementation standards for the ODAF Security Repository.

The Security Repository provides a metadata-driven security architecture that supports authentication, authorization, policy evaluation, contextual access control, and data protection across the entire platform.

Security SHALL be centralized, declarative, auditable, and technology independent.

---

# 2. Design Objectives

Security DDL SHALL:

- centralize security metadata;
- support enterprise identity management;
- support policy-based authorization;
- support contextual access control;
- support row-level security;
- support field-level protection;
- remain compiler-generated.

---

# 3. Security Architecture

```text
Identity

↓

Authentication

↓

Security Context

↓

Policy Engine

↓

Permission Evaluation

↓

Dataset Filter

↓

Audit
```

Every platform service SHALL delegate authorization to the Security Repository.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Security Policy
```

Security Policies SHALL be reusable across repositories.

---

# 5. Security Meta Model

```text
Security Policy

│

├── Identity

├── Role

├── Permission

├── Policy Rule

├── Context

├── Resource

├── Action

├── Row Filter

├── Field Mask

└── Runtime Mapping
```

---

# 6. Identity

Identity represents an authenticated principal.

Examples include:

- User
- Service Account
- API Client
- Integration Account
- AI Agent

Identity SHALL have a globally unique identifier.

---

# 7. Roles

Roles group security responsibilities.

Examples:

- Administrator
- Purchasing Manager
- Finance Officer
- Warehouse Operator
- External Partner

Roles SHALL remain independent from permissions.

---

# 8. Resources

Resources represent protected platform objects.

Examples include:

- Application
- View
- Component
- Dataset
- Workflow
- Report
- API
- Business Object

Resources SHALL be identified by immutable identifiers.

---

# 9. Actions

Actions define permitted operations.

Examples include:

- Create
- Read
- Update
- Delete
- Execute
- Approve
- Release
- Cancel
- Export
- Print

Additional actions MAY be defined by metadata.

---

# 10. Permissions

Permissions authorize Actions against Resources.

Permission SHALL be expressed through policies rather than direct role mappings whenever possible.

---

# 11. Policy Rules

Policy Rules SHALL define declarative authorization logic.

Examples include:

- Department = Purchasing
- Company = CURRENT_COMPANY
- Amount < Approval Limit
- Business Unit = User Business Unit

Policies SHALL integrate with the Unified Rule Engine.

---

# 12. Security Context

Security Context SHALL include:

- User
- Roles
- Organization
- Company
- Business Unit
- Department
- Language
- Time Zone
- Device
- Session
- Authentication Method
- Risk Score

Context SHALL be resolved before policy evaluation.

---

# 13. Row-Level Security

Datasets MAY define row-level filters.

Examples:

```text
Company = CURRENT_COMPANY

Department = CURRENT_DEPARTMENT

Owner = CURRENT_USER
```

The compiler SHALL generate runtime filters from metadata.

---

# 14. Field-Level Security

Sensitive fields MAY define masking or visibility rules.

Examples:

- Salary
- National Identifier
- Bank Account
- Cost Price
- Personal Information

Field protection SHALL support:

- hidden;
- masked;
- read-only;
- encrypted.

---

# 15. Authentication Integration

Authentication MAY be delegated to external identity providers.

Supported mechanisms include:

- Local Authentication
- LDAP
- Active Directory
- OAuth2
- OpenID Connect
- SAML
- Client Certificate
- API Key

Authentication SHALL remain independent of authorization.

---

# 16. Runtime Mapping

Compilation transforms

```text
SEC_POLICY

↓

Compiler

↓

RT_SECURITY

↓

Security Engine
```

Security identity SHALL be preserved.

---

# 17. Constraints

| ID | Constraint |
|----|------------|
| SEC-001 | Every protected Resource SHALL reference at least one Security Policy |
| SEC-002 | Policies SHALL be evaluated before data access |
| SEC-003 | Authentication SHALL precede authorization |
| SEC-004 | Security Context SHALL be immutable during request execution |
| SEC-005 | Field masking SHALL be metadata-defined |

---

# 18. Relationships

```text
Security Policy

owns

Policy Rule

owns

Permission

owns

Context

references

Dataset

references

Workflow

references

Report

references

Integration

references

Audit
```

---

# 19. Traceability

```text
Identity

↓

Security Policy

↓

Permission

↓

Runtime Evaluation

↓

Audit Event
```

Every authorization decision SHALL be traceable.

---

# 20. Risks

Potential risks include:

- privilege escalation;
- policy conflicts;
- context inconsistencies;
- missing row filters;
- excessive permissions;
- authentication failures.

These risks SHALL be mitigated through centralized policies, governance validation, compiler verification, continuous auditing, and least-privilege principles.

---

# 21. Summary

The Security Repository defines the metadata-driven security architecture of ODAF.

By separating identities, policies, permissions, resources, actions, contexts, row-level filters, and field-level protection into reusable metadata, ODAF provides enterprise-grade security independent of implementation technologies.

This architecture enables fine-grained authorization, contextual policy evaluation, centralized governance, and secure runtime execution across every platform service.

---

# Security Repository Model

```text
Security Policy
        │
        ├── Identity
        ├── Role
        ├── Permission
        ├── Policy Rule
        ├── Context
        ├── Resource
        ├── Action
        ├── Row Filter
        ├── Field Mask
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Security Policy | SEC_POLICY |
| Identity | SEC_IDENTITY |
| Role | SEC_ROLE |
| Permission | SEC_PERMISSION |
| Policy Rule | SEC_POLICY_RULE |
| Resource | SEC_RESOURCE |
| Action | SEC_ACTION |
| Security Context | SEC_CONTEXT |
| Row Filter | SEC_ROW_FILTER |
| Field Mask | SEC_FIELD_MASK |
| Runtime Security | RT_SECURITY |

---

# Next Document

➡ **29-Audit-DDL.md**

The next chapter defines the Audit Repository, including immutable audit events, security logging, execution tracing, compliance records, digital evidence, retention policies, and append-only storage for enterprise governance.