<?php

namespace App\Services;

use App\Models\{Deal, DealFinanceDocument, DealProposal, User};
use Illuminate\Support\Facades\{Schema, Storage};

class DealFinanceDocuments
{
    public function canUpload(?User $user): bool
    {
        return $user && ! $user->isClient() && ! $user->isDisabled() && ! $user->isArchived()
            && ($user->isSuperAdmin() || $user->isAdmin() || $user->hasPermission('create_finance'));
    }

    public function canView(?User $user): bool
    {
        return $user && ! $user->isClient() && ! $user->isDisabled() && ! $user->isArchived()
            && ($this->canUpload($user) || $user->hasPermission('access_finance') || $user->hasPermission('access_deals'));
    }

    public function resolve(Deal $deal, string $key): array
    {
        if (preg_match('/^legacy-(\d+)-(finance|client|invoice)$/', $key, $match)) {
            $proposal = DealProposal::where('deal_id', $deal->id)->findOrFail($match[1]);
            $column = ['finance'=>'quotation_finance_file_path', 'client'=>'quotation_client_file_path', 'invoice'=>'invoice_file_path'][$match[2]];
            $path = $proposal->{$column};
            abort_unless($path, 404);
            return ['id'=>$key, 'document_type'=>$match[2] === 'invoice' ? 'payment_notice' : 'quotation',
                'original_name'=>basename($path), 'path'=>$path, 'mime_type'=>null, 'size'=>null,
                'created_at'=>$proposal->updated_at?->toIso8601String(), 'legacy'=>true];
        }
        $record = DealFinanceDocument::where('deal_id', $deal->id)->findOrFail($key);
        return array_merge($record->toArray(), ['id'=>(string)$record->id, 'legacy'=>false]);
    }

    public function documents(Deal $deal): array
    {
        $records = Schema::hasTable('deal_finance_documents')
            ? DealFinanceDocument::where('deal_id', $deal->id)->latest('id')->get()->map(fn ($record) => array_merge($record->toArray(), ['id'=>(string)$record->id, 'legacy'=>false]))->all() : [];
        $known = array_column($records, 'path');
        // Existing columns already classify older uploads. Keep them accessible without changing their files.
        foreach (DealProposal::where('deal_id', $deal->id)->latest('id')->get() as $proposal) {
            foreach (['finance'=>'quotation_finance_file_path', 'client'=>'quotation_client_file_path', 'invoice'=>'invoice_file_path'] as $source=>$column) {
                $path = $proposal->{$column};
                if (! $path || in_array($path, $known, true)) { continue; }
                $records[] = $this->resolve($deal, 'legacy-'.$proposal->id.'-'.$source);
                $known[] = $path;
            }
        }
        return array_map(function ($record) use ($deal) {
            $disk = Storage::disk('public');
            $exists = $disk->exists($record['path']);
            return [
                'id'=>$record['id'], 'document_type'=>$record['document_type'],
                'original_name'=>$record['original_name'],
                'mime_type'=>$record['mime_type'] ?: ($exists ? $disk->mimeType($record['path']) : null),
                'size'=>$record['size'] ?? ($exists ? $disk->size($record['path']) : null),
                'uploaded_at'=>$record['created_at'], 'uploaded_by'=>$record['uploaded_by'] ?? null,
                'legacy'=>$record['legacy'], 'available'=>$exists,
                'url'=>route('deals.finance-documents.file', [$deal->id, $record['id']]),
                'download_url'=>route('deals.finance-documents.file', [$deal->id, $record['id'], 'download'=>1]),
            ];
        }, $records);
    }
}
