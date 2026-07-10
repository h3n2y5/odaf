---
document_id: DB-V2-025
title: Core DDL
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-024
  - DB-V2-026
---

# Chapter 25

# Core DDL

---

# 1. Purpose

This chapter defines the physical Oracle implementation standards for the ODAF Metadata Repository.

Rather than documenting individual SQL statements only, this chapter establishes the mandatory conventions governing every Oracle object created by the platform.

The Core DDL specification SHALL serve as the authoritative reference for all database schemas, tables, indexes, constraints, storage objects, and generated DDL.

---

# 2. Design Objectives

The Core DDL SHALL:

- standardize Oracle object creation;
- ensure consistency across repositories;
- support compiler-generated DDL;
- optimize enterprise scalability;
- simplify maintenance;
- support future schema evolution.

---

# 3. Oracle Repository Structure

The metadata repository SHALL be organized into logical domains.

```text
APP_*

UI_*

DS_*

WF_*

VAL_*

RPT_*

NTF_*

INT_*

DEP_*

RT_*

SEC_*

AUD_*

GOV_*

KNW_*
```

Each domain SHALL own its physical tables.

---

# 4. Standard Aggregate Root Columns

Every Aggregate Root SHALL define at least the following columns.

| Column | Type | Description |
|----------|------|------------|
| OBJECT_ID | RAW(16) | Immutable platform identifier |
| OBJECT_CODE | VARCHAR2(100) | Business identifier |
| OBJECT_NAME | VARCHAR2(200) | Display name |
| DESCRIPTION | CLOB | Description |
| VERSION_NO | NUMBER(10) | Metadata version |
| STATUS | VARCHAR2(30) | Lifecycle status |
| CREATED_AT | TIMESTAMP WITH TIME ZONE | Creation timestamp |
| UPDATED_AT | TIMESTAMP WITH TIME ZONE | Last update timestamp |
| CREATED_BY | RAW(16) | Creator reference |
| UPDATED_BY | RAW(16) | Last modifier reference |

Additional columns MAY be introduced by individual repositories.

---

# 5. Standard Data Types

The following Oracle data types SHALL be preferred.

| Logical Type | Oracle Type |
|---------------|-------------|
| Identifier | RAW(16) |
| Code | VARCHAR2(100 CHAR) |
| Name | VARCHAR2(200 CHAR) |
| Description | CLOB |
| Boolean | NUMBER(1) |
| Integer | NUMBER(10) |
| Decimal | NUMBER(38,10) |
| Date | DATE |
| Timestamp | TIMESTAMP WITH TIME ZONE |
| JSON | JSON or CLOB (depending on Oracle version) |

---

# 6. Naming Standards

All physical objects SHALL follow consistent naming conventions.

| Object | Prefix |
|----------|--------|
| Table | APP_, UI_, DS_, WF_, RT_, ... |
| Primary Key | PK_ |
| Foreign Key | FK_ |
| Unique Constraint | UK_ |
| Check Constraint | CK_ |
| Index | IDX_ |
| Sequence (if required) | SEQ_ |
| Trigger | TRG_ |
| View | VW_ |
| Package | PKG_ |

Names SHALL be deterministic and compiler-generated.

---

# 7. Constraint Standards

Every table SHALL define:

- Primary Key;
- Required Foreign Keys;
- Business Unique Constraints;
- Check Constraints where applicable.

Foreign keys SHALL reference `OBJECT_ID`.

Business uniqueness SHALL use dedicated unique constraints.

---

# 8. Indexing Standards

The compiler SHALL generate indexes according to repository semantics.

Recommended indexes include:

- Primary Key indexes;
- Foreign Key indexes;
- Business Code indexes;
- Status indexes;
- Version indexes.

Composite indexes SHALL be generated only when justified.

---

# 9. Tablespace Strategy

Recommended tablespaces include:

| Tablespace | Purpose |
|------------|---------|
| TS_META | Design-time metadata |
| TS_RUNTIME | Runtime metadata |
| TS_AUDIT | Audit repositories |
| TS_INDEX | Secondary indexes |
| TS_LOB | Large Objects |

Organizations MAY customize tablespace assignments.

---

# 10. Runtime Storage

Runtime metadata SHALL reside in dedicated repositories.

Examples include:

```text
RT_APPLICATION

RT_VIEW

RT_DATASET

RT_WORKFLOW

RT_RULE
```

Runtime repositories SHOULD be configured as read-only after successful activation.

---

# 11. Storage Optimization

Recommended Oracle features include:

- Advanced Compression;
- SecureFiles LOB;
- Partitioning;
- Read-only tablespaces for archived metadata;
- Automatic Indexing (where appropriate).

Physical optimizations SHALL preserve logical behavior.

---

# 12. Partitioning Strategy

Repositories with high data volume MAY be partitioned.

Typical candidates include:

- AUD_EVENT;
- DEP_HISTORY;
- RT_EXECUTION_LOG;
- NTF_DELIVERY_HISTORY.

Partitioning strategies MAY include:

- range;
- interval;
- list;
- hash.

---

# 13. Compiler-Generated DDL

The Compiler SHALL generate Oracle DDL from metadata.

Generated artifacts include:

- CREATE TABLE;
- CREATE INDEX;
- ALTER TABLE;
- COMMENT ON;
- CREATE VIEW;
- PACKAGE specifications;
- PACKAGE bodies (where applicable).

Manual modification of generated objects SHOULD be avoided.

---

# 14. DDL Versioning

Generated DDL SHALL be versioned.

Each generated artifact SHALL record:

- compiler version;
- metadata version;
- generation timestamp;
- deployment package.

Version metadata SHALL be traceable.

---

# 15. Migration Strategy

Schema evolution SHALL be migration-based.

Supported operations include:

- create;
- alter;
- rename;
- deprecate;
- archive.

Destructive changes SHOULD require explicit approval.

---

# 16. Runtime Compatibility

Generated DDL SHALL remain compatible with Runtime Packages.

Compiler validation SHALL prevent incompatible schema changes from being deployed.

---

# 17. Security

DDL generation SHALL support:

- Oracle privileges;
- grants;
- synonyms (optional);
- Virtual Private Database (future);
- Row-Level Security integration.

Security metadata SHALL drive privilege generation.

---

# 18. Traceability

```text
Metadata

↓

Compiler

↓

Generated DDL

↓

Deployment Package

↓

Oracle Repository

↓

Runtime
```

Every physical object SHALL be traceable to its originating metadata definition.

---

# 19. Constraints

| ID | Constraint |
|----|------------|
| DDL-001 | Every Aggregate Root SHALL implement the standard column set |
| DDL-002 | All identifiers SHALL use OBJECT_ID as the technical key |
| DDL-003 | Naming conventions SHALL be compiler-generated |
| DDL-004 | Runtime repositories SHALL remain isolated from design repositories |
| DDL-005 | Generated DDL SHALL be version controlled |

---

# 20. Risks

Potential risks include:

- inconsistent naming;
- schema drift;
- manual database modifications;
- missing indexes;
- incompatible migrations.

These risks SHALL be mitigated through compiler-generated DDL, governance validation, automated migrations, and deployment verification.

---

# 21. Summary

The Core DDL specification defines the physical Oracle implementation standards for the ODAF Metadata Repository.

By standardizing column definitions, naming conventions, constraints, indexes, tablespaces, migrations, and compiler-generated artifacts, ODAF establishes a consistent and maintainable Oracle schema suitable for enterprise-scale deployments.

This chapter forms the bridge between the logical Enterprise ERD and the executable Oracle repository.

---

# Oracle Repository Layout

```text
Design-Time Repository
        │
        ├── APP_*
        ├── UI_*
        ├── DS_*
        ├── WF_*
        ├── VAL_*
        ├── RPT_*
        ├── NTF_*
        ├── INT_*
        ├── GOV_*
        ├── KNW_*
        └── SEC_*

                │
                ▼
             Compiler
                │
                ▼
Runtime Repository
        │
        ├── RT_APPLICATION
        ├── RT_VIEW
        ├── RT_DATASET
        ├── RT_WORKFLOW
        ├── RT_RULE
        ├── RT_REPORT
        ├── RT_NOTIFICATION
        └── RT_PACKAGE
```

---

# Next Document

➡ **26-Oracle-Physical-Implementation.md**

The next chapter defines the detailed Oracle implementation, including schema creation scripts, tablespace definitions, storage clauses, indexing examples, partitioning examples, and compiler-generated SQL templates.