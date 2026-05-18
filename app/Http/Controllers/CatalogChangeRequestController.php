<?php

namespace App\Http\Controllers;

use App\Models\CatalogChangeRequest;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CatalogChangeRequestController extends Controller
{
    public function approve(Request $request, CatalogChangeRequest $catalogChangeRequest): RedirectResponse
    {
        abort_unless(in_array((string) ($request->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true), 403);
        abort_if($catalogChangeRequest->status !== 'Pending Approval', 422, 'This request has already been reviewed.');

        $now = now();
        $reviewerId = $request->user()?->id;

        if ($catalogChangeRequest->module === 'product') {
            $this->approveProductRequest($catalogChangeRequest, $reviewerId, $now);
            $message = 'Product change request approved.';
        } else {
            $this->approveServiceRequest($catalogChangeRequest, $reviewerId, $now);
            $message = 'Service change request approved.';
        }

        $catalogChangeRequest->update([
            'status' => 'Approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $now,
            'rejection_notes' => null,
        ]);

        return back()->with(
            $catalogChangeRequest->module === 'product' ? 'success' : 'services_success',
            $message
        );
    }

    public function reject(Request $request, CatalogChangeRequest $catalogChangeRequest): RedirectResponse
    {
        abort_unless(in_array((string) ($request->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true), 403);
        abort_if($catalogChangeRequest->status !== 'Pending Approval', 422, 'This request has already been reviewed.');

        $catalogChangeRequest->update([
            'status' => 'Rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with(
            $catalogChangeRequest->module === 'product' ? 'success' : 'services_success',
            ucfirst($catalogChangeRequest->module).' change request rejected.'
        );
    }

    private function approveProductRequest(CatalogChangeRequest $catalogChangeRequest, ?int $reviewerId, $now): void
    {
        $product = Product::query()->findOrFail($catalogChangeRequest->record_id);

        if ($catalogChangeRequest->action === 'delete') {
            $product->delete();
            return;
        }

        $product->update($catalogChangeRequest->payload ?? []);
    }

    private function approveServiceRequest(CatalogChangeRequest $catalogChangeRequest, ?int $reviewerId, $now): void
    {
        $service = Service::query()->findOrFail($catalogChangeRequest->record_id);

        if ($catalogChangeRequest->action === 'delete') {
            $service->delete();
            return;
        }

        $payload = $catalogChangeRequest->payload ?? [];
        $payload['status'] = 'Active';
        $payload['reviewed_by'] = $reviewerId;
        $payload['reviewed_at'] = $now;
        $payload['approved_by'] = $reviewerId;
        $payload['approved_at'] = $now;

        $service->update($payload);
    }
}
