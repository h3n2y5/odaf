---
document_id: DB-V2-027
title: History DDL
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-026
  - DB-V2-028
  - DB-V2-010
---

# Chapter 27

# History DDL

---

# 1. Purpose

This chapter defines the physical Oracle implementation standards for business history repositories within the Oracle Dynamic Application Framework (ODAF).

History repositories preserve the evolution of business objects over time.

Unlike Audit, which records operational events, History captures business state transitions and object versions.

History SHALL be deterministic, queryable, and traceable.

---

# 2. Design Objectives

History DDL SHALL:

- preserve business object evolution;
- support temporal queries;
- support version reconstruction;
- support timeline visualization;
- minimize storage overhead;
- remain compiler-generated.

---

# 3. History Architecture

```text
Business Object

↓

State Change

↓

Version

↓

Snapshot

↓

Timeline
```

History SHALL describe business evolution rather than operational logging.

---

# 4. Aggregate Root

The Aggregate Root is

```text
History Object
```

Each History Object corresponds to one Business Aggregate Root.

---

# 5. History Meta Model

```text
History Object

│

├── Version

├── Snapshot

├── Delta

├── Timeline Event

├── Relationship History

├── State

├── Retention Policy

└── Runtime Mapping
```

---

# 6. History Object

History SHALL represent one immutable version of a business object.

Typical attributes include:

| Attribute | Description |
|------------|-------------|
| HISTORY_ID | Unique history identifier |
| OBJECT_ID | Business object identifier |
| VERSION_NO | Object version |
| VALID_FROM | Effective timestamp |
| VALID_TO | Expiration timestamp |
| CHANGE_TYPE | Insert / Update / Delete / Merge |
| SNAPSHOT_FLAG | Indicates full snapshot |

---

# 7. Version Management

Every business object SHALL maintain version identity.

Versioning SHALL support:

- initial version;
- incremental versions;
- rollback reference;
- version comparison.

Versions SHALL be immutable.

---

# 8. Snapshot Strategy

Snapshots represent complete business object states.

Snapshots MAY be generated:

- periodically;
- after major changes;
- before deployment;
- before archival.

Snapshots SHALL enable efficient reconstruction.

---

# 9. Delta Strategy

Delta records SHALL capture only modified attributes.

Examples include:

- changed fields;
- changed relationships;
- changed status;
- changed ownership.

Compiler MAY optimize storage using delta encoding.

---

# 10. Timeline Events

Timeline events describe significant lifecycle milestones.

Examples:

- Created
- Submitted
- Approved
- Released
- Received
- Closed
- Cancelled

Timeline events SHALL remain ordered.

---

# 11. Relationship History

History SHALL preserve relationship evolution.

Examples:

- Supplier changes;
- Organization reassignment;
- Project reassignment;
- Workflow transitions.

Relationship history SHALL remain versioned.

---

# 12. Temporal Queries

History SHALL support temporal retrieval.

Examples include:

- state at a given date;
- compare versions;
- reconstruct object;
- show evolution timeline.

Temporal queries SHALL remain deterministic.

---

# 13. Retention Policy

History repositories SHALL support configurable retention.

Typical policies include:

- retain forever;
- archive after N years;
- legal hold;
- purge after expiration.

Retention SHALL be governed by metadata.

---

# 14. Physical Storage

Recommended Oracle implementation includes:

- dedicated history tables;
- partitioning by date;
- compression;
- read-only archived partitions.

History repositories SHOULD optimize analytical queries.

---

# 15. Runtime Mapping

Compilation transforms

```text
Business Object

↓

History Repository

↓

Runtime Timeline

↓

Business History
```

Logical identity SHALL be preserved.

---

# 16. Constraints

| ID | Constraint |
|----|------------|
| HIS-001 | Every History Object SHALL reference one Business Object |
| HIS-002 | Versions SHALL be immutable |
| HIS-003 | Timeline SHALL remain chronological |
| HIS-004 | Delta SHALL reference a valid version |
| HIS-005 | Retention SHALL be metadata-defined |

---

# 17. Relationships

```text
Business Aggregate

owns

History Object

owns

Version

owns

Snapshot

owns

Timeline Event

references

Workflow

references

Audit
```

---

# 18. Traceability

```text
Business Object

↓

History

↓

Version

↓

Timeline

↓

Runtime
```

History SHALL enable complete business object reconstruction.

---

# 19. Risks

Potential risks include:

- uncontrolled repository growth;
- fragmented timelines;
- inconsistent snapshots;
- invalid delta chains;
- excessive retention.

These risks SHALL be mitigated through partitioning, compression, governance, and compiler validation.

---

# 20. Summary

The History Repository defines the temporal storage architecture of ODAF.

By separating history from audit, preserving immutable object versions, supporting snapshots and delta encoding, and enabling deterministic temporal queries, ODAF provides enterprise-grade lifecycle tracking for business data.

This architecture supports regulatory compliance, business analytics, rollback analysis, and historical reconstruction without compromising runtime performance.

---

# History Repository Model

```text
History Object
        │
        ├── Version
        ├── Snapshot
        ├── Delta
        ├── Timeline Event
        ├── Relationship History
        ├── State
        ├── Retention Policy
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| History Object | HIS_OBJECT |
| History Version | HIS_VERSION |
| Snapshot | HIS_SNAPSHOT |
| Delta | HIS_DELTA |
| Timeline Event | HIS_TIMELINE |
| Relationship History | HIS_RELATIONSHIP |
| Retention Policy | HIS_RETENTION |
| Runtime History | RT_HISTORY |

---

# Next Document

➡ **28-Audit-DDL.md**

The next chapter defines the Audit Repository, including immutable audit events, security logging, operational tracing, session history, compliance records, and append-only storage strategies for enterprise governance.