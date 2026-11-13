<?php

namespace App\Services;

use App\Models\Transmittal;
use App\Services\TransmittalWorkflowBoundary;
use App\Models\TransmittalWorkspaceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransmittalWorkspaceItemSyncService
{
    public function synchronize(Transmittal $transmittal, array $payload): void
    {
        if (! array_key_exists('items', $payload)) {
            return;
        }

        $items = $payload['items'];
        if (! is_array($items)) {
            $this->invalid('items', 'The items payload must be an array.');
        }
        if (count($items) > 50) {
            $this->invalid('items', 'The items list cannot exceed 50 items.');
        }

        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);

        DB::transaction(function () use ($transmittal, $items): void {
            $locked = TransmittalWorkflowBoundary::v2Query()
                ->lockForUpdate()
                ->findOrFail($transmittal->getKey());

            $this->assertEditable($locked);

            $existingItems = $locked->workspaceItems()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $retainedIds = [];
            $submittedIds = [];
            $position = 0;

            foreach ($items as $data) {
                if (! is_array($data)) {
                    $this->invalid("items.{$position}", 'Each item must be an object.');
                }

                $itemId = $this->optionalId($data['id'] ?? null, "items.{$position}.id");
                if ($itemId !== null) {
                    if (isset($submittedIds[$itemId])) {
                        $this->invalid("items.{$position}.id", 'An item may be submitted only once.');
                    }
                    $submittedIds[$itemId] = true;
                    $item = $existingItems->get($itemId);
                    if (! $item instanceof TransmittalWorkspaceItem) {
                        $this->invalid("items.{$position}.id", 'The item does not belong to this transmittal.');
                    }
                } else {
                    $item = null;
                }

                $documentId = $item?->document_id;
                if (array_key_exists('document_id', $data)) {
                    $documentId = $this->optionalId($data['document_id'], "items.{$position}.document_id");
                }
                if ($documentId !== null) {
                    $document = $locked->documents()
                        ->whereKey($documentId)
                        ->lockForUpdate()
                        ->first();
                    if ($document === null) {
                        $this->invalid(
                            "items.{$position}.document_id",
                            'The document does not belong to this transmittal.'
                        );
                    }
                }

                $lineNumber = $data['line_number'] ?? $data['no'] ?? ($position + 1);
                if ($lineNumber === '') {
                    $lineNumber = $position + 1;
                }
                $lineNumber = $this->requiredId($lineNumber, "items.{$position}.line_number");
                if ($lineNumber > 4294967295) {
                    $this->invalid("items.{$position}.line_number", 'The line number exceeds its supported range.');
                }

                $values = [
                    'document_id' => $documentId,
                    'position' => $position,
                    'line_number' => $lineNumber,
                    'particular' => $this->optionalString($data, 'particular', 255, $position),
                    'unique_id' => $this->optionalString($data, 'unique_id', 120, $position),
                    'quantity' => $this->optionalString($data, 'quantity', 40, $position),
                    'description' => $this->optionalString($data, 'description', 2000, $position),
                    'remarks' => $this->optionalString($data, 'remarks', 2000, $position),
                ];

                if ($item === null) {
                    $item = $locked->workspaceItems()->make($values);
                } else {
                    $item->fill($values);
                }
                if ($item->save() === false || ! $item->exists || $item->getKey() === null) {
                    $this->invalid('items', 'The workspace item could not be persisted.');
                }
                $retainedIds[] = $item->getKey();
                $position++;
            }

            $omittedItems = $locked->workspaceItems();
            if ($retainedIds === []) {
                $omittedItems->delete();
            } else {
                $omittedItems->whereNotIn('id', $retainedIds)->delete();
            }
        });
    }

    public function assertEditable(Transmittal $transmittal): void
    {
        abort_unless(TransmittalWorkflowBoundary::isV2($transmittal), 404);
        $editableStatuses = ['draft', 'rejected', 'pending_approval'];
        $status = strtolower((string) $transmittal->status);

        if (! in_array($status, $editableStatuses, true)) {
            $this->invalid('items', 'Items can be changed only while the transmittal is editable.');
        }

        if (
            $transmittal->approved_snapshot !== null
            || $transmittal->approved_at !== null
            || strtolower((string) $transmittal->approval_status) === 'approved'
            || $transmittal->authorityCode()->exists()
            || $transmittal->proofs()->exists()
            || $transmittal->receipts()->exists()
            || $transmittal->acknowledgments()->exists()
        ) {
            $this->invalid('items', 'Items cannot be changed after approval or retained evidence.');
        }
    }

    private function optionalId(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->requiredId($value, $field);
    }

    private function requiredId(mixed $value, string $field): int
    {
        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            $this->invalid($field, 'The value must be a positive integer.');
        }

        $id = (int) $value;
        if ($id < 1) {
            $this->invalid($field, 'The value must be a positive integer.');
        }

        return $id;
    }

    private function optionalString(array $data, string $key, int $maxLength, int|string $position): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (! is_string($value)) {
            $this->invalid("items.{$position}.{$key}", 'The value must be a string.');
        }
        if (mb_strlen($value) > $maxLength) {
            $this->invalid("items.{$position}.{$key}", "The value may not exceed {$maxLength} characters.");
        }

        return $value;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
