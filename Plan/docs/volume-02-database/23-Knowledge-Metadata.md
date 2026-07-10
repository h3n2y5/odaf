---
document_id: DB-V2-023
title: Knowledge Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-022
  - DB-V2-024
  - DB-V2-012
  - DB-V2-015
  - DB-V2-016
---

# Chapter 23

# Knowledge Metadata

---

# 1. Purpose

This chapter defines the Knowledge Repository of the Oracle Dynamic Application Framework (ODAF).

The Knowledge Repository captures reusable architectural, business, and technical knowledge as structured metadata.

Unlike application metadata, Knowledge Metadata represents reusable expertise that can be applied across multiple applications, domains, and organizations.

Knowledge Metadata serves as the foundation for automation, standardization, AI-assisted development, and enterprise reuse.

---

# 2. Design Objectives

Knowledge Metadata SHALL:

- capture reusable enterprise knowledge;
- standardize architectural patterns;
- preserve organizational expertise;
- enable metadata generation;
- support AI-assisted development;
- support knowledge discovery;
- remain technology independent.

---

# 3. Knowledge Architecture

```text
Domain

↓

Capability

↓

Knowledge

↓

Pattern

↓

Template

↓

Generated Metadata
```

Knowledge SHALL be reusable across applications.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Knowledge Object
```

Knowledge Objects SHALL remain independent from specific applications.

---

# 5. Knowledge Meta Model

```text
Knowledge Object

│

├── Domain

├── Capability

├── Pattern

├── Template

├── Best Practice

├── Guideline

├── Reference

├── AI Prompt

├── Example

└── Runtime Mapping
```

---

# 6. Domain

Domains organize enterprise knowledge.

Examples

- ERP
- CRM
- Finance
- Human Resources
- Manufacturing
- Healthcare
- Government

Domains SHALL be hierarchical.

---

# 7. Capability

Capabilities describe reusable business functions.

Examples

- Purchase Order
- Goods Receipt
- Invoice Matching
- Payroll
- Recruitment

Capabilities SHALL remain independent of implementation.

---

# 8. Knowledge Objects

Knowledge Objects capture reusable knowledge.

Examples

- Business Pattern
- UI Pattern
- Workflow Pattern
- Security Pattern
- Integration Pattern
- Reporting Pattern

Knowledge Objects SHALL be versioned.

---

# 9. Patterns

Patterns define reusable architectural solutions.

Examples

- Master Data
- Document Processing
- Approval Workflow
- Dashboard
- Wizard
- Lookup
- Search Dialog

Patterns SHALL reference reusable metadata.

---

# 10. Templates

Templates generate metadata.

Examples

- Purchase Order Module
- Approval Workflow
- Dashboard Layout
- Integration Adapter
- Security Model

Templates SHALL support parameterization.

---

# 11. Best Practices

Knowledge SHALL capture enterprise best practices.

Examples include

- naming conventions;
- performance guidelines;
- indexing recommendations;
- security recommendations;
- UX recommendations.

Best Practices SHALL remain version controlled.

---

# 12. Guidelines

Guidelines describe recommended implementation approaches.

Examples include:

- UI design;
- workflow modeling;
- reporting;
- deployment;
- testing.

Guidelines SHALL complement Governance Policies.

---

# 13. References

Knowledge MAY reference:

- standards;
- specifications;
- internal documentation;
- architectural decisions (ADR);
- reusable assets.

References SHALL remain traceable.

---

# 14. AI Prompt Metadata

Knowledge MAY define reusable AI prompts.

Examples include:

- metadata generation prompts;
- workflow generation prompts;
- SQL generation prompts;
- documentation prompts.

Prompt Templates SHALL be versioned.

---

# 15. Examples

Knowledge MAY include executable examples.

Examples include:

- metadata snippets;
- workflow samples;
- UI samples;
- integration examples;
- validation examples.

Examples SHALL remain synchronized with current metadata versions.

---

# 16. Knowledge Graph

Knowledge SHALL be represented as a graph.

```text
Domain

↓

Capability

↓

Pattern

↓

Template

↓

Metadata

↓

Application
```

Knowledge relationships SHALL be navigable.

---

# 17. Runtime Mapping

Compilation transforms

```text
KNW_OBJECT

↓

Compiler

↓

RT_KNOWLEDGE

↓

Knowledge Engine
```

Knowledge identity SHALL be preserved.

---

# 18. Constraints

| ID | Constraint |
|----|------------|
| KNW-001 | Every Knowledge Object belongs to one Domain |
| KNW-002 | Patterns SHALL be reusable |
| KNW-003 | Templates SHALL be parameterized |
| KNW-004 | AI Prompts SHALL be versioned |
| KNW-005 | References SHALL remain traceable |

---

# 19. Relationships

```text
Domain

owns

Capability

owns

Knowledge Object

owns

Pattern

owns

Template

references

Workflow

references

UI

references

Dataset

references

Validation
```

---

# 20. Traceability

```text
Knowledge

↓

Pattern

↓

Template

↓

Metadata

↓

Compiler

↓

Runtime
```

Every generated metadata artifact SHALL be traceable to its originating Knowledge Object.

---

# 21. Risks

Potential risks include:

- duplicated knowledge;
- obsolete templates;
- inconsistent best practices;
- AI prompt drift;
- excessive specialization.

These risks SHALL be mitigated through governance, versioning, review workflows, and automated validation.

---

# 22. Summary

The Knowledge Repository extends ODAF beyond a metadata platform into a knowledge-driven development platform.

By capturing reusable business capabilities, architectural patterns, templates, guidelines, AI prompts, and enterprise best practices as structured metadata, ODAF enables standardized application development, organizational learning, and AI-assisted automation.

Knowledge becomes a first-class architectural asset that continuously enriches the platform and accelerates future solution delivery.

---

# Knowledge Repository Model

```text
Knowledge Object
        │
        ├── Domain
        ├── Capability
        ├── Pattern
        ├── Template
        ├── Best Practice
        ├── Guideline
        ├── Reference
        ├── AI Prompt
        ├── Example
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Knowledge Object | KNW_OBJECT |
| Domain | KNW_DOMAIN |
| Capability | KNW_CAPABILITY |
| Pattern | KNW_PATTERN |
| Template | KNW_TEMPLATE |
| Best Practice | KNW_BEST_PRACTICE |
| Guideline | KNW_GUIDELINE |
| Reference | KNW_REFERENCE |
| AI Prompt | KNW_AI_PROMPT |
| Runtime Knowledge | RT_KNOWLEDGE |

---

# Next Document

➡ **24-AI-Metadata.md**

The next chapter defines the AI Repository, including AI providers, models, prompt templates, agents, reasoning pipelines, tool contracts, memory, orchestration, and metadata-driven AI capabilities integrated throughout the ODAF platform.