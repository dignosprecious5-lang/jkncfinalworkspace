<?php

namespace App\Policies;

use App\Models\Transmittal;
use App\Models\User;
use App\Services\TransmittalV2Authorization;
use App\Services\TransmittalWorkflowBoundary;

final class TransmittalV2Policy
{
    public function view(User $user, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        return TransmittalV2Authorization::canApprove($user)
            || TransmittalV2Authorization::ownsTransmittal($user, $transmittal->prepared_by);
    }

    public function viewAttachments(User $user, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        return $this->view($user, $transmittal)
            || $transmittal->recipients()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $user->email)])
                ->exists();
    }

    public function approve(User $user, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        return TransmittalV2Authorization::canApprove($user)
            && ! TransmittalV2Authorization::ownsTransmittal($user, $transmittal->prepared_by)
            && $transmittal->status === 'pending_approval';
    }

    public function reject(User $user, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        return TransmittalV2Authorization::canApprove($user)
            && $transmittal->status === 'pending_approval';
    }

    public function transmit(User $user, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        return (TransmittalV2Authorization::canApprove($user)
                || TransmittalV2Authorization::ownsTransmittal($user, $transmittal->prepared_by))
            && in_array($transmittal->status, ['approved', 'sent'], true);
    }

    public function uploadProof(User $user, Transmittal $transmittal): bool
    {
        if (! TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        $ownerCanManageDraft = TransmittalV2Authorization::ownsTransmittal($user, $transmittal->prepared_by)
            && in_array($transmittal->status, ['draft', 'pending_approval'], true);

        return $ownerCanManageDraft
            || (TransmittalV2Authorization::canApprove($user)
                && in_array($transmittal->status, ['pending_approval', 'approved', 'sent', 'acknowledged'], true));
    }
}
