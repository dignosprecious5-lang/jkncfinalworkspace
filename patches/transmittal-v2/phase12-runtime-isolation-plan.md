# Phase 12 Laravel and MySQL Runtime Isolation Plan

Status: proposal only. No application runtime or database was started for Phase 12. The current host has not been proven to isolate PHP from existing business data or network resources.

## Gate 0: prove the disposable boundary

Use a disposable VM or container only after its isolation is independently verified. The test environment must:

- Run with network access disabled at the VM/hypervisor or container network layer; no bridge, DNS, external route, or access to production.
- Have no host home/profile, `.env`, credential store, SSH keys, browser profile, backup directory, protected original checkout, or business database files mounted.
- Deny UNC/SMB access and prevent the test process from reaching host services, including the existing MySQL96 service.
- Receive only a disposable copy of the integration candidate and synthetic fixtures. Store cache, session, logs, compiled views, uploads, and database files on disposable storage.
- Use test-only encryption/session keys and fake mail, OCR, and external-service adapters. Never use production tokens or credentials.

If any boundary cannot be demonstrated independently, do not run Laravel or MySQL tests. PHP `open_basedir`, Laravel config, or a separate schema on the existing MySQL service is not a complete OS/network isolation boundary.

## Laravel HTTP runtime profile

After the OS boundary is proven, run only the isolated PHPUnit configuration with `transmittal_v2.enabled=false` by default. Use in-memory SQLite for middleware, CSRF, feature-gate, and basic HTTP contract checks where the schema permits. Use synthetic users/records only; fake storage, mail, OCR/process execution, cache, and sessions. Disable outbound network at OS level, not only through app configuration. A test bootstrap must reject environment files and any database driver/name outside its explicit allowlist.

SQLite results do not establish MySQL column, collation, locking, transaction, or concurrent receipt-number behavior.

## Disposable MySQL profile (currently blocked)

Before any MySQL execution, establish the exact required server version from an approved non-secret project record. That version is not yet verified in this plan. Then create a separate ephemeral MySQL instance with a dedicated empty data directory, isolated credentials, and a private socket/port inside the network-isolated environment. Do not stop, reuse, or connect to the existing MySQL96 service or its files. Verify the server process and data directory belong only to this disposable environment before creating synthetic schemas.

Run only the additive V2 migration candidates and synthetic checks for V1 row preservation, V2 workspace-item isolation, restrictive foreign keys, receipt uniqueness, duplicate acknowledgments, rollback, row-lock/concurrency behavior, and file retention on uncertain outcomes. Destroy only the disposable VM/container after evidence is captured; do not use cleanup commands on host or business paths.

## Required evidence before test execution

1. Independent confirmation of VM/container identity, no-network mode, no host credential/data mounts, and disabled UNC/SMB access.
2. Exact MySQL version requirement plus disposable instance binary/version, data directory, and private endpoint evidence.
3. Review of the test-only environment allowlist, temporary paths, and feature-flag default.
4. Explicit approval for creating and later removing the disposable test environment. This plan itself does not authorize setup or execution.
