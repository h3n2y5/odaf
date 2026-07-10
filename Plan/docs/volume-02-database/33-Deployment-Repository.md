---
document_id: DB-V2-033
title: Deployment Repository
volume: Volume 2 – Oracle Metadata & Database Design
version: 1.0.0-draft
status: Draft
owner: ODAF Architecture Board
classification: Public

created: 2026-07-07
last_updated: 2026-07-07

related_documents:
  - DB-V2-031
  - DB-V2-032
  - DB-V2-034
---

# Chapter 33

# Deployment Repository

---

# 1. Purpose

This chapter defines the Deployment Repository of the Oracle Dynamic Application Framework (ODAF).

The Deployment Repository stores, manages, validates, promotes, and activates compiler-generated deployment artifacts.

Rather than serving merely as a deployment log, the Deployment Repository functions as the software supply chain for metadata-driven applications.

---

# 2. Design Objectives

The Deployment Repository SHALL:

- store immutable deployment artifacts;
- support deterministic deployment;
- preserve deployment traceability;
- support environment promotion;
- maintain deployment history;
- enable rollback;
- support artifact verification.

---

# 3. Deployment Architecture

```text
Metadata

↓

Compiler

↓

Build Artifact

↓

Deployment Repository

↓

Promotion

↓

Activation

↓

Runtime
```

Deployment SHALL operate only on compiler-generated artifacts.

---

# 4. Aggregate Root

The Aggregate Root is

```text
Deployment Artifact
```

Each Deployment Artifact SHALL represent one immutable build output.

---

# 5. Deployment Meta Model

```text
Deployment Artifact

│

├── Runtime Package

├── Manifest

├── Dependency Graph

├── Signature

├── Checksum

├── SBOM

├── Promotion History

├── Activation Record

└── Runtime Mapping
```

---

# 6. Deployment Artifacts

Typical deployment artifacts include:

- Runtime Package;
- Generated Oracle DDL;
- Generated PL/SQL Packages;
- Deployment Manifest;
- Build Report;
- Documentation;
- Software Bill of Materials (SBOM).

Artifacts SHALL be immutable.

---

# 7. Manifest

Every Deployment Artifact SHALL include a Manifest.

Typical Manifest contents include:

- package identifier;
- package version;
- compiler version;
- metadata version;
- runtime version;
- dependencies;
- checksum;
- generation timestamp.

The Manifest SHALL be validated before activation.

---

# 8. Artifact Repository

Deployment Artifacts SHALL be stored in a versioned repository.

Repository capabilities include:

- artifact lookup;
- version history;
- tagging;
- dependency tracking;
- artifact retrieval.

The repository SHALL preserve historical builds.

---

# 9. Promotion Model

Deployment SHALL support controlled environment promotion.

```text
Development

↓

Integration

↓

QA

↓

UAT

↓

Staging

↓

Production
```

Promotion SHALL preserve artifact identity.

---

# 10. Deployment Verification

Before activation the platform SHALL verify:

- package integrity;
- dependency resolution;
- compiler compatibility;
- runtime compatibility;
- governance approval;
- security validation.

Verification failures SHALL prevent activation.

---

# 11. Software Bill of Materials (SBOM)

Every deployment MAY include an SBOM.

Typical contents include:

- metadata objects;
- compiler version;
- plugins;
- runtime components;
- dependencies;
- generated artifacts.

SBOM SHALL support traceability and compliance.

---

# 12. Digital Signature

Deployment Artifacts MAY be digitally signed.

Signature verification SHALL occur before activation.

Unsigned artifacts MAY be rejected according to Governance Policy.

---

# 13. Activation

Deployment activation SHALL:

- verify the artifact;
- activate the Runtime Package;
- refresh runtime caches;
- update activation history;
- generate audit events.

Activation SHALL be deterministic.

---

# 14. Rollback

Rollback SHALL reactivate a previously verified Deployment Artifact.

Rollback SHALL:

- preserve audit history;
- preserve artifact identity;
- refresh runtime caches.

Rollback SHALL NOT modify immutable artifacts.

---

# 15. Deployment Pipeline

```text
Compile

↓

Package

↓

Verify

↓

Sign

↓

Store

↓

Promote

↓

Activate

↓

Audit
```

Every pipeline stage SHALL be traceable.

---

# 16. Runtime Mapping

Compilation transforms

```text
Metadata

↓

Compiler

↓

Deployment Artifact

↓

Runtime Package

↓

Execution
```

Artifact identity SHALL remain preserved.

---

# 17. Constraints

| ID | Constraint |
|----|------------|
| DEP-001 | Deployment Artifacts SHALL be immutable |
| DEP-002 | Every Artifact SHALL include a Manifest |
| DEP-003 | Verification SHALL precede activation |
| DEP-004 | Promotion SHALL preserve artifact identity |
| DEP-005 | Rollback SHALL activate previously verified artifacts |

---

# 18. Relationships

```text
Deployment Artifact

owns

Manifest

owns

SBOM

owns

Signature

owns

Promotion History

references

Runtime Package

references

Compiler Build

references

Governance

references

Audit
```

---

# 19. Traceability

```text
Metadata

↓

Compiler

↓

Deployment Artifact

↓

Promotion

↓

Activation

↓

Runtime

↓

Audit
```

Every deployment SHALL be fully traceable.

---

# 20. Risks

Potential risks include:

- artifact corruption;
- unsigned deployments;
- dependency conflicts;
- inconsistent promotion;
- failed rollback.

These risks SHALL be mitigated through immutable artifacts, verification, digital signatures, governance approval, and automated deployment validation.

---

# 21. Summary

The Deployment Repository defines the software supply chain for ODAF.

By managing immutable deployment artifacts, manifests, signatures, promotion history, SBOMs, and activation records, ODAF provides deterministic, traceable, and auditable deployments across all managed environments.

The Deployment Repository bridges the Compiler Repository and the Runtime Repository while preserving metadata integrity throughout the application lifecycle.

---

# Deployment Repository Overview

```text
Deployment Artifact
        │
        ├── Runtime Package
        ├── Manifest
        ├── Dependency Graph
        ├── Signature
        ├── Checksum
        ├── SBOM
        ├── Promotion History
        ├── Activation Record
        └── Runtime Mapping
```

---

# Planned Oracle Objects

| Logical Object | Planned Oracle Table |
|----------------|----------------------|
| Deployment Artifact | DEP_ARTIFACT |
| Deployment Manifest | DEP_MANIFEST |
| Deployment Dependency | DEP_DEPENDENCY |
| Deployment Signature | DEP_SIGNATURE |
| Deployment SBOM | DEP_SBOM |
| Promotion History | DEP_PROMOTION |
| Activation Record | DEP_ACTIVATION |
| Deployment Registry | DEP_REGISTRY |
| Runtime Deployment | RT_DEPLOYMENT |

---

# Next Document

➡ **34-Platform-Repository.md**

The next chapter defines the Platform Repository, including platform configuration, plugin registry, compiler extensions, runtime capabilities, feature flags, licensing metadata, and platform-level services that govern the entire ODAF ecosystem.