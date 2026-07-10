---
document_id: CORE-V3-010
title: Execution Context
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-008
  - CORE-V3-009
  - CORE-V3-011
---

# Chapter 10

# Execution Context

---

# 1. Purpose

This chapter defines the Execution Context of the Oracle Dynamic Application Framework (ODAF).

The Execution Context is the immutable runtime state associated with a single execution session.

It provides all runtime engines with a consistent view of identity, security, tenant, transaction, localization, execution policies, compiler metadata, and execution lifecycle.

The Execution Context is created at the beginning of a request and destroyed when execution completes.

---

# 2. Design Objectives

The Execution Context SHALL:

- remain immutable after creation;
- exist only for one execution session;
- isolate concurrent executions;
- support hierarchical contexts;
- support execution cancellation;
- support execution deadlines;
- remain independent from implementation technologies.

---

# 3. Execution Context Architecture

```text
Incoming Request
        │
        ▼
Execution Context Builder
        │
        ▼
Execution Context
        │
        ├── Identity Context
        ├── Tenant Context
        ├── Security Context
        ├── Locale Context
        ├── Transaction Context
        ├── Correlation Context
        ├── Runtime Context
        ├── Compiler Context
        └── Execution Policy
                │
                ▼
Runtime Engines
```

The Runtime Kernel SHALL pass the Execution Context to every runtime engine participating in execution.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Execution Context
```

The Execution Context owns every request-scoped runtime value.

---

# 5. Execution Context Meta Model

```text
Execution Context

│

├── Identity Context

├── Tenant Context

├── Security Context

├── Locale Context

├── Transaction Context

├── Correlation Context

├── Runtime Context

├── Compiler Context

├── Execution Policy

├── Parent Context

└── Cancellation Token
```

---

# 6. Identity Context

The Identity Context SHALL contain:

- authenticated identity;
- principal identifier;
- assigned roles;
- organizational membership;
- delegated identity (if applicable).

Identity information SHALL remain immutable throughout execution.

---

# 7. Tenant Context

The Tenant Context SHALL define:

- tenant identifier;
- organization identifier;
- business unit;
- environment;
- deployment identifier.

Tenant isolation SHALL be enforced by all runtime engines.

---

# 8. Security Context

The Security Context SHALL contain:

- granted permissions;
- active security policies;
- row-level access constraints;
- field-level access constraints;
- execution privileges.

Runtime engines SHALL consume the Security Context rather than directly querying authorization services.

---

# 9. Transaction Context

The Transaction Context SHALL define:

- transaction identifier;
- transaction scope;
- consistency requirements;
- isolation policy;
- compensation policy.

Transaction behavior SHALL remain independent of the underlying database implementation.

---

# 10. Correlation Context

The Correlation Context SHALL support distributed execution.

Typical information includes:

- correlation identifier;
- parent execution identifier;
- request identifier;
- trace identifier;
- span identifier.

Correlation metadata SHALL support end-to-end traceability.

---

# 11. Runtime Context

The Runtime Context SHALL contain:

- runtime package identifier;
- runtime version;
- execution graph identifier;
- scheduler identifier;
- execution state.

Runtime engines SHALL treat these values as read-only.

---

# 12. Compiler Context

The Compiler Context SHALL preserve compiler-generated execution metadata.

Typical values include:

- compiler version;
- compilation identifier;
- optimization profile;
- conformance profile;
- build identifier.

Compiler metadata SHALL support runtime traceability.

---

# 13. Parent and Child Contexts

Execution Contexts MAY be hierarchical.

Example:

```text
Parent Context

↓

Workflow

↓

Child Context

↓

Sub Workflow

↓

Grandchild Context
```

Child contexts SHALL inherit immutable parent values unless explicitly overridden by compiler-defined policies.

---

# 14. Cancellation and Deadline

Execution Context SHALL support cancellation and deadlines.

Typical lifecycle:

```text
Execution Started

↓

Deadline Active

↓

Cancellation Requested

↓

Execution Stopped

↓

Context Disposed
```

Cancellation SHALL propagate to all active runtime engines participating in the execution session.

---

# 15. Constraints

| ID | Constraint |
|----|------------|
| ECX-001 | Execution Context SHALL be immutable |
| ECX-002 | Context SHALL exist only within one execution session |
| ECX-003 | Child contexts SHALL preserve parent invariants |
| ECX-004 | Cancellation SHALL propagate deterministically |
| ECX-005 | Runtime engines SHALL NOT modify the Execution Context |

---

# 16. Relationships

```text
Execution Session

creates

Execution Context

contains

Identity Context

contains

Security Context

contains

Transaction Context

contains

Compiler Context

consumed by

Runtime Engines
```

---

# 17. Traceability

```text
Incoming Request

↓

Execution Context

↓

Execution Graph

↓

Runtime Engines

↓

Audit

↓

Telemetry
```

Every runtime action SHALL be traceable to its originating Execution Context.

---

# 18. Risks

Potential risks include:

- context leakage;
- mutable shared state;
- inconsistent cancellation handling;
- excessive context size;
- broken parent-child inheritance.

These risks SHALL be mitigated through immutable context construction, strict lifecycle management, deterministic inheritance rules, runtime validation, and telemetry.

---

# 19. Summary

The Execution Context provides the immutable execution universe for every runtime request within ODAF.

By consolidating identity, tenant, security, transaction, compiler metadata, execution policies, correlation information, and lifecycle controls into a single request-scoped object, the Execution Context enables deterministic, isolated, and technology-independent runtime execution.

This architecture eliminates hidden global state, simplifies engine interactions, and provides the foundation for scalable orchestration across all runtime services.

---

# Execution Context Overview

```text
Incoming Request
        │
        ▼
Execution Context
        ├── Identity
        ├── Tenant
        ├── Security
        ├── Locale
        ├── Transaction
        ├── Correlation
        ├── Runtime
        ├── Compiler
        ├── Execution Policy
        └── Cancellation
                │
                ▼
Runtime Engines
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| ECX_BUILDER | Execution Context creation |
| ECX_IDENTITY | Identity context |
| ECX_SECURITY | Security context |
| ECX_TRANSACTION | Transaction context |
| ECX_CORRELATION | Correlation management |
| ECX_RUNTIME | Runtime metadata |
| ECX_COMPILER | Compiler metadata |
| ECX_CANCELLATION | Cancellation and deadline management |

---

# Next Document

➡ **11-Dataset-Engine.md**

The next chapter defines the Dataset Engine, including dataset execution, query planning, execution strategies, runtime adapters, caching policies, and compiler-generated dataset graphs that provide deterministic data access across supported backend technologies.