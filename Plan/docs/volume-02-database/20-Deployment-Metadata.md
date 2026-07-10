---
document_id: DB-V2-020
title: Deployment Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-019
  - DB-V2-021
  - DB-V2-009
  - DB-V2-010
---

# Chapter 20

# Deployment Metadata

---

# 1. Purpose

This chapter defines the Deployment Repository of the Oracle Dynamic Application Framework (ODAF).

Deployment in ODAF is the controlled promotion of compiled metadata between managed environments.

The platform deploys immutable metadata packages rather than application source code.

Deployment SHALL be deterministic, traceable, repeatable, and reversible.

---

# 2. Design Objectives

Deployment Metadata SHALL:

- deploy immutable metadata packages;
- support controlled environment promotion;
- support zero or low downtime deployment;
- support rollback;
- support release traceability;
- support deployment governance;
- remain metadata-driven.

---

# 3. Deployment Architecture

```text
Metadata

↓

Compiler

↓

Deployment Package

↓

Environment Promotion

↓

Activation

↓

Runtime
```

Deployment SHALL operate on compiled metadata only.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Deployment Package
```

Every Deployment Package SHALL represent one immutable deployment artifact.

---

# 5. Deployment Meta Model

```text
Deployment Package

│

├── Manifest

├── Dependency

├── Environment

├── Promotion Policy

├── Validation Policy

├── Activation Policy

├── Rollback Policy

├── Verification

└── Runtime Mapping
```

---

# 6. Deployment Package

A Deployment Package is the immutable unit promoted between environments.

Typical attributes include:

| Attribute | Description |
|------------|-------------|
| PACKAGE_ID | Unique package identifier |
| PACKAGE_NAME | Package name |
| PACKAGE_VERSION | Package version |
| METADATA_VERSION | Referenced metadata version |
| COMPILER_VERSION | Compiler version |
| STATUS | Lifecycle state |
| CHECKSUM | Package checksum |

Deployment Packages SHALL be immutable.

---

# 7. Package Manifest

Every Deployment Package SHALL contain a Manifest.

Typical manifest information includes:

- package identity;
- metadata versions;
- dependency list;
- required platform version;
- compiler version;
- checksum;
- digital signature (future).

The Manifest SHALL be validated before deployment.

---

# 8. Environment

Supported environments include:

- Development
- Integration
- QA
- UAT
- Staging
- Production

Additional environments MAY be defined through metadata.

---

# 9. Promotion

Packages SHALL be promoted between environments.

```text
Development

↓

Integration

↓

QA

↓

UAT

↓

Staging

↓

Production
```

Promotion SHALL preserve package identity.

---

# 10. Validation

Before promotion the platform SHALL validate:

- metadata integrity;
- dependency graph;
- version compatibility;
- compiler compatibility;
- security policy;
- package signature.

Deployment SHALL fail if validation fails.

---

# 11. Dependency Management

Deployment Packages SHALL declare dependencies.

Examples include:

- platform version;
- metadata version;
- runtime version;
- plugin version;
- integration adapter version.

Dependencies SHALL be resolved before activation.

---

# 12. Activation

Deployment SHALL activate metadata explicitly.

Activation policies include:

- immediate;
- scheduled;
- manual approval;
- blue/green switch.

Activation SHALL generate audit events.

---

# 13. Rollback

Rollback SHALL restore a previously activated deployment package.

Rollback SHALL preserve:

- audit history;
- deployment history;
- package identity.

Rollback SHALL NOT modify immutable deployment artifacts.

---

# 14. Verification

Deployment verification SHALL confirm:

- package integrity;
- runtime compatibility;
- metadata consistency;
- service availability;
- activation success.

Verification SHALL complete before deployment is finalized.

---

# 15. Blue/Green Deployment

The platform SHOULD support Blue/Green deployment.

Conceptually:

```text
Runtime A (Blue)

↓

Runtime B (Green)

↓

Traffic Switch

↓

Deactivate Previous Runtime
```

Blue/Green deployment minimizes service interruption.

---

# 16. Deployment Pipeline

```text
Compile

↓

Validate

↓

Package

↓

Sign

↓

Deploy

↓

Verify

↓

Activate

↓

Audit
```

Every stage SHALL be observable and auditable.

---

# 17. Runtime Mapping

Compilation transforms

```text
DEP_PACKAGE

↓

Compiler

↓

RT_DEPLOYMENT

↓

Deployment Engine
```

Package identity SHALL remain unchanged.

---

# 18. Constraints

| ID | Constraint |
|----|------------|
| DEP-001 | Deployment Packages SHALL be immutable |
| DEP-002 | Every package SHALL contain a Manifest |
| DEP-003 | Validation SHALL precede activation |
| DEP-004 | Dependencies SHALL be satisfied |
| DEP-005 | Rollback SHALL use immutable packages |

---

# 19. Relationships

```text
Deployment Package

owns

Manifest

owns

Environment

owns

Promotion Policy

owns

Rollback Policy

references

Runtime

references

Audit

references

Security
```

---

# 20. Traceability

```text
Metadata

↓

Compiler

↓

Deployment Package

↓

Environment

↓

Activation

↓

Runtime

↓

Audit
```

Every deployment SHALL be traceable.

---

# 21. Risks

Potential risks include:

- invalid packages;
- missing dependencies;
- incompatible runtime versions;
- partial deployment;
- failed rollback;
- environment drift.

These risks SHALL be mitigated through validation, immutable packages, verification, governance, and deployment automation.

---

# 22. Summary

The Deployment Repository defines the metadata-driven deployment architecture of ODAF.

By promoting immutable deployment packages through managed environments, validating dependencies, preserving deployment history, and supporting deterministic rollback and activation policies, ODAF provides a reliable enterprise deployment model independent of implementation technology.

---

# Deployment Repository Model

```text
Deployment Package
        │
        ├── Manifest
        ├── Dependency
        ├── Environment
        ├── Promotion Policy
        ├── Validation Policy
        ├── Activation Policy
        ├── Rollback Policy
        ├── Verification
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Deployment Package | DEP_PACKAGE |
| Package Manifest | DEP_MANIFEST |
| Environment | DEP_ENVIRONMENT |
| Promotion Policy | DEP_PROMOTION |
| Validation Policy | DEP_VALIDATION |
| Rollback Policy | DEP_ROLLBACK |
| Deployment History | DEP_HISTORY |
| Deployment Verification | DEP_VERIFICATION |
| Runtime Deployment | RT_DEPLOYMENT |

---

# Next Document

➡ **21-Runtime-Metadata.md**

The next chapter defines the Runtime Repository, including compiled metadata, runtime object graphs, runtime caches, execution contexts, metadata activation, runtime optimization, and the execution model used by the ODAF Kernel.