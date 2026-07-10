---
document_id: DB-V2-019
title: Integration Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-018
  - DB-V2-020
  - DB-V2-014
  - DB-V2-015
---

# Chapter 19

# Integration Metadata

---

# 1. Purpose

This chapter defines the Integration Repository of the Oracle Dynamic Application Framework (ODAF).

The Integration Repository provides a metadata-driven abstraction for communication between ODAF and external systems.

Business capabilities SHALL interact with logical integration contracts rather than transport protocols or implementation technologies.

---

# 2. Design Objectives

Integration Metadata SHALL:

- separate business capabilities from external technologies;
- support multiple integration styles;
- provide reusable contracts;
- support transformation and mapping;
- support resilience patterns;
- enable secure enterprise integration;
- remain metadata-driven.

---

# 3. Integration Architecture

```text
Business Capability

↓

Integration Contract

↓

Operation

↓

Transformation

↓

Adapter

↓

Transport

↓

External System
```

The architecture SHALL isolate business logic from transport-specific implementations.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Integration Contract
```

Each Integration Contract belongs to exactly one Feature.

---

# 5. Integration Meta Model

```text
Integration Contract

│

├── Operation

├── Request Schema

├── Response Schema

├── Mapping

├── Adapter

├── Transport

├── Security

├── Retry Policy

├── Circuit Breaker

└── Runtime Mapping
```

---

# 6. Integration Contract

An Integration Contract defines a logical service exposed or consumed by ODAF.

Examples:

- Customer Service
- Payment Gateway
- Inventory Service
- HR Service
- AI Service

Contracts SHALL remain independent of transport protocols.

---

# 7. Operations

Operations represent callable business functions.

Examples:

- Get Customer
- Create Purchase Order
- Reserve Inventory
- Cancel Shipment
- Generate AI Summary

Operations SHALL define request and response schemas.

---

# 8. Request and Response Schema

Schemas describe exchanged information.

Each schema SHALL define:

- attributes;
- data types;
- required fields;
- constraints;
- version.

Consumers SHALL depend on schemas rather than transport payloads.

---

# 9. Mapping

Mappings transform data between internal and external representations.

Examples:

- Object → JSON
- JSON → Dataset
- XML → Dataset
- Oracle Cursor → Dataset
- Dataset → CSV

Mappings SHALL be reusable.

---

# 10. Adapters

Supported adapters include:

| Adapter | Purpose |
|----------|---------|
| REST | REST services |
| SOAP | SOAP services |
| DATABASE | Database access |
| DB_LINK | Oracle Database Link |
| PLSQL | Oracle Package |
| KAFKA | Kafka messaging |
| RABBITMQ | RabbitMQ |
| SFTP | File transfer |
| WEBHOOK | Webhook |
| EMAIL | SMTP |
| AI | AI Provider |
| PLUGIN | Future extensions |

Adapters SHALL implement a common integration interface.

---

# 11. Transport

Transport defines communication mechanisms.

Examples:

- HTTP
- HTTPS
- TCP
- JDBC
- Oracle Net
- Message Queue

Transport SHALL remain hidden from business capabilities.

---

# 12. Security

Integration SHALL support:

- authentication;
- authorization;
- API keys;
- OAuth2;
- mutual TLS;
- digital signatures;
- encryption.

Security SHALL integrate with the Security Repository.

---

# 13. Retry Policy

Retry strategies MAY include:

- immediate retry;
- exponential backoff;
- scheduled retry;
- dead-letter queue.

Retry SHALL be configurable through metadata.

---

# 14. Circuit Breaker

Integration MAY define resilience policies.

Supported states include:

```text
Closed

↓

Open

↓

Half Open

↓

Closed
```

Circuit Breaker behavior SHALL be metadata-defined.

---

# 15. AI Providers

AI providers SHALL be modeled as Integration Adapters.

Examples include:

- OpenAI-compatible API;
- local LLM;
- enterprise AI gateway;
- future AI providers.

Prompt templates and response mappings SHALL be stored as metadata.

---

# 16. Runtime Pipeline

The Runtime SHALL execute the following pipeline.

```text
Integration Request

↓

Validation

↓

Mapping

↓

Security

↓

Adapter

↓

Transport

↓

Retry

↓

Circuit Breaker

↓

Response Mapping

↓

Audit
```

Every stage SHALL be observable and traceable.

---

# 17. Runtime Mapping

Compilation transforms

```text
INT_CONTRACT

↓

Compiler

↓

RT_INTEGRATION

↓

Integration Engine
```

Logical identity SHALL be preserved.

---

# 18. Constraints

| ID | Constraint |
|----|------------|
| INT-001 | Every Integration Contract belongs to one Feature |
| INT-002 | Every Operation defines request and response schemas |
| INT-003 | Adapters SHALL implement the integration contract |
| INT-004 | Business capabilities SHALL NOT reference transport directly |
| INT-005 | Retry and security policies SHALL be metadata-defined |

---

# 19. Relationships

```text
Feature

owns

Integration Contract

owns

Operation

owns

Mapping

references

Dataset

references

Notification

references

Workflow

references

Security
```

---

# 20. Traceability

```text
Business Capability

↓

Integration Contract

↓

Operation

↓

Adapter

↓

Transport

↓

External System

↓

Audit Event
```

Every integration execution SHALL be traceable.

---

# 21. Risks

Potential risks include:

- unstable external systems;
- incompatible schema evolution;
- transport failures;
- security vulnerabilities;
- mapping inconsistencies;
- excessive coupling.

These risks SHALL be mitigated through contracts, adapters, resilience patterns, validation, and governance.

---

# 22. Summary

The Integration Repository establishes a metadata-driven enterprise integration architecture.

By separating business contracts, operations, mappings, adapters, transports, and resilience policies, ODAF enables integration with databases, APIs, messaging systems, AI providers, and future technologies without exposing implementation details to business capabilities.

This architecture provides a stable, extensible, and technology-independent foundation for enterprise connectivity.

---

# Integration Repository Model

```text
Integration Contract
        │
        ├── Operation
        ├── Request Schema
        ├── Response Schema
        ├── Mapping
        ├── Adapter
        ├── Transport
        ├── Security
        ├── Retry Policy
        ├── Circuit Breaker
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Integration Contract | INT_CONTRACT |
| Integration Operation | INT_OPERATION |
| Request Schema | INT_REQUEST_SCHEMA |
| Response Schema | INT_RESPONSE_SCHEMA |
| Mapping | INT_MAPPING |
| Adapter | INT_ADAPTER |
| Transport | INT_TRANSPORT |
| Retry Policy | INT_RETRY_POLICY |
| Circuit Breaker | INT_CIRCUIT_BREAKER |
| Runtime Integration | RT_INTEGRATION |

---

# Next Document

➡ **20-Deployment-Metadata.md**

The next chapter defines the Deployment Repository, including deployment packages, manifests, environments, release pipelines, promotion policies, rollback strategies, runtime activation, and metadata-driven deployment across the ODAF platform.