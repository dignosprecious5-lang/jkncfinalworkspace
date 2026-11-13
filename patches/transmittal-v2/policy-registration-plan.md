# V2 Authorization Isolation Plan

Status: prepared; provider is not registered and V2 routes remain inactive.

## V1 compatibility finding

The active V1 `TransmittalController::dashboard()` checks `Auth::user()` and `hasPermission('approve_corporate')` directly. It does not call a Transmittal Gate policy. The existing V2 provider candidate previously mapped the shared `App\Models\Transmittal` model globally with `Gate::policy()`. That global mapping is removed because it could change policy resolution for any other code using the shared model.

## V2-only strategy

- `TransmittalV2Policy` remains the record-level policy implementation. It checks V2 ownership, administrator roles, record status, and recipient-specific attachment access.
- The inactive V2 provider defines only namespaced abilities: `transmittal-v2.view`, `transmittal-v2.view-attachments`, `transmittal-v2.approve`, `transmittal-v2.reject`, `transmittal-v2.transmit`, and `transmittal-v2.upload-proof`.
- V2 controller calls use the namespaced approval, rejection, transmit, and proof abilities. The provider does not call `Gate::policy()` and does not replace a shared-model policy map.
- The provider is intentionally absent from `bootstrap/providers.php`. On a separately approved V2 activation task, register `App\Providers\TransmittalV2AuthorizationServiceProvider::class` there only after route/middleware and policy tests pass. Keep the V2 feature flag false until controlled activation is separately approved.
- V2 controller-local ownership and scoped-record checks remain in place. The Gate abilities supplement those checks; they do not replace them.

## Remaining authorization verification

Source contracts do not prove Laravel Gate dispatch, role data, session ownership, or record-level behavior at runtime. Those require isolated HTTP tests with synthetic users and records, then MySQL verification on a disposable instance. Do not test against an existing ORDO database.
