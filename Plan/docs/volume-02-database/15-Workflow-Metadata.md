---
document_id: DB-V2-015
title: Workflow Metadata
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public
created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-014
  - DB-V2-016
  - DB-V2-005
---

# Chapter 15

# Workflow Metadata

---

# 1. Purpose

This chapter defines the Workflow Repository of the Oracle Dynamic Application Framework (ODAF).

The Workflow Repository describes business processes through metadata.

Workflow definitions SHALL remain independent from implementation technology.

The Runtime Engine SHALL execute compiled workflow metadata.

---

# 2. Design Objectives

Workflow Metadata SHALL:

- represent business processes;
- support event-driven execution;
- support long-running processes;
- support approvals;
- support timers;
- support orchestration;
- support compensation;
- remain metadata-driven.

---

# 3. Workflow Architecture

```text
Business Capability

↓

Workflow Definition

↓

Activity Graph

↓

Transition Graph

↓

Runtime Instance

↓

History
```

Workflow Definitions SHALL be immutable after deployment.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Workflow Definition
```

Every Workflow belongs to exactly one Feature.

---

# 5. Workflow Meta Model

```text
Workflow

│

├── Activity

├── Transition

├── Event

├── Action

├── Variable

├── Timer

├── Escalation

├── Compensation

└── Runtime Mapping
```

---

# 6. Workflow Definition

Workflow Definition describes the complete business process.

Typical attributes include:

| Attribute | Description |
|------------|------------|
| OBJECT_ID | Identifier |
| OBJECT_CODE | Business Code |
| OBJECT_NAME | Name |
| VERSION | Version |
| STATUS | Lifecycle |
| START_EVENT | Initial Event |

---

# 7. Activity

An Activity represents a unit of work.

Examples

- User Task
- Service Task
- Script Task
- Approval Task
- Notification Task
- Decision Task
- Timer Task
- AI Task

Activities SHALL be reusable.

---

# 8. Transition

Transitions connect Activities.

```text
Activity

↓

Transition

↓

Activity
```

Transitions MAY contain conditions.

---

# 9. Events

Supported Events include

- Start
- End
- Timer
- Message
- Signal
- Dataset Changed
- API Call
- Scheduler
- Webhook
- AI Event

Events initiate or continue workflow execution.

---

# 10. Variables

Workflow Variables SHALL define execution context.

Examples

- Company
- User
- Document Number
- Amount
- Currency
- Approval Level

Variables SHALL be strongly typed.

---

# 11. Actions

Activities MAY execute Actions.

Examples

- Update Dataset
- Send Email
- Call REST API
- Execute Dataset
- Invoke Workflow
- Generate Report
- Publish Event
- Execute AI Agent

Actions SHALL reference metadata.

---

# 12. Decision Model

Workflow decisions SHALL be metadata-driven.

Supported decision strategies include

- Expression
- Rule Table
- Decision Tree
- Script
- AI Decision (future)

Decision logic SHALL remain externalized from runtime code.

---

# 13. Timer Model

Timers SHALL support:

- delayed execution;
- deadline monitoring;
- escalation;
- recurring execution;
- timeout handling.

---

# 14. Escalation

Escalation policies MAY include:

- reminder;
- reassignment;
- supervisor approval;
- automatic rejection;
- timeout completion.

Escalation SHALL be metadata-defined.

---

# 15. Compensation

Workflow SHALL support compensation.

Examples

- Reverse Approval
- Cancel Reservation
- Rollback Inventory
- Undo Allocation

Compensation SHALL be explicitly modeled.

---

# 16. Workflow Graph

Workflow SHALL be represented as a Directed Graph.

```text
Start

↓

Validate

↓

Decision

↙      ↘

Approve Reject

↘      ↙

Archive

↓

End
```

The compiler SHALL validate graph integrity.

---

# 17. Runtime Mapping

Compilation transforms

```text
WF_WORKFLOW

↓

Compiler

↓

RT_WORKFLOW

↓

Workflow Engine
```

Workflow identity SHALL be preserved.

---

# 18. Workflow Instance

Runtime creates Workflow Instances.

Workflow Instance contains

- Current Activity
- Variables
- History
- Tokens
- Execution State

Instances SHALL remain independent from Workflow Definitions.

---

# 19. Constraints

| ID | Constraint |
|-----|------------|
| WF-001 | Every Workflow belongs to one Feature |
| WF-002 | Workflow Graph SHALL be connected |
| WF-003 | One Start Event minimum |
| WF-004 | End Events SHALL be reachable |
| WF-005 | Cycles SHALL be explicitly allowed |

---

# 20. Relationships

```text
Feature

owns

Workflow

owns

Activity

owns

Transition

owns

Variable

owns

Event

references

Dataset

references

Notification

references

Security
```

---

# 21. Traceability

```text
Business Capability

↓

Workflow

↓

Runtime Workflow

↓

Workflow Instance

↓

Audit
```

---

# 22. Risks

Potential risks include:

- deadlocks;
- unreachable activities;
- endless loops;
- inconsistent variables;
- missing compensations;
- invalid event routing.

Compiler validation SHALL detect these conditions before deployment.

---

# 23. Summary

The Workflow Repository defines a metadata-driven orchestration engine capable of executing enterprise business processes.

By separating Workflow Definitions from Workflow Instances and representing processes as directed graphs composed of Activities, Events, Transitions, Variables, and Actions, ODAF enables long-running, event-driven, and highly extensible workflows independent of implementation technology.

---

# Workflow Repository Model

```text
Workflow

├── Activity

├── Transition

├── Event

├── Variable

├── Action

├── Timer

├── Escalation

├── Compensation

└── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Workflow | WF_WORKFLOW |
| Activity | WF_ACTIVITY |
| Transition | WF_TRANSITION |
| Event | WF_EVENT |
| Variable | WF_VARIABLE |
| Action | WF_ACTION |
| Timer | WF_TIMER |
| Escalation | WF_ESCALATION |
| Compensation | WF_COMPENSATION |
| Workflow Instance | RT_WORKFLOW_INSTANCE |

---

# Next Document

➡ **16-Validation-Metadata.md**

The next chapter defines the Validation Repository, including validation rules, expressions, rule sets, regular expressions, business constraints, decision tables, execution policies, and metadata-driven validation throughout the ODAF platform.