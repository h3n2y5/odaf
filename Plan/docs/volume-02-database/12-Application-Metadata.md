---
document_id: DB-V2-012
title: Application Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-011
  - DB-V2-013
  - DB-V2-003
  - DB-V2-005
---

# Chapter 12

# Application Metadata

---

# 1. Purpose

This chapter defines the metadata model for applications within the Oracle Dynamic Application Framework (ODAF).

The Application Repository represents the highest business abstraction in the platform.

Every executable solution SHALL belong to exactly one Application.

The Application Repository defines:

- application identity;
- application lifecycle;
- application configuration;
- module organization;
- navigation hierarchy;
- application ownership.

---

# 2. Design Objectives

Application Metadata SHALL:

- represent enterprise software products;
- organize business capabilities;
- provide stable application identity;
- support modular architecture;
- support independent deployment;
- support metadata-driven configuration.

---

# 3. Application Hierarchy

The Application Repository follows the hierarchy below.

```text
Platform
    │
    ▼
Application
    │
    ▼
Module
    │
    ▼
Feature
    │
    ▼
Navigation
    │
    ▼
Page
```

Each level has a distinct responsibility.

---

# 4. Aggregate Root

The Aggregate Root of the Application Repository is:

```text
Application
```

All child objects SHALL belong to one Application.

Cross-application ownership SHALL NOT be permitted.

---

# 5. Application Meta Model

The logical composition of an Application is shown below.

```text
Application
        │
        ├── Manifest
        ├── Configuration
        ├── Module
        ├── Navigation
        ├── Security Mapping
        ├── Deployment Mapping
        └── Runtime Mapping
```

---

# 6. Application Manifest

Every Application SHALL define a Manifest.

The Manifest describes the identity and configuration of the application.

Typical attributes include:

| Attribute | Description |
|-----------|-------------|
| OBJECT_ID | Platform identifier |
| OBJECT_CODE | Business code |
| OBJECT_NAME | Application name |
| APPLICATION_VERSION | Current version |
| DISPLAY_NAME | Display caption |
| DESCRIPTION | Functional description |
| OWNER_ID | Business owner |
| DEFAULT_LANGUAGE | Default language |
| DEFAULT_TIMEZONE | Default timezone |
| DEFAULT_THEME | UI theme |
| DEFAULT_HOME_PAGE | Startup page |
| STATUS | Lifecycle state |

The Manifest SHALL be versioned.

---

# 7. Module

A Module groups related business capabilities.

Examples:

```text
Purchasing

Inventory

Finance

Sales

Human Resources
```

Each Module SHALL belong to exactly one Application.

---

# 8. Feature

A Feature represents a coherent business capability within a Module.

Examples:

```text
Purchase Request

Purchase Order

Goods Receipt

Invoice Matching
```

Features provide logical organization without imposing UI structure.

---

# 9. Navigation

Navigation organizes user access to functionality.

Navigation metadata includes:

- navigation groups;
- menus;
- menu hierarchy;
- default routes;
- landing pages.

Navigation SHALL be metadata-driven.

---

# 10. Application Configuration

Application-specific configuration SHALL be stored as metadata.

Examples include:

- localization;
- branding;
- theme;
- supported languages;
- default currency;
- default organization;
- feature toggles.

Configuration SHALL NOT require runtime code modification.

---

# 11. Lifecycle

Applications SHALL follow the standard metadata lifecycle.

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

---

# 12. Versioning

Each Application SHALL expose:

- application version;
- compatibility version;
- deployment version;
- runtime version.

Application versioning SHALL comply with Chapter 09.

---

# 13. Ownership

Each Application SHALL define:

- business owner;
- technical owner;
- architecture owner;
- support team.

Ownership SHALL be auditable.

---

# 14. Security Mapping

Applications SHALL define security mappings.

Examples:

- default roles;
- administrative roles;
- guest access;
- feature permissions;
- module permissions.

Application security SHALL integrate with the Security Repository.

---

# 15. Deployment Mapping

Applications SHALL identify deployment artifacts.

Typical mappings include:

- deployment package;
- runtime package;
- compiler profile;
- environment configuration.

Deployment SHALL remain metadata-driven.

---

# 16. Runtime Mapping

During compilation the Application is transformed into Runtime Metadata.

```text
APP_APPLICATION
        │
        ▼
Compiler
        │
        ▼
RT_APPLICATION
```

Logical identity SHALL be preserved.

---

# 17. Relationships

Application Metadata owns the following relationships.

```text
Application

owns

Module

owns

Feature

owns

Navigation

references

Security

references

Deployment
```

Relationships SHALL remain acyclic.

---

# 18. Constraints

The following constraints apply.

| ID | Constraint |
|----|------------|
| APP-001 | Every Module belongs to one Application. |
| APP-002 | Every Feature belongs to one Module. |
| APP-003 | Every Navigation belongs to one Application. |
| APP-004 | Application Code SHALL be unique. |
| APP-005 | Only one active Manifest version is permitted per effective period. |

---

# 19. Logical Repository Objects

The following logical objects compose the Application Repository.

| Object | Responsibility |
|---------|----------------|
| Application | Aggregate Root |
| Manifest | Identity and configuration |
| Module | Business grouping |
| Feature | Business capability |
| Navigation | Navigation hierarchy |
| Configuration | Runtime configuration |

Physical Oracle tables are specified in later chapters.

---

# 20. Traceability

Application Metadata SHALL participate in architecture traceability.

```text
Business Requirement
        │
        ▼
Application
        │
        ▼
Module
        │
        ▼
Feature
        │
        ▼
Page
        │
        ▼
Runtime
```

---

# 21. Risks

Potential risks include:

- oversized applications;
- excessive module coupling;
- duplicated features;
- inconsistent configuration;
- unmanaged application growth.

These risks SHALL be mitigated through modular design, governance, and metadata validation.

---

# 22. Summary

The Application Repository defines the highest-level business structure within ODAF.

By modeling applications as modular software products composed of manifests, modules, features, navigation, and configuration, ODAF separates business organization from implementation details while enabling metadata-driven deployment, governance, and runtime execution.

This repository serves as the root from which all subsequent metadata domains—including User Interface, Dataset, Workflow, and Security—are organized.

---

# Application Repository Model

```text
Application
        │
        ├── Manifest
        ├── Module
        ├── Feature
        ├── Navigation
        ├── Configuration
        ├── Security Mapping
        ├── Deployment Mapping
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Application | APP_APPLICATION |
| Manifest | APP_MANIFEST |
| Module | APP_MODULE |
| Feature | APP_FEATURE |
| Navigation | APP_NAVIGATION |
| Configuration | APP_CONFIGURATION |

---

# Next Document

➡ **13-UI-Metadata.md**

The next chapter defines the User Interface Repository, including pages, tabs, panels, sections, fields, layouts, actions, and presentation metadata that transform business capabilities into interactive user experiences.