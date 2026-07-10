---
document_id: SAD-V1-019
title: References
volume: Volume 1 – Software Architecture Document
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - SAD-V1-018
  - SAD-V1-020
---

# Chapter 19
# References

---

# 1. Purpose

This chapter identifies the normative and informative references supporting the Oracle Dynamic Application Framework (ODAF).

These references provide the conceptual, architectural, methodological, and technological foundations upon which the ODAF Specification is based.

Normative references define standards that SHALL be considered authoritative.

Informative references provide guidance and background information.

---

# 2. Normative References

The following references are normative.

Implementations SHALL conform to applicable requirements derived from these standards.

| ID | Reference |
|----|-----------|
| NR-001 | ISO/IEC/IEEE 42010 — Systems and Software Engineering — Architecture Description |
| NR-002 | ISO/IEC 25010 — Systems and Software Quality Models |
| NR-003 | RFC 2119 — Key Words for Use in RFCs to Indicate Requirement Levels |
| NR-004 | Semantic Versioning 2.0.0 |
| NR-005 | Unicode UTF-8 Standard |
| NR-006 | ISO 8601 — Date and Time Representation |

---

# 3. Informative References

The following publications influenced the architecture but are not mandatory.

| ID | Reference |
|----|-----------|
| IR-001 | arc42 Architecture Documentation |
| IR-002 | C4 Model for Software Architecture |
| IR-003 | Domain-Driven Design (Eric Evans) |
| IR-004 | Clean Architecture (Robert C. Martin) |
| IR-005 | Implementing Domain-Driven Design (Vaughn Vernon) |
| IR-006 | Enterprise Integration Patterns (Gregor Hohpe, Bobby Woolf) |
| IR-007 | Patterns of Enterprise Application Architecture (Martin Fowler) |

---

# 4. Architecture Methodologies

ODAF adopts concepts from several architecture methodologies.

| Methodology | Purpose |
|--------------|----------|
| TOGAF | Enterprise Architecture |
| arc42 | Documentation Structure |
| C4 Model | Architecture Visualization |
| ATAM | Quality Attribute Analysis |
| ADR | Architecture Decision Recording |

These methodologies complement one another.

---

# 5. Software Architecture Patterns

The platform incorporates recognized architectural patterns.

| Pattern | Purpose |
|----------|---------|
| Layered Architecture | Separation of responsibilities |
| Microkernel Architecture | Runtime extensibility |
| Plugin Architecture | Extension model |
| Repository Pattern | Metadata persistence |
| Dependency Injection | Loose coupling |
| Event-Driven Architecture | Runtime communication |
| Factory Pattern | Object creation |
| Strategy Pattern | Replaceable algorithms |
| Adapter Pattern | External integration |
| Observer Pattern | Event notification |

Pattern selection SHALL be documented through ADR where appropriate.

---

# 6. Database References

The Oracle metadata repository is based upon established Oracle capabilities.

Relevant references include:

- Oracle Database Concepts
- Oracle Database Administrator's Guide
- Oracle SQL Language Reference
- Oracle PL/SQL Language Reference
- Oracle Performance Tuning Guide
- Oracle Security Guide
- Oracle Data Dictionary Reference

These documents guide the database implementation described in Volume 2.

---

# 7. Security References

The ODAF security model is influenced by recognized security standards.

| Standard | Purpose |
|----------|---------|
| OWASP ASVS | Application Security Verification |
| OWASP Top 10 | Security Risk Awareness |
| OAuth 2.0 | Authorization Framework |
| OpenID Connect | Authentication |
| NIST Cybersecurity Framework | Security Governance |

Security implementation SHALL be described in Volume 3.

---

# 8. Cloud and Infrastructure References

Cloud deployment concepts are influenced by:

- Kubernetes
- Docker
- OCI Containers
- Twelve-Factor App Methodology
- Cloud Native Computing Foundation (CNCF)

These references support future deployment strategies.

---

# 9. Enterprise Integration References

Integration architecture follows established enterprise integration principles.

References include:

- Enterprise Integration Patterns
- REST Architectural Style
- SOAP Web Services
- OpenAPI Specification
- AsyncAPI Specification

---

# 10. Documentation Standards

Documentation SHALL follow:

- Markdown
- Mermaid
- Semantic Versioning
- Architecture Decision Records
- RFC 2119 terminology

Documentation SHALL remain version controlled.

---

# 11. Testing References

The ODAF verification model is influenced by:

- ISTQB Glossary
- ISO/IEC/IEEE 29119 Software Testing
- Architecture Tradeoff Analysis Method (ATAM)
- Test Pyramid

Testing guidance is further specified in Volume 3.

---

# 12. Modeling References

Modeling concepts include:

- UML 2.x
- Mermaid
- C4 Model
- Entity Relationship Diagram (ERD)
- BPMN (future support)

Model selection depends upon the intended audience and purpose.

---

# 13. Metadata References

The metadata architecture is influenced by concepts from:

- Oracle Application Express (Oracle APEX)
- Eclipse Modeling Framework (EMF)
- Meta-Object Facility (MOF)
- Model-Driven Architecture (MDA)
- Model-Driven Engineering (MDE)

ODAF extends these concepts through a compiled metadata runtime.

---

# 14. Artificial Intelligence References

Future platform capabilities may adopt concepts from:

- AI-assisted Software Engineering
- Large Language Models (LLM)
- Retrieval-Augmented Generation (RAG)
- Knowledge Graphs
- Intelligent Code Generation

AI SHALL support, but SHALL NOT replace, architectural governance.

---

# 15. Internal References

The following ODAF documents are normative within the platform.

| Document | Description |
|----------|-------------|
| Volume 1 | Software Architecture Document |
| Volume 2 | Oracle Metadata & Database Design |
| Volume 3 | ODAF Core Implementation |
| Volume 4 | ODAF Studio |
| Volume 5 | Sample ERP |

All subsequent volumes SHALL reference Volume 1 where architectural guidance is required.

---

# 16. Reference Governance

The Architecture Board SHALL maintain the reference catalog.

Responsibilities include:

- adding new references;
- retiring obsolete references;
- validating external standards;
- ensuring consistency across documentation.

References SHALL be reviewed periodically.

---

# 17. Future References

The reference catalog is expected to evolve.

Future additions MAY include:

- AI governance standards;
- cloud-native architecture standards;
- enterprise metadata standards;
- digital twin architecture;
- knowledge graph standards.

The reference catalog SHALL remain aligned with platform evolution.

---

# 18. Summary

The references documented in this chapter establish the intellectual and technical foundation of ODAF.

By aligning with internationally recognized standards, architectural methodologies, software engineering practices, and enterprise technologies, ODAF ensures that its architecture remains consistent, maintainable, interoperable, and suitable for long-term enterprise adoption.

These references SHALL guide the interpretation, implementation, and future evolution of the ODAF Specification.

---

# Next Document

➡ **20-Appendix.md**

The final chapter contains the glossary, abbreviations, document identifiers, requirement catalogs, traceability indexes, document templates, and supplementary information supporting the ODAF Software Architecture Document.