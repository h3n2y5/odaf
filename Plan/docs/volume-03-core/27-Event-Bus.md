---
document_id: CORE-V3-027
title: Event Bus
volume: Volume 3 – ODAF Core Implementation
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - CORE-V3-012
  - CORE-V3-015
  - CORE-V3-016
  - CORE-V3-021
  - CORE-V3-026
---

# Chapter 27

# Event Bus

---

# 1. Purpose

This chapter defines the Event Bus of the Oracle Dynamic Application Framework (ODAF).

The Event Bus executes compiler-generated Event Graphs that coordinate deterministic event publication, routing, delivery, persistence, replay, and subscription across the platform.

Rather than acting as a generic messaging broker, the Event Bus executes immutable Delivery Plans generated during compilation.

The Event Bus provides technology-independent event orchestration for platform-wide communication.

---

# 2. Design Objectives

The Event Bus SHALL:

- execute Event Graphs;
- support deterministic event routing;
- support ordered event delivery;
- support replayable event streams;
- support multiple transport implementations;
- support configurable Quality of Service;
- remain implementation independent;
- expose event metrics.

---

# 3. Event Bus Architecture

```text
Business Event
        │
        ▼
Event Bus
        │
        ├── Event Planner
        ├── Topic Resolver
        ├── Routing Engine
        ├── Delivery Manager
        ├── Replay Manager
        ├── Persistence Manager
        ├── QoS Manager
        ├── Transport Adapter
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Subscribers
```

The Event Bus SHALL execute compiler-generated Event Graphs only.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Event Delivery
```

An Event Delivery represents one execution instance of a compiled Event Graph.

---

# 5. Event Meta Model

```text
Event Delivery

│

├── Event Graph

├── Delivery Plan

├── Topic Graph

├── Routing Policy

├── Ordering Policy

├── Replay Policy

├── QoS Policy

├── Transport Adapter

├── Metrics

├── Diagnostics

└── Delivery Result
```

---

# 6. Event Graph

The compiler SHALL generate immutable Event Graphs.

Typical node types include:

- Publisher;
- Event;
- Topic;
- Routing;
- Persistence;
- Replay;
- Delivery;
- Subscriber;
- Completion.

Event Graphs SHALL remain immutable during execution.

---

# 7. Event Planner

The Event Planner SHALL generate a Delivery Plan.

Planning activities MAY include:

- topic resolution;
- routing optimization;
- subscriber ordering;
- persistence planning;
- replay planning;
- transport selection.

Delivery Plans SHALL remain deterministic.

---

# 8. Topic Resolution

Topics SHALL be compiler-generated.

Examples:

```text
business.purchase.created

business.purchase.approved

business.invoice.paid

security.authentication.failed
```

Topic naming SHALL follow metadata-defined conventions.

---

# 9. Routing Engine

The Routing Engine SHALL support:

- direct routing;
- topic routing;
- fan-out routing;
- multicast routing;
- selective routing;
- conditional routing.

Routing SHALL remain deterministic.

---

# 10. Delivery Semantics

Supported Quality of Service policies MAY include:

| QoS | Description |
|------|-------------|
| At Most Once | Best-effort delivery |
| At Least Once | Guaranteed delivery with possible duplicates |
| Exactly Once | Single successful delivery |

QoS SHALL be compiler-generated.

---

# 11. Replay and Persistence

The Event Bus SHALL support:

- event persistence;
- replay;
- subscriber recovery;
- historical replay;
- checkpoint replay.

Replay SHALL preserve event ordering.

---

# 12. Transport Adapters

Event transport SHALL occur through Transport Adapters.

Supported adapters MAY include:

| Transport | Description |
|-----------|-------------|
| Kafka | Distributed event streaming |
| RabbitMQ | Message broker |
| JMS | Java Messaging Service |
| AMQP | Messaging protocol |
| MQTT | Lightweight messaging |
| HTTP | Event over HTTP |
| In-Memory | Local runtime |
| Plugin | Custom transport |

The Event Bus SHALL remain independent from transport implementations.

---

# 13. Event Pipeline

Event execution SHALL follow this sequence.

```text
Business Event

↓

Event Planner

↓

Topic Resolution

↓

Routing

↓

Persistence

↓

Delivery

↓

Subscribers
```

Execution SHALL remain deterministic.

---

# 14. Runtime Metrics

The Event Bus SHALL collect:

- publication latency;
- routing duration;
- delivery latency;
- replay duration;
- subscriber count;
- transport utilization;
- QoS success rate.

Metrics SHALL support operational monitoring.

---

# 15. Diagnostics

The Event Bus SHALL generate diagnostics for:

- routing failures;
- delivery failures;
- replay failures;
- transport failures;
- subscriber failures;
- QoS violations.

Diagnostics SHALL remain traceable.

---

# 16. Constraints

| ID | Constraint |
|----|------------|
| EVT-001 | Event Bus SHALL execute immutable Event Graphs |
| EVT-002 | Topics SHALL be compiler-generated |
| EVT-003 | Delivery SHALL follow compiler-generated QoS policies |
| EVT-004 | Replay SHALL preserve ordering |
| EVT-005 | Runtime SHALL NOT modify Event Graphs |

---

# 17. Relationships

```text
Business Event

produces

Event Graph

planned by

Event Planner

resolved by

Topic Resolver

executed by

Routing Engine

transported through

Transport Adapter

consumed by

Subscribers
```

---

# 18. Traceability

```text
Business Event

↓

Event Graph

↓

Event Delivery

↓

Delivery Result

↓

Audit
```

Every Event Delivery SHALL remain traceable to the originating business event, compiler build, delivery plan, transport adapter, and subscriber execution.

---

# 19. Risks

Potential risks include:

- routing loops;
- duplicate delivery;
- transport failures;
- replay inconsistency;
- subscriber incompatibility.

These risks SHALL be mitigated through compiler validation, immutable Event Graphs, deterministic routing, ordered replay, QoS enforcement, and runtime diagnostics.

---

# 20. Summary

The Event Bus provides deterministic, technology-independent event distribution across the ODAF platform.

By executing immutable compiler-generated Event Graphs through planning, topic resolution, routing, persistence, replay, QoS management, and transport adapters, the Event Bus separates event semantics from messaging technology.

This architecture enables scalable event-driven execution, replayable event streams, deterministic delivery, and complete traceability while preserving the principles of a Compiler-Driven Metadata Platform.

---

# Event Bus Overview

```text
Business Event
        │
        ▼
Event Bus
        ├── Event Planner
        ├── Topic Resolver
        ├── Routing Engine
        ├── Delivery Manager
        ├── Replay Manager
        ├── Persistence Manager
        ├── QoS Manager
        ├── Transport Adapter
        ├── Metrics Collector
        └── Diagnostics
                │
                ▼
Subscribers
```

---

# Planned Core Components

| Component | Responsibility |
|-----------|----------------|
| EVT_PLANNER | Event planning |
| EVT_TOPIC | Topic resolution |
| EVT_ROUTER | Event routing |
| EVT_DELIVERY | Delivery management |
| EVT_REPLAY | Event replay |
| EVT_PERSIST | Event persistence |
| EVT_QOS | Quality of Service management |
| EVT_ADAPTER | Transport adapter abstraction |
| EVT_METRICS | Event metrics |
| EVT_DIAGNOSTICS | Event diagnostics |
| EVT_RESULT | Event delivery result |

---

# Next Document

➡ **28-Appendix.md**

The appendix contains terminology, glossary, architectural patterns, reference diagrams, naming conventions, and implementation notes supporting the Core Implementation architecture.