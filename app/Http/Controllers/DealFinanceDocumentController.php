<?php

namespace App\Http\Controllers;

use App\Models\{Deal, DealFinanceDocument};
use App\Services\DealFinanceDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;

class DealFinanceDocumentController extends Controller
{
    public function index(Request $request, Deal $deal, DealFinanceDocuments $documents)
    {
        abort_unless($documents->canView($request->user()), 403);
        return response()->json(['documents'=>$documents->documents($deal)]);
    }

    public function store(Request $request, Deal $deal, DealFinanceDocuments $documents)
    {
        abort_unless($documents->canUpload($request->user()), 403);
        $validated = $request->validate([
            'document_type'=>['required', 'in:quotation,payment_notice'],
            'document'=>['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'extensions:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);
        $path = null;
        try {
            $record = DB::transaction(function () use ($request, $deal, $validated, &$path) {
                Deal::whereKey($deal->id)->lockForUpdate()->firstOrFail();
                $proposal = $deal->fresh()->proposal;
                if ($validated['document_type'] === 'quotation' && $proposal
                    && ($proposal->quotation_approved_at || $proposal->quotation_status === 'approved')) {
                    throw ValidationException::withMessages(['document'=>'Approved quotations cannot be replaced unless the deal is renegotiated.']);
                }
                // Retain the previous quotation when the older form only stored its path.
                $previous = $validated['document_type'] === 'quotation' ? $proposal?->quotation_finance_file_path : null;
                if ($previous && ! DealFinanceDocument::where('deal_id', $deal->id)->where('path', $previous)->exists()) {
                    $disk = Storage::disk('public');
                    $exists = $disk->exists($previous);
                    DealFinanceDocument::create([
                        'deal_id'=>$deal->id, 'document_type'=>'quotation', 'original_name'=>basename($previous),
                        'path'=>$previous, 'mime_type'=>$exists ? $disk->mimeType($previous) : 'application/octet-stream',
                        'size'=>$exists ? $disk->size($previous) : 0, 'uploaded_by'=>null,
                        'created_at'=>$proposal->updated_at,
                    ]);
                }
                $file = $validated['document'];
                $path = $file->store('deal-proposals/'.$deal->id.'/finance-documents', 'public');
                if (! $path) { throw new \RuntimeException('Unable to store the uploaded document.'); }
                $name = preg_replace('/[\x00-\x1F\x7F]/', '', basename(str_replace('\\', '/', $file->getClientOriginalName())));
                $record = DealFinanceDocument::create([
                    'deal_id'=>$deal->id, 'document_type'=>$validated['document_type'],
                    'original_name'=>$name, 'path'=>$path, 'mime_type'=>$file->getMimeType(),
                    'size'=>$file->getSize(), 'uploaded_by'=>$request->user()->id,
                ]);
                if ($proposal && $validated['document_type'] === 'quotation') {
                    $proposal->forceFill(['quotation_finance_file_path'=>$path, 'quotation_status'=>'uploaded'])->save();
                }
                return $record;
            });
        } catch (\Throwable $error) {
            if ($path) { Storage::disk('public')->delete($path); }
            throw $error;
        }
        return response()->json(['id'=>(string)$record->id, 'documents'=>$documents->documents($deal)], 201);
    }

    public function file(Request $request, Deal $deal, string $document, DealFinanceDocuments $documents)
    {
        abort_unless($documents->canView($request->user()), 403);
        $record = $documents->resolve($deal, $document);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($record['path']), 404, 'The original uploaded file is no longer available.');
        $mime = $record['mime_type'] ?: $disk->mimeType($record['path']);
        $inline = $mime === 'application/pdf' || in_array($mime, ['image/jpeg','image/png'], true);
        $headers = ['Content-Type'=>$mime ?: 'application/octet-stream', 'X-Content-Type-Options'=>'nosniff'];
        if ($request->boolean('download') || ! $inline) {
            return $disk->download($record['path'], $record['original_name'], $headers);
        }
        return $disk->response($record['path'], $record['original_name'], $headers, 'inline');
    }
}
