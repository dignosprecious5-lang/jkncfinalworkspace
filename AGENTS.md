# AGENTS.md — ORDO Records Management Foundation

## Confidentiality & Project Overview
This project is the **ORDO Records Management Foundation** prototype[cite: 1].
This project is strictly confidential and contains proprietary business requirements and implementation details[cite: 1, 2].

- Never expose confidential project information unnecessarily[cite: 2].
- Never commit `.env` files or API keys[cite: 1, 2].
- Never push proprietary materials to unauthorized repositories[cite: 1, 2].
- Do not add external integrations without explicit approval[cite: 1, 2].
- Do not upload project files to external services unless explicitly authorized[cite: 2].

---

## Target Allocation & Project Phase
- **Initial Allocation:** 40 development hours total (Sprint 1 & Sprint 2 core deliverables completed).
- **Current Phase:** Post-Sprint Hardening, Feature Parity, & Production Refinement.
- **Current Objective:** Focus 100% of effort on resolving non-functional UI elements, perfecting file preview/stream rendering, hardening async pipelines, and reaching complete feature parity with ORDO Reference Mock V7[cite: 2].
- **Corporate Governance Note:** Corporate Governance integration proceeds strictly after the Records Management foundation is reviewed and accepted[cite: 1].

---

## Technology Stack
- **Backend:** Laravel / PHP[cite: 1].
- **Database:** MySQL[cite: 1].
- **Frontend:** Blade[cite: 1].
- **Styling:** Tailwind CSS[cite: 1].
- **Interactivity:** Alpine.js or Livewire[cite: 1].
- **PDF Processing:** `smalot/pdfparser`[cite: 1].
- **Image Processing:** Intervention Image[cite: 1].
- **QR Generation:** `simplesoftwareio/simple-qrcode`[cite: 1].

---

## Architecture & Security Rules
- Follow standard Laravel conventions[cite: 1].
- Use existing authentication and tenant/company isolation conventions where available[cite: 1].
- All database queries involving records must respect `tenant_id` and `company_id` server-side[cite: 1].
- Never rely only on frontend filtering for security; use policies and server-side authorization[cite: 1].
- Use database transactions for atomic filing operations[cite: 1].
- Use ULID or UUID as internal database primary identifiers[cite: 1].
- Generate the unique 5-character alphanumeric Document Reference atomically upon official filing[cite: 1].

---

## Development Workflow Rules
Before making significant changes:
1. Inspect the existing implementation[cite: 1].
2. Identify affected files[cite: 1].
3. Explain the proposed change[cite: 1].
4. Make the smallest appropriate change[cite: 1].
5. Run relevant tests[cite: 1, 3].
6. Report what was changed and what was tested[cite: 3].
## Development & Refinement Rules
During this polish phase:
1. **Targeted Fixes Only:** Resolve non-functional controls (e.g., non-rendering previews, broken preview tab toggles, missing CSRF tokens) without rewriting working underlying services[cite: 1].
2. **Preserve Passing Tests:** Any UI or controller modification MUST keep the existing test suite (439+ passing tests) green[cite: 3]. Add new unit/feature tests for any newly fixed preview route or asset handler[cite: 3].
3. **No Unvetted Package Additions:** Do not introduce heavy frontend PDF libraries or JS frameworks for previewing if browser-native streaming or existing PDF.js assets can handle the requirement.
4. **Performance Guardrail:** All primary pages and drawer loads MUST remain locked at $\le 13$ database queries per request load[cite: 1].

*Do not rewrite working code unnecessarily[cite: 1]. Do not introduce new dependencies without justification[cite: 1, 2].*

---

## Records Domain Rules

### Core Record Requirements
Official Records must support:
- Classification[cite: 1]
- Index[cite: 1]
- Title[cite: 1]
- Date[cite: 1]
- Source[cite: 1]
- Reference Number (External & ORDO Document Reference)[cite: 1]
- Files (Master & Supporting)[cite: 1]
- Activity History (Append-only audit log)[cite: 1]
- Links to other records and ORDO modules[cite: 1]

### File Handling & Linking
- **No File Duplication:** Never duplicate existing document files when creating links[cite: 1].
- **Polymorphic Linking:** Use `record_links` (`linked_type`, `linked_id`) for module relationships[cite: 1].
- **Asynchronous OCR:** OCR processing must be queued asynchronously[cite: 1].
- **OCR Resilience:** OCR failure must NOT prevent the original file from being preserved[cite: 1].
- **Needs Review:** Failed OCR processing should result in a `Needs Review` state without blocking manual filing[cite: 1].

### Capacity & Billing Rules
- **Intake Item:** 0 Record Units (consumes storage bytes only)[cite: 1].
- **Official Filed Record:** 1 Record Unit (assigns Document Reference)[cite: 1].
- **Supporting Files / Versions / Record Links:** 0 additional Record Units[cite: 1].

---

## Standard Classification Scheme Reference
The classification hierarchy is `Classification` -> `Index` -> `Record`[cite: 1]:
- `101`: Client Master File[cite: 1]
- `301`: Entity & Ownership (Indexes: `301-1` Incorporation, `301-6` GIS, `301-12` Minutes, `301-13` Board Resolution, `301-15` Sec Cert)[cite: 1]
- `401`: BIR & Tax (Indexes: `401-4` Form 2303, `401-30` Form 2307)[cite: 1]
- `701`: Finance[cite: 1]
- `801`: Banking[cite: 1]
- `901`: Legal[cite: 1]

---

## UI & User Experience
Do **NOT** redesign the ORDO interface[cite: 1].

Strictly preserve:
- Existing layout[cite: 1]
- Typography[cite: 1]
- Color palette[cite: 1]
- Navigation style[cite: 1]
- Visual hierarchy[cite: 1]
- Component behavior[cite: 1]

---

## Document Preview & File Streaming Rules
When fixing or enhancing document previews across drawers (`#classifyDrawer`, `#recordDrawer`) and views:

- **Browser-Native Streaming:** Server routes MUST stream files inline using proper headers (`Content-Type` matching MIME type, `Content-Disposition: inline`) rather than forcing downloads.
- **Supported Formats:**
  - **PDFs:** Render via browser-native `<iframe src="...">` or Embed PDF viewer with navigation parameters enabled (`#toolbar=1&navpanes=0`).
  - **Images (JPEG/PNG/WEBP):** Render via fluid responsive `<img>` containers preserving aspect ratio.
  - **Unsupported/Binary Formats:** Display structured fallback cards showing file metadata (extension, size, MIME type) and an "Open / Download Original" action button.
- **Secure File Serving:** File preview endpoints MUST go through the `ordo.record` / `CompanyContext` authorization pipeline. Never expose raw disk storage paths directly to the web public directory.
- **Temporary URL Lifetime:** If using cloud/private storage drivers (e.g., S3/minio), temporary signed URLs for preview embeds must have short expiration limits (e.g., 15–30 minutes).

---

**Progressive Disclosure:** Show only necessary default fields (Company, Classification, Index, Title, Document Date, Source, Reference No.)[cite: 1]. Keep "Advanced Details" (Identifiers, Dates, Signatories, Financials) collapsed and expose them conditionally based on the selected Index[cite: 1].