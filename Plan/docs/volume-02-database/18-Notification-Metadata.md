---
document_id: DB-V2-018
title: Notification Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-017
  - DB-V2-019
  - DB-V2-015
  - DB-V2-010
---

# Chapter 18

# Notification Metadata

---

# 1. Purpose

This chapter defines the Notification Repository of the Oracle Dynamic Application Framework (ODAF).

Notifications represent metadata-driven delivery of business events.

The Notification Repository separates:

- event generation;
- recipient resolution;
- message rendering;
- channel delivery;
- delivery monitoring.

Notification SHALL remain independent of delivery technology.

---

# 2. Design Objectives

Notification Metadata SHALL:

- support event-driven messaging;
- support multiple delivery channels;
- support reusable templates;
- support localization;
- support retry strategies;
- support subscriptions;
- support AI-assisted messaging;
- remain metadata-driven.

---

# 3. Notification Architecture

```text
Business Event

↓

Notification

↓

Recipient Resolver

↓

Template

↓

Renderer

↓

Channel

↓

Delivery

↓

Audit
```

Business processes SHALL publish events instead of invoking delivery mechanisms directly.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Notification
```

Every Notification belongs to exactly one Feature.

---

# 5. Notification Meta Model

```text
Notification

│

├── Event

├── Subscription

├── Recipient Rule

├── Template

├── Renderer

├── Channel

├── Retry Policy

├── Delivery Policy

├── Localization

└── Runtime Mapping
```

---

# 6. Notification

A Notification defines a reusable messaging definition.

Typical attributes include:

| Attribute | Description |
|------------|------------|
| OBJECT_ID | Platform identifier |
| OBJECT_CODE | Business code |
| OBJECT_NAME | Notification name |
| VERSION | Metadata version |
| STATUS | Lifecycle |
| DEFAULT_CHANNEL | Preferred delivery channel |

---

# 7. Events

Notifications SHALL subscribe to business events.

Examples include:

- Purchase Approved
- Invoice Posted
- Workflow Completed
- Report Generated
- Login Failed
- Deployment Completed
- Dataset Refreshed

Events SHALL remain independent from delivery channels.

---

# 8. Subscription

Subscriptions connect Events to Notifications.

```text
Business Event

↓

Subscription

↓

Notification
```

Multiple subscriptions MAY exist for the same event.

---

# 9. Recipient Resolution

Recipients SHALL be resolved dynamically.

Supported recipient strategies include:

- Current User
- Document Owner
- Supervisor
- Role Members
- Organization
- Distribution List
- External Address
- API Consumer

Recipient resolution SHALL occur at runtime.

---

# 10. Templates

Templates define message content.

Supported template sections include:

- Subject
- Header
- Body
- Footer
- Attachment Definition

Templates SHALL support parameter substitution.

---

# 11. Localization

Notification templates SHALL support multiple languages.

Localization SHALL include:

- subject;
- body;
- formatting;
- date;
- number.

Language selection SHALL occur during rendering.

---

# 12. Renderers

Renderers transform templates into channel-specific messages.

Supported renderers include:

| Renderer | Output |
|----------|--------|
| HTML | Email |
| TEXT | SMS |
| MARKDOWN | Chat |
| JSON | API |
| ADAPTIVE_CARD | Teams |
| AI | AI Summary |
| FUTURE | Plugin Renderer |

Renderers SHALL remain independent from delivery channels.

---

# 13. Channels

Supported channels include:

- Email
- SMS
- Push Notification
- Microsoft Teams
- Slack
- WhatsApp
- Telegram
- REST API
- Webhook
- Kafka
- RabbitMQ
- Future Plugin Channel

Channels SHALL implement a common delivery contract.

---

# 14. Retry Policy

Delivery MAY define retry strategies.

Examples:

- immediate retry;
- exponential backoff;
- scheduled retry;
- dead-letter queue.

Retry SHALL be metadata-defined.

---

# 15. Delivery Policy

Delivery policies SHALL define:

- priority;
- expiration;
- batching;
- throttling;
- acknowledgment requirements.

Delivery SHALL remain deterministic.

---

# 16. AI Notification

Notifications MAY request AI-generated summaries.

Examples:

- executive summary;
- anomaly explanation;
- recommendation;
- natural language digest.

AI-generated content SHALL supplement the original notification.

---

# 17. Notification Pipeline

Runtime SHALL execute the following pipeline.

```text
Business Event

↓

Subscription Resolution

↓

Recipient Resolution

↓

Template Rendering

↓

Channel Delivery

↓

Retry Management

↓

Audit
```

Every stage SHALL be auditable.

---

# 18. Runtime Mapping

Compilation transforms

```text
NTF_NOTIFICATION

↓

Compiler

↓

RT_NOTIFICATION

↓

Notification Engine
```

Notification identity SHALL be preserved.

---

# 19. Constraints

| ID | Constraint |
|-----|------------|
| NTF-001 | Every Notification belongs to one Feature |
| NTF-002 | Events SHALL remain channel independent |
| NTF-003 | Channels SHALL implement the delivery contract |
| NTF-004 | Templates SHALL support localization |
| NTF-005 | Retry policies SHALL be metadata-defined |

---

# 20. Relationships

```text
Feature

owns

Notification

owns

Subscription

owns

Template

owns

Retry Policy

references

Workflow

references

Security

references

Report

references

Dataset
```

---

# 21. Traceability

```text
Business Event

↓

Notification

↓

Renderer

↓

Channel

↓

Delivery

↓

Audit Event
```

Every delivered notification SHALL be traceable to its originating business event.

---

# 22. Risks

Potential risks include:

- duplicate subscriptions;
- delivery failures;
- excessive retries;
- template inconsistencies;
- unsupported channels;
- notification storms.

These risks SHALL be mitigated through compiler validation, delivery monitoring, retry policies, throttling, and governance.

---

# 23. Summary

The Notification Repository defines a metadata-driven messaging architecture that decouples business events from delivery technologies.

By organizing notifications into events, subscriptions, recipient rules, templates, renderers, channels, retry policies, and delivery pipelines, ODAF supports scalable enterprise messaging across email, chat, APIs, message brokers, mobile platforms, and future communication channels.

This architecture enables reliable, auditable, extensible, and technology-independent notification services.

---

# Notification Repository Model

```text
Notification
        │
        ├── Event
        ├── Subscription
        ├── Recipient Rule
        ├── Template
        ├── Renderer
        ├── Channel
        ├── Retry Policy
        ├── Delivery Policy
        ├── Localization
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Notification | NTF_NOTIFICATION |
| Event | NTF_EVENT |
| Subscription | NTF_SUBSCRIPTION |
| Recipient Rule | NTF_RECIPIENT_RULE |
| Template | NTF_TEMPLATE |
| Channel | NTF_CHANNEL |
| Renderer | NTF_RENDERER |
| Retry Policy | NTF_RETRY_POLICY |
| Delivery Policy | NTF_DELIVERY_POLICY |
| Runtime Notification | RT_NOTIFICATION |

---

# Next Document

➡ **19-Integration-Metadata.md**

The next chapter defines the Integration Repository, including external systems, APIs, adapters, connectors, mappings, message contracts, synchronization policies, and metadata-driven enterprise integration.