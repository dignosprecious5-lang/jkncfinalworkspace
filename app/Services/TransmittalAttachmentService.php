<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

class TransmittalAttachmentService
{
    /** Validate and normalize the existing V1 payload without database or storage writes. */
    public function validate(Request $request): array
    {
        if (config('transmittal_attachments') !== [
            'max_files' => 50, 'max_total_bytes' => 104_857_600,
            'item_max_kib' => 10_240, 'supporting_max_kib' => 20_480,
        ]) {
            throw new RuntimeException('Attachment limits do not match the approved configuration.');
        }
        $items = $request->input('items', []);
        if (is_string($items)) {
            try {
                if (! str_starts_with(ltrim($items), '[')) throw new JsonException;
                $items = json_decode($items, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->invalid('items', 'The item list is invalid. Review the form and try again.');
            }
        }
        if ($items === null) $items = [];
        if (! is_array($items) || ! array_is_list($items)) {
            $this->invalid('items', 'Items must be an ordered list.');
        }
        foreach ($items as &$item) {
            if (is_array($item)) {
                foreach (['no', 'particular', 'unique_id', 'qty', 'description', 'remarks'] as $key) {
                    if (array_key_exists($key, $item) && $item[$key] === '') $item[$key] = null;
                }
            }
        }
        unset($item);
        $files = $request->allFiles();
        if (array_diff(array_keys($files), ['item_files', 'attachments']) !== []) {
            $this->invalid('uploads', 'The submission contains an unexpected file field.');
        }
        foreach (['item_files', 'attachments'] as $group) {
            if ($request->request->has($group) || $request->query->has($group)) {
                $this->invalid($group, 'Upload fields must contain files only.');
            }
        }
        $itemFiles = $files['item_files'] ?? [];
        $attachments = $files['attachments'] ?? [];
        $textBytes = static function ($attribute, $value, $fail): void {
            if (is_string($value) && strlen($value) > 65_535) $fail('This item text exceeds the database text capacity.');
        };
        Validator::make(['items' => $items, 'item_files' => $itemFiles, 'attachments' => $attachments], [
            'items' => ['array'],
            'items.*' => ['array:no,particular,unique_id,qty,description,remarks'],
            'items.*.no' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'items.*.particular' => ['nullable', 'string', 'max:255'],
            'items.*.unique_id' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'items.*.description' => ['nullable', 'string', $textBytes],
            'items.*.remarks' => ['nullable', 'string', $textBytes],
            'item_files' => ['array', 'max:50'],
            'item_files.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
            'attachments' => ['array', 'max:50'],
            'attachments.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,csv', 'max:20480'],
        ])->validate();
        $received = ['item_files' => [], 'attachments' => []];
        $count = 0;
        $bytes = 0;
        foreach (['item_files' => $itemFiles, 'attachments' => $attachments] as $group => $uploads) {
            foreach ($uploads as $index => $file) {
                if (! is_int($index) || $index < 0 || ! $file instanceof UploadedFile || ! $file->isValid()) {
                    $this->invalid($group, 'An upload has an invalid index or did not finish uploading.');
                }
                if ($group === 'item_files' && ! array_key_exists($index, $items)) {
                    $this->invalid('item_files', 'An item attachment has no matching item row.');
                }
                $size = $file->getSize();
                if (! is_int($size) || $size <= 0) $this->invalid($group, 'Empty or unreadable uploads are not accepted.');
                $received[$group][$index] = $size;
                $count++;
                $bytes += $size;
            }
        }
        if ($count > 50) $this->invalid('uploads', 'A transmittal may contain at most 50 files combined.');
        if ($bytes > 104_857_600) $this->invalid('uploads', 'Combined upload size must not exceed 100 MiB.');

        // Required even for zero files: an old/truncated request must not become a text-only save.
        $rawManifest = $request->input('upload_manifest');
        if (! is_string($rawManifest) || strlen($rawManifest) > 16_384) {
            $this->invalid('upload_manifest', 'Upload completeness information is missing. Refresh the form before saving.');
        }
        try {
            $manifest = json_decode($rawManifest, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid('upload_manifest', 'Upload completeness information is invalid. Refresh the form.');
        }
        Validator::make(['manifest' => $manifest], [
            'manifest' => ['required', 'array:version,item_files,attachments'],
            'manifest.version' => ['required', 'integer', 'in:1'],
            'manifest.item_files' => ['present', 'array', 'max:50'],
            'manifest.attachments' => ['present', 'array', 'max:50'],
            'manifest.item_files.*' => ['required', 'array:index,size'],
            'manifest.attachments.*' => ['required', 'array:index,size'],
            'manifest.item_files.*.index' => ['required', 'integer', 'min:0'],
            'manifest.attachments.*.index' => ['required', 'integer', 'min:0'],
            'manifest.item_files.*.size' => ['required', 'integer', 'min:1', 'max:10485760'],
            'manifest.attachments.*.size' => ['required', 'integer', 'min:1', 'max:20971520'],
        ])->validate();
        $expectedCount = 0;
        $expectedBytes = 0;
        foreach (['item_files', 'attachments'] as $group) {
            if (! array_is_list($manifest[$group])) $this->invalid('upload_manifest', 'Upload completeness lists are invalid.');
            $expected = [];
            foreach ($manifest[$group] as $entry) {
                if (! is_int($entry['index']) || ! is_int($entry['size']) || array_key_exists($entry['index'], $expected)) {
                    $this->invalid('upload_manifest', 'Upload completeness information contains duplicate or invalid indexes.');
                }
                $expected[$entry['index']] = $entry['size'];
                $expectedCount++;
                $expectedBytes += $entry['size'];
            }
            ksort($expected);
            ksort($received[$group]);
            if ($expected !== $received[$group]) {
                $this->invalid('upload_manifest', 'Some selected files were missing or incomplete. Nothing was saved. Reselect the files or contact support.');
            }
        }
        if ($expectedCount > 50 || $expectedBytes > 104_857_600) {
            $this->invalid('upload_manifest', 'The selected uploads exceed the 50-file or 100 MiB combined limit.');
        }
        $request->merge(['items' => $items]);
        return ['items' => $items, 'item_files' => $itemFiles, 'attachments' => $attachments];
    }

    public function attempt(string $reference): TransmittalAttachmentAttempt
    {
        return new TransmittalAttachmentAttempt($reference);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
