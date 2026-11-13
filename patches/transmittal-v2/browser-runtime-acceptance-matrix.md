# V2 Browser and Laravel Runtime Acceptance Matrix

Status: preparation only. These cases have not been run. Use synthetic users, documents, recipients, and transmittals in a disposable local environment. Never connect to an existing ORDO database or production service.

| Area | Scenario | Expected result | Required environment |
|---|---|---|---|
| Feature gate | Visit each intended V2 route while `transmittal_v2.enabled` is false | Route is unavailable; no record or file data returned | Laravel HTTP runtime, isolated config |
| Guest draft access | Create and revisit a draft in the same session | Only that session's draft is visible | Laravel HTTP runtime; synthetic records |
| Guest token denial | Try invalid, expired-session, and another draft's token | 404/denial before sensitive relationships load | Laravel HTTP runtime; independent sessions |
| Role authorization | Owner, admin, superadmin, non-role user, and unrelated user exercise view/edit/approve/reject/transmit | Only policy-allowed actions succeed; no cross-record disclosure | Laravel HTTP runtime; synthetic users/records |
| CSRF and throttling | Submit state-changing forms without/with CSRF; exceed a throttle | Missing CSRF rejected; configured rate limit enforced | Laravel HTTP runtime with `web` middleware |
| File selection | Valid PDF/JPEG/PNG/TIFF/DOC inputs plus empty, unsupported, spoofed MIME, and oversized samples | Valid types follow documented limits; invalid samples fail clearly; no silent omission | Browser plus isolated HTTP runtime |
| Resumable transfer | Upload chunks, interrupt, resume, retry, duplicate a chunk, and submit an incomplete upload | Progress is truthful; retries are bounded; ownership/hash/size checks hold; incomplete upload is rejected | Browser plus isolated storage and HTTP runtime |
| OCR errors | OCR unavailable, timeout, malformed response, network interruption, and unauthorized request | Clear non-sensitive error; no automatic save; retry does not apply stale results | Browser with process double; real OCR later in separate isolation |
| Image compression | Compress supported image and compare before/after size, dimensions, and preview | New output meets limit or fails clearly; source file is never overwritten; disclose JPEG conversion/flattening | Browser; synthetic images |
| PDF compression | Compress unencrypted and encrypted PDFs; open resulting PDF and inspect pages/text | Unsupported/encrypted files fail clearly; output is accepted only if smaller and valid | Browser; synthetic PDFs; dependency approval required |
| ZIP extraction | Valid archive, invalid archive, too many entries, expanded-byte limit, path traversal names, and compression bomb | Only allowed files are queued; expansion stops at count/byte bounds; no filesystem extraction or path writes | Browser; synthetic adversarial archives; `fflate` approval required |
| Document download | Owner, authorized recipient, session-owned draft, unrelated user, and wrong document ID | Only record-scoped authorized files stream; unauthorized responses reveal no path or metadata | Laravel HTTP runtime; isolated private storage |
| Receipt PDF | Owner/admin, correct recipient token, wrong transmittal/receipt pair, and unauthorized user | Correct receipt only; mismatched or unauthorized request denied | Laravel HTTP runtime; synthetic receipts |
| Acknowledgment | Valid, expired, revoked, malformed, replayed, and cross-recipient token; duplicate POST | Scope and expiry enforced; duplicate is idempotent; CSRF/throttle apply; no unrelated record disclosure | Laravel runtime; disposable MySQL for row locks/uniqueness |
| Email and external effects | Send/transmit/acknowledge with provider unavailable or fake | No external send in tests; errors are sanitized and retry behavior is explicit | Laravel runtime with mail/provider fakes and network disabled |
| Accessibility/responsive | Keyboard-only use, visible focus, labels, live progress/error announcements, zoom, and narrow viewport | All controls reachable and understandable; no clipped dialogs or hidden validation errors | Desktop browser at 320px, tablet, and desktop widths; assistive technology review |

## Verification layers

- **Source-only (already run):** route-manifest method/security contracts, policy namespace contracts, controller/source checks, PHP lint, and JavaScript syntax. These do not prove runtime behavior.
- **Laravel HTTP runtime (pending):** boot only in an isolated worktree profile with disposable storage/cache/session/log directories, network-disabled fakes, and an explicitly isolated database. An in-memory SQLite profile may cover basic middleware/UI contracts but does not establish MySQL schema, locking, collation, or concurrency compatibility.
- **MySQL (blocked):** run schema, receipt uniqueness, row-lock, duplicate acknowledgment, rollback, and preservation tests only after a separate disposable MySQL instance is proven empty and isolated.
- **Browser (pending):** use only synthetic files and records against that isolated runtime. Verify generated Vite assets before enabling any V2 route. Keep `transmittal_v2.enabled` false outside the approved test profile.
