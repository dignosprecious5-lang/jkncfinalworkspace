# Transmittal V2 isolated MySQL verification plan

**Status:** Prepared for a disposable MySQL test instance; not executed. The
existing local MySQL service was not contacted.

## Environment gate

Before running database tests, provision an independently verified disposable
MySQL instance and database. It must be loopback-only, contain no business data,
have no mounted production files or credentials, use a test-only database user
restricted to one uniquely named test database, and be removable as a whole.
Verify the actual server version, InnoDB table engines, empty database, and
schema name through that isolated instance. Never point this test profile at the
existing `MySQL96` service. Do not place credentials in source control or inspect
the application `.env`.

## Synthetic fixture and acceptance cases

1. Create synthetic V1-shaped parent/item/receipt tables and sentinel rows with
   attachment path strings. Apply only separately approved candidate migrations
   to a disposable schema. Assert every sentinel value, identifier, receipt
   number, item row, and attachment path remains byte-for-byte unchanged.
2. Confirm V2 workspace items are stored only in
   `transmittal_workspace_items`; legacy `transmittal_items` schema and rows do
   not change.
3. Insert many V2 receipt numbers concurrently. Assert unique `receipt_no`
   values and exactly one receipt per recipient. Exercise unique-index collision
   failure and verify the surrounding transaction leaves no partial audit rows.
4. Submit two acknowledgments for the same synthetic recipient using two MySQL
   connections. Assert the parent/recipient row locks serialize writes and only
   one receipt, acknowledgment event, proof update record, and history record
   commit.
5. Force a transaction exception after receipt creation and before audit writes.
   Assert complete rollback and that a retry can record one coherent acknowledgment.
6. Attempt parent deletes referenced by each V2 table. Assert restrictive
   foreign keys reject the delete and all business/audit rows remain.
7. Exercise the existing file-backed fake disk around an acknowledgment failure.
   Assert the flow never deletes or rewrites V1 attachment files or V2 document
   files.
8. Check `transmittal_receipts.receipt_no` retains its original unique index and
   all legacy values. Verify nullable `recipient_id` uniqueness allows multiple
   legacy NULL rows while preventing duplicate non-NULL recipient IDs.

## Review limitations

The candidate files under `migrations/` are outside Laravel's automatic
migration discovery and have no destructive `down()` implementation. Existing
tables or columns cause a stop for reconciliation rather than a silent skip.
MySQL DDL may not be transactional; apply one candidate at a time only after
reviewing the isolated schema and recovery plan. No concurrency, foreign-key,
rollback, or preservation behavior is marked verified until this plan runs on
the disposable MySQL instance.
