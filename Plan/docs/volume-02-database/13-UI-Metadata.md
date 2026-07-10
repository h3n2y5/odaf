---
document_id: DB-V2-013
title: UI Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-012
  - DB-V2-014
  - DB-V2-005
---

# Chapter 13

# UI Metadata

---

# 1. Purpose

This chapter defines the User Interface (UI) Metadata Repository of the Oracle Dynamic Application Framework (ODAF).

The UI Repository describes the complete presentation layer through metadata.

User interfaces SHALL be constructed dynamically from metadata rather than handwritten presentation code.

The UI Repository defines:

- views;
- layouts;
- component trees;
- component properties;
- actions;
- presentation behavior.

---

# 2. Design Objectives

UI Metadata SHALL:

- separate presentation from business logic;
- support reusable components;
- support responsive layouts;
- support multiple rendering targets;
- remain metadata-driven;
- enable visual design through ODAF Studio.

---

# 3. UI Architecture

The UI Repository follows the hierarchy below.

```text
Application
    │
    ▼
Feature
    │
    ▼
View
    │
    ▼
Layout
    │
    ▼
Component Tree
    │
    ▼
Component
    │
    ▼
Property
```

Business capability remains independent from presentation.

---

# 4. Aggregate Root

The Aggregate Root of the UI Repository is:

```text
View
```

Each View belongs to exactly one Feature.

A Feature MAY expose multiple Views.

Examples include:

- List View
- Detail View
- Create View
- Edit View
- Approval View
- Dashboard View

---

# 5. View

A View represents a complete presentation model.

Typical attributes include:

- identity;
- title;
- layout;
- navigation;
- lifecycle;
- default route.

A View SHALL remain independent from rendering technology.

---

# 6. Layout

Layouts organize Components spatially.

Supported logical layouts include:

- Vertical Layout
- Horizontal Layout
- Grid Layout
- Split Layout
- Tab Layout
- Card Layout
- Wizard Layout
- Dashboard Layout

Layouts MAY be nested.

---

# 7. Component Tree

Every View SHALL contain exactly one Component Tree.

Example:

```text
View
 │
 ├── Header
 ├── Toolbar
 ├── Grid
 ├── Filter Panel
 ├── Detail Panel
 └── Footer
```

Components form a hierarchical tree.

---

# 8. Component

A Component is the smallest reusable UI building block.

Examples include:

- Text
- TextBox
- NumberBox
- DatePicker
- Grid
- Tree
- Chart
- Button
- Card
- Dialog
- Image
- Icon

Every Component SHALL have a Component Type.

---

# 9. Component Types

Component Types define behavior.

Examples include:

| Component Type | Purpose |
|----------------|---------|
| TEXTBOX | Text input |
| GRID | Tabular data |
| BUTTON | Action |
| CARD | Information container |
| CHART | Visualization |
| IMAGE | Static image |
| TAB | Navigation |
| DIALOG | Modal window |
| TREE | Hierarchical display |

New Component Types MAY be introduced through plugins.

---

# 10. Component Properties

Every Component exposes metadata properties.

Typical properties include:

- label;
- width;
- height;
- visibility;
- read-only;
- required;
- style;
- icon;
- tooltip;
- default value.

Properties SHALL be metadata-driven.

---

# 11. Component Actions

Components MAY define actions.

Examples include:

- click;
- change;
- focus;
- blur;
- refresh;
- open dialog;
- navigate;
- invoke workflow.

Actions SHALL reference metadata rather than executable code.

---

# 12. Data Binding

Components SHALL bind to metadata-defined datasets.

```text
Component

↓

Dataset

↓

Column

↓

Runtime Data
```

Direct SQL access from Components SHALL NOT be permitted.

---

# 13. Validation Binding

Components MAY reference Validation Metadata.

```text
Component

↓

Validation Rule

↓

Runtime Validation
```

Validation SHALL remain declarative.

---

# 14. Security Binding

UI Components SHALL support metadata-driven security.

Examples include:

- visibility;
- editability;
- enabled state;
- execution rights.

Security SHALL integrate with the Security Repository.

---

# 15. Styling

Presentation styling SHALL be metadata-driven.

Examples include:

- themes;
- colors;
- typography;
- spacing;
- responsive behavior.

Styling SHALL remain independent from business logic.

---

# 16. Responsive Design

The platform SHALL support multiple rendering targets.

Examples include:

- desktop;
- tablet;
- mobile;
- kiosk.

Rendering behavior SHALL be derived from metadata.

---

# 17. Plugin Components

Developers MAY introduce custom Component Types through plugins.

Examples include:

- QR Code
- Signature Pad
- GIS Map
- AI Chat
- Timeline
- Markdown Viewer

Plugins SHALL conform to published UI extension interfaces.

---

# 18. Runtime Transformation

During compilation:

```text
UI Metadata

↓

Compiler

↓

Runtime View

↓

Renderer

↓

Browser
```

The logical UI structure SHALL be preserved.

---

# 19. Constraints

The following constraints apply.

| ID | Constraint |
|----|------------|
| UI-001 | Every View belongs to one Feature. |
| UI-002 | Every View has one Component Tree. |
| UI-003 | Components SHALL have one Component Type. |
| UI-004 | Components SHALL NOT execute SQL directly. |
| UI-005 | Security SHALL be metadata-driven. |

---

# 20. Relationships

```text
Feature

owns

View

owns

Component Tree

owns

Component

owns

Property

references

Dataset

references

Validation

references

Security
```

---

# 21. Traceability

```text
Business Capability

↓

Feature

↓

View

↓

Component

↓

Renderer

↓

Browser
```

Presentation SHALL remain traceable to business capability.

---

# 22. Risks

Potential risks include:

- oversized component trees;
- duplicate components;
- inconsistent layouts;
- excessive nesting;
- plugin incompatibility.

These risks SHALL be mitigated through compiler validation and Studio tooling.

---

# 23. Summary

The UI Repository defines the metadata-driven presentation architecture of ODAF.

By representing user interfaces as hierarchical component trees composed of reusable Components, Layouts, and Views, ODAF separates presentation from business logic while enabling extensibility, responsive rendering, plugin integration, and long-term maintainability.

This repository provides the presentation foundation upon which Runtime Renderers, ODAF Studio, and future rendering engines are built.

---

# UI Repository Model

```text
View
    │
    ├── Layout
    ├── Component Tree
    ├── Component
    ├── Property
    ├── Dataset Binding
    ├── Validation Binding
    ├── Security Binding
    └── Action
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| View | UI_VIEW |
| Layout | UI_LAYOUT |
| Component Tree | UI_COMPONENT_TREE |
| Component | UI_COMPONENT |
| Component Type | UI_COMPONENT_TYPE |
| Component Property | UI_COMPONENT_PROPERTY |
| Component Action | UI_COMPONENT_ACTION |
| View Route | UI_VIEW_ROUTE |

---

# Next Document

➡ **14-Dataset-Metadata.md**

The next chapter defines the Dataset Repository, including datasets, SQL definitions, parameters, joins, filters, caching policies, data binding, and query optimization metadata that connect business capabilities to enterprise data sources.