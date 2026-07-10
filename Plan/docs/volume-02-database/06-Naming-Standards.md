---
document_id: DB-V2-006
title: Naming Standards
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-005
  - DB-V2-007
---

# Chapter 06

# Naming Standards

---

# 1. Purpose

This chapter defines the enterprise naming conventions used throughout the Oracle Dynamic Application Framework (ODAF).

Consistent naming improves:

- readability;
- maintainability;
- traceability;
- automation;
- compiler implementation;
- Studio generation;
- deployment consistency.

All Oracle objects SHALL conform to these standards.

---

# 2. Design Principles

The naming convention SHALL satisfy the following principles.

- Consistent
- Predictable
- Human Readable
- Machine Readable
- Stable
- Domain Oriented
- Technology Independent whenever practical

---

# 3. General Rules

All identifiers SHALL:

- use UPPER_CASE;
- use underscore (_) as separator;
- avoid spaces;
- avoid special characters;
- avoid abbreviations unless standardized;
- use singular nouns for entity names;
- remain stable after publication.

Example

```text
APP_APPLICATION

UI_PAGE

DS_DATASET

WF_WORKFLOW
```

---

# 4. Repository Prefixes

Every metadata repository SHALL own a unique prefix.

| Repository | Prefix |
|------------|--------|
| Application | APP_ |
| User Interface | UI_ |
| Dataset | DS_ |
| Workflow | WF_ |
| Validation | VAL_ |
| Security | SEC_ |
| Reporting | RPT_ |
| Notification | NTF_ |
| Integration | INT_ |
| Runtime | RT_ |
| Deployment | DEP_ |
| Audit | AUD_ |
| Governance | GOV_ |
| Knowledge | KB_ |
| System | SYS_ |

No prefix SHALL be reused.

---

# 5. Table Naming

Table names SHALL follow:

```text
<PREFIX>_<ENTITY>
```

Examples

```text
APP_APPLICATION

APP_MODULE

UI_PAGE

DS_DATASET

WF_WORKFLOW
```

Table names SHALL describe business meaning.

---

# 6. History Tables

History tables SHALL follow:

```text
<TABLE_NAME>_HIST
```

Examples

```text
APP_APPLICATION_HIST

UI_PAGE_HIST

DS_DATASET_HIST
```

History tables SHALL be immutable.

---

# 7. View Naming

Views SHALL begin with:

```text
V_
```

Examples

```text
V_APP_APPLICATION

V_UI_PAGE

V_DS_DATASET
```

Read-only runtime views MAY use:

```text
RV_
```

Example

```text
RV_RUNTIME_PAGE
```

---

# 8. Package Naming

PL/SQL packages SHALL follow:

```text
PKG_<DOMAIN>_<ENTITY>
```

Examples

```text
PKG_APP_APPLICATION

PKG_UI_PAGE

PKG_DS_DATASET
```

Service packages SHALL expose published interfaces only.

---

# 9. Sequence Naming

Sequences SHALL follow:

```text
SEQ_<TABLE>
```

Example

```text
SEQ_APP_APPLICATION
```

---

# 10. Trigger Naming

Triggers SHALL follow:

```text
TRG_<TABLE>_<EVENT>
```

Examples

```text
TRG_APP_APPLICATION_BI

TRG_APP_APPLICATION_BU

TRG_UI_PAGE_AI
```

Standard suffixes

| Suffix | Meaning |
|---------|----------|
| BI | Before Insert |
| BU | Before Update |
| BD | Before Delete |
| AI | After Insert |
| AU | After Update |
| AD | After Delete |

---

# 11. Index Naming

Indexes SHALL follow:

```text
IDX_<TABLE>_<COLUMN>
```

Examples

```text
IDX_APP_APPLICATION_CODE

IDX_UI_PAGE_MENU
```

Unique indexes SHALL begin with

```text
UIDX_
```

---

# 12. Constraint Naming

Constraints SHALL follow standardized prefixes.

| Prefix | Meaning |
|---------|----------|
| PK_ | Primary Key |
| FK_ | Foreign Key |
| UK_ | Unique Constraint |
| CK_ | Check Constraint |
| NN_ | Not Null |

Examples

```text
PK_APP_APPLICATION

FK_UI_PAGE_MENU

UK_APP_CODE

CK_STATUS

NN_OBJECT_NAME
```

---

# 13. Column Naming

Column names SHALL describe business meaning.

Examples

```text
OBJECT_ID

OBJECT_CODE

OBJECT_NAME

STATUS

DESCRIPTION

DISPLAY_ORDER

ACTIVE_FLAG
```

Boolean columns SHALL end with

```text
_FLAG
```

Example

```text
VISIBLE_FLAG

ACTIVE_FLAG

CACHE_FLAG
```

---

# 14. Foreign Keys

Foreign key columns SHALL reference the parent object.

Example

```text
APPLICATION_ID

MODULE_ID

PAGE_ID

DATASET_ID
```

The suffix `_ID` SHALL be mandatory.

---

# 15. Date and Timestamp Columns

Standard names

```text
CREATED_AT

UPDATED_AT

APPROVED_AT

DEPLOYED_AT

EFFECTIVE_FROM

EFFECTIVE_UNTIL
```

All timestamps SHALL use Oracle `TIMESTAMP`.

---

# 16. Audit Columns

Every Aggregate Root SHOULD contain:

```text
CREATED_BY

CREATED_AT

UPDATED_BY

UPDATED_AT

VERSION_NO
```

These columns support auditing and optimistic locking.

---

# 17. Code Columns

Business identifiers SHALL use

```text
OBJECT_CODE
```

Display names SHALL use

```text
OBJECT_NAME
```

Descriptions SHALL use

```text
DESCRIPTION
```

Business codes SHALL remain immutable after publication.

---

# 18. Enumeration Naming

Lookup tables SHALL follow

```text
SYS_<CATEGORY>
```

Examples

```text
SYS_STATUS

SYS_LANGUAGE

SYS_COUNTRY
```

Enumeration codes SHALL use uppercase.

---

# 19. API Naming

REST endpoints SHALL follow:

```text
/api/applications

/api/pages

/api/datasets
```

JSON properties SHALL use:

```text
camelCase
```

Example

```json
{
  "objectId": "...",
  "objectCode": "...",
  "objectName": "..."
}
```

---

# 20. Runtime Object Naming

Compiled runtime objects SHALL begin with

```text
RT_
```

Examples

```text
RT_PAGE

RT_DATASET

RT_WORKFLOW
```

---

# 21. Compiler Artifact Naming

Compiler artifacts SHALL begin with

```text
CMP_
```

Examples

```text
CMP_PAGE

CMP_WORKFLOW

CMP_DATASET
```

Intermediate Representation artifacts MAY begin with

```text
MIR_
```

Examples

```text
MIR_PAGE

MIR_DATASET
```

---

# 22. File Naming

Documentation SHALL use

```text
NN-Document-Name.md
```

Examples

```text
05-Metadata-Meta-Model.md

24-Enterprise-ERD.md
```

Deployment packages SHALL follow

```text
ODAF_<VERSION>.zip
```

---

# 23. Reserved Words

The following SHALL NOT be used as identifiers.

Examples include

- USER
- DATE
- NUMBER
- TABLE
- INDEX
- VIEW
- ORDER
- GROUP

If required, domain-specific prefixes SHALL be added.

Example

```text
APP_USER

SYS_ORDER_STATUS
```

---

# 24. Naming Compliance

Every Oracle object SHALL comply with these standards.

Compliance SHALL be verified automatically by the Metadata Compiler and ODAF Studio.

Objects violating these conventions SHALL fail validation unless explicitly approved through governance.

---

# 25. Summary

The naming standards defined in this chapter establish a consistent, predictable, and scalable naming convention for all Oracle metadata objects within ODAF.

By applying standardized prefixes, suffixes, lifecycle conventions, and repository-specific namespaces, ODAF ensures that metadata remains understandable, traceable, and automation-friendly across hundreds of tables, packages, views, constraints, indexes, and runtime artifacts.

These standards are mandatory for all future metadata domains and implementation artifacts.

---

# Naming Catalog

| Object Type | Pattern |
|-------------|---------|
| Table | `<PREFIX>_<ENTITY>` |
| History Table | `<TABLE>_HIST` |
| View | `V_<TABLE>` |
| Runtime View | `RV_<ENTITY>` |
| Package | `PKG_<DOMAIN>_<ENTITY>` |
| Sequence | `SEQ_<TABLE>` |
| Trigger | `TRG_<TABLE>_<EVENT>` |
| Index | `IDX_<TABLE>_<COLUMN>` |
| Unique Index | `UIDX_<TABLE>_<COLUMN>` |
| Primary Key | `PK_<TABLE>` |
| Foreign Key | `FK_<CHILD>_<PARENT>` |
| Check Constraint | `CK_<TABLE>_<RULE>` |
| Not Null Constraint | `NN_<TABLE>_<COLUMN>` |

---

# Next Document

➡ **07-Universal-Metadata-Object.md**

The next chapter defines the Universal Metadata Object (UMO), the logical base contract implemented by every aggregate root within the Oracle Metadata Repository.