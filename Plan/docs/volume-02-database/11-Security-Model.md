---
document_id: DB-V2-011
title: Security Model
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-010
  - DB-V2-012
  - SAD-V1-006
  - SAD-V1-014
---

# Chapter 11

# Security Model

---

# 1. Purpose

This chapter defines the security architecture of the Oracle Dynamic Application Framework (ODAF).

Security protects the complete platform, including metadata management, compilation, deployment, runtime execution, administration, and governance.

Security is therefore considered a core architectural capability rather than an application feature.

---

# 2. Security Objectives

The security architecture SHALL provide:

- confidentiality;
- integrity;
- availability;
- accountability;
- traceability;
- least privilege;
- separation of duties;
- defense in depth.

---

# 3. Security Principles

The following principles govern all platform components.

| ID | Principle |
|----|-----------|
| SEC-001 | Secure by Default |
| SEC-002 | Least Privilege |
| SEC-003 | Deny by Default |
| SEC-004 | Separation of Duties |
| SEC-005 | Defense in Depth |
| SEC-006 | Complete Auditability |
| SEC-007 | Identity Before Authorization |
| SEC-008 | Metadata-Driven Security |

---

# 4. Multi-Layer Security Model

The ODAF platform applies security through multiple layers.

```text
Identity

↓

Authentication

↓

Authorization

↓

Metadata Security

↓

Compiler Security

↓

Deployment Security

↓

Runtime Security

↓

Audit Security
```

Each layer SHALL operate independently while contributing to overall platform security.

---

# 5. Identity

Every actor interacting with ODAF SHALL possess a unique identity.

Supported actor categories include:

- human user;
- service account;
- runtime process;
- compiler process;
- deployment service;
- scheduled task.

Every identity SHALL possess a globally unique identifier.

---

# 6. Authentication

Authentication verifies identity.

Supported authentication mechanisms MAY include:

- username and password;
- enterprise LDAP;
- OAuth 2.0;
- OpenID Connect;
- SAML 2.0;
- client certificates;
- API tokens.

Authentication SHALL occur before any protected operation.

---

# 7. Authorization

Authorization determines which operations an authenticated identity may perform.

ODAF adopts Role-Based Access Control (RBAC).

Permissions SHALL be assigned through metadata.

Authorization SHALL evaluate:

- user;
- role;
- permission;
- context;
- target object.

---

# 8. Security Domains

Platform security protects the following domains.

| Domain | Protected Assets |
|----------|------------------|
| Metadata | Definitions and configuration |
| Studio | Metadata editing |
| Compiler | Compilation process |
| Deployment | Release management |
| Runtime | Application execution |
| API | Service endpoints |
| Plugin | Extensions |
| Audit | Audit records |

Each domain SHALL define its own security policies.

---

# 9. Metadata Security

Metadata SHALL be protected according to its lifecycle.

Supported operations include:

- create;
- read;
- update;
- delete;
- approve;
- compile;
- deploy;
- archive.

Permissions SHALL be evaluated independently for each operation.

---

# 10. Role-Based Access Control (RBAC)

RBAC SHALL define permissions through metadata.

Conceptual model:

```text
User

↓

Role Assignment

↓

Role

↓

Permission

↓

Protected Object
```

A user MAY possess multiple roles.

A role MAY grant multiple permissions.

---

# 11. Permission Model

Permissions SHALL be fine-grained.

Examples include:

- APP.APPLICATION.CREATE
- APP.APPLICATION.UPDATE
- UI.PAGE.READ
- DS.DATASET.EXECUTE
- WF.WORKFLOW.APPROVE
- DEP.DEPLOYMENT.EXECUTE

Permission identifiers SHALL follow the Canonical Object Name convention.

---

# 12. Metadata Object Security

Every Aggregate Root SHALL support object-level authorization.

Examples:

- page visibility;
- field editability;
- dataset execution;
- workflow approval;
- report generation.

Security SHALL be evaluated before execution.

---

# 13. Row-Level Security

Repositories MAY implement row-level authorization.

Typical filters include:

- organization;
- company;
- site;
- department;
- project;
- owner.

Row-level restrictions SHALL be metadata-driven.

---

# 14. Compiler Security

Compilation SHALL require explicit authorization.

Compiler permissions include:

- compile metadata;
- validate metadata;
- publish runtime artifacts;
- execute optimization.

Unauthorized compilation SHALL be rejected.

---

# 15. Deployment Security

Deployment SHALL require deployment-specific authorization.

Deployment permissions include:

- deploy package;
- rollback deployment;
- activate release;
- deactivate release.

Production deployment SHOULD require approval workflows.

---

# 16. Runtime Security

Runtime SHALL enforce:

- authentication;
- authorization;
- session validation;
- permission evaluation;
- data access policies.

Runtime SHALL NOT bypass metadata-defined security rules.

---

# 17. API Security

Every API endpoint SHALL require explicit security policies.

Supported controls include:

- authentication;
- authorization;
- rate limiting;
- API tokens;
- client validation;
- request logging.

API security SHALL remain independent from presentation technology.

---

# 18. Plugin Security

Plugins SHALL execute within controlled boundaries.

Plugins SHALL:

- declare required permissions;
- access published interfaces only;
- avoid direct repository modification.

Plugin permissions SHALL be validated during installation.

---

# 19. Security Audit

All security-relevant operations SHALL generate immutable audit events.

Examples include:

- authentication;
- authorization failures;
- role assignment;
- permission changes;
- deployment approval;
- administrative actions.

Security audit SHALL integrate with the Audit Strategy defined in Chapter 10.

---

# 20. Security Lifecycle

Security configuration SHALL follow the standard metadata lifecycle.

```text
Draft

↓

Review

↓

Approved

↓

Compiled

↓

Deployed

↓

Active

↓

Deprecated

↓

Archived
```

Only approved security metadata SHALL become active.

---

# 21. Security Validation

Before deployment, the platform SHALL validate:

- orphaned permissions;
- invalid role assignments;
- missing approvals;
- inconsistent policies;
- unresolved dependencies.

Validation failures SHALL block deployment.

---

# 22. Security Traceability

Security SHALL participate in architecture traceability.

```text
Business Requirement

↓

Security Policy

↓

Role

↓

Permission

↓

Metadata Object

↓

Runtime

↓

Audit Event
```

Every authorization decision SHALL be traceable.

---

# 23. Risks

Potential security risks include:

- excessive privileges;
- orphaned permissions;
- privilege escalation;
- unauthorized deployment;
- insecure plugins;
- compromised service accounts.

These risks SHALL be mitigated through RBAC, metadata validation, governance, and continuous auditing.

---

# 24. Summary

The ODAF Security Model establishes a metadata-driven, multi-layer security architecture protecting every stage of the platform lifecycle.

Rather than securing only generated applications, ODAF secures the metadata repository, Studio, compiler, deployment pipeline, runtime, APIs, plugins, and audit infrastructure.

By combining identity management, RBAC, fine-grained permissions, metadata-defined policies, and immutable audit events, ODAF provides a secure foundation suitable for enterprise-scale application development and operation.

---

# Security Model Overview

```text
Identity
        │
        ▼
Authentication
        │
        ▼
Authorization (RBAC)
        │
        ▼
Metadata Security
        │
        ▼
Compiler Security
        │
        ▼
Deployment Security
        │
        ▼
Runtime Security
        │
        ▼
Audit Security
```

---

# Security Artifact Catalog

| Artifact | Planned Oracle Prefix |
|-----------|-----------------------|
| Users | SEC_USER |
| Roles | SEC_ROLE |
| Permissions | SEC_PERMISSION |
| Role Assignments | SEC_USER_ROLE |
| Permission Assignments | SEC_ROLE_PERMISSION |
| Security Policies | SEC_POLICY |
| Sessions | SEC_SESSION |
| API Clients | SEC_API_CLIENT |
| Service Accounts | SEC_SERVICE_ACCOUNT |
| Audit Security Events | AUD_SECURITY |

---

# Next Document

➡ **12-Application-Metadata.md**

The next chapter begins the specification of the Application Repository, defining the metadata model for applications, modules, navigation, menus, and page organization that forms the top-level structure of every ODAF application.