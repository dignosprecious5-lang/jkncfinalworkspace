# Transmittal V2 Vite Integration Status

Status: V2 CSS/JavaScript inputs are configured in `vite.config.js`; the locked Vite 7.3.1 build passed with output directed to an isolated temporary directory (64 modules transformed). Production assets, Forge build hooks, browser deployment and route activation remain unverified. The V2 and optional-feature flags remain default-off.

## Configured V2 inputs

- Preserve the current `resources/css/app.css` and `resources/js/app.js` inputs.
- `resources/css/transmittal.css` and `resources/js/transmittal.js` are separate V2 inputs.
- `resources/js/transmittal-v2-file-tools.js` is a separate input and imports the isolated `resources/js/transmittal-v2-upload-compression.js` helper. Do not copy the protected `app.js` or change V1 entry points.
- Build and verify the Vite manifest before enabling any V2 route or rendering a V2 view that calls `@vite`.

## Dependency status

- `resources/js/transmittal-v2-file-tools.js` provides size formatting, image compression, and an explicit unavailable response for ZIP extraction. The image helper returns a new JPEG file and does not mutate or write the source file.
- The image compression path may resize images and flatten transparent pixels onto white. The user must explicitly choose compression; browser quality and output must be checked before enabling the flow.
- PDF compression is not implemented because `pdf-lib` is absent from the workspace dependencies.
- ZIP extraction remains unimplemented and explicitly disabled. The installed `@zip.js/zip.js@2.24.0` dependency does not activate extraction. A future bounded worker implementation, cancellation, integrity/path checks, and remaining-queue byte/count limits require separate authorization.
- Do not add package imports or claim either feature is available until dependency versions, bundle impact, archive-bomb limits, PDF quality, and browser behavior receive review and approval.

## Verified local tooling and proposed pins

- Local tools: Node `v22.12.0`, npm `10.9.0`.
- Existing lock pins: Vite `7.3.1`, Laravel Vite plugin `2.1.0`, and `@tailwindcss/vite` `4.2.1`. Vite and the Laravel plugin require Node `^20.19.0 || >=22.12.0`; the installed Node meets that locked requirement. The Tailwind Vite plugin declares Vite 5, 6, or 7 as compatible peers.
- Installed ZIP dependency: **@zip.js/zip.js@2.24.0**, using the official npm registry. Its lock integrity is `sha512-WlaVKzdqBK+RF3bdiXZWbdCnvGB6PdO4IHC1mn/2Yfl3bASgqUNegnU3+PNg61iecwoPfUi5BgLUPqBukXpyWw==`. The package declares Node >=18 and no runtime/optional/peer dependencies. Node 22.12.0 satisfies that engine requirement.
- The earlier candidates are superseded: `fflate@0.8.3` is blocked by the security investigation; `pdf-lib@1.17.1` is deferred pending independent PDF resource-safety review. Neither is declared, locked, or installed.
- Installation used a lockfile-only update followed by local population of node_modules, both with lifecycle scripts disabled and no automatic audit fixes. The 162 original non-root package records were preserved, including versions, origins, integrity values, and metadata. The only dependency addition was zip.js.
- The exact approved top-level lockfile name exception is `jknc_project` to `jknc_transmittal_integration`, matching the verified integration folder. `package.json` has no name field in either snapshot; its identity field was not renamed. The original baseline name remains intact, and all other fields stay under strict comparison.
- The immutable pre-install fixture is `tests/Fixtures/transmittal-v2/dependency-baseline.json`. Standalone assertions allow exactly the approved ZIP declaration/record and documented top-level lockfile name exception, and reject unrelated manifest, version, integrity, resolution, or package-set changes. The fixture is not a new baseline that accepts the entire current tree without comparison.
- Security acceptance remains pending. The saved full-tree audit reported **11 pre-existing flagged package entries: 2 critical, 7 high, 1 moderate, 1 low**, exit code 1. zip.js was not a finding in that report. This is not a vulnerability-free certification, and no remediation has been applied.
- Dependency imports and source-only tests do not run extraction, Vite, concurrently, development servers, or Laravel. Existing PDF/ZIP capability flags remain false. The legacy unavailable messages in the helpers do not imply approval to install fflate.
- Source-verification results are recorded in the feature checklist after the approved regression suites run. Runtime/browser acceptance, build integration, and security remediation remain separate work.

## Activation prerequisites

1. Complete separately approved security remediation, dependency verification, and bounded ZIP/PDF feature review. Installing the ZIP library alone authorizes no activation.
2. Preserve the current V1 entries and the isolated V2 entries. Smart Upload and OCR remain default-disabled; the manual draft/edit/upload/download workflow is independently accepted.
3. In isolated staging, verify the generated manifest, browser loading, deployed CSS/JS, and graceful failures. The local build alone does not verify Forge's build or shared release assets.
4. Keep V2 and dispatch gates disabled until route, actual-schema, authorization, storage, provider, worker and browser verification are complete.
