<?php

namespace App\Http\Controllers\Records;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRecordNumberingRequest;
use App\Models\RecordActivityLog;
use App\Services\CompanyContext;
use App\Support\RecordNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The Record Numbering tab's save.
 *
 * A settings write, shaped like the Usage tab's Plan limits save: an ordinary
 * post that redirects back with `setup_tab` so the modal reopens where it came
 * from.
 */
class RecordNumberingController extends Controller
{
    public function __construct(
        private readonly CompanyContext $context,
        private readonly RecordNumberGenerator $numbers,
    ) {}

    public function update(UpdateRecordNumberingRequest $request): RedirectResponse
    {
        // Re-read rather than taking the context's instance as-is.
        //
        // Filing advances record_next_sequence on its own, inside the filing
        // transaction, through a different instance. An instance held across
        // that still carries the old number, so forceFill() would find nothing
        // dirty and save() would write nothing at all — the operator would see
        // "Record numbering updated" and nothing would have changed. Reading
        // fresh also makes the audit snapshot below the values that are really
        // stored rather than the ones this request happens to be holding.
        $company = $this->context->current()->fresh() ?? $this->context->current();

        // What the operator is about to change, captured before it changes.
        $before = [
            'prefix' => (string) $company->record_prefix,
            'digits' => (int) $company->record_sequence_digits,
            'next_sequence' => (int) $company->record_next_sequence,
            'include_year' => (bool) $company->record_include_year,
        ];

        $company->forceFill([
            'record_prefix' => $request->prefix(),
            'record_sequence_digits' => (int) $request->input('record_sequence_digits'),
            'record_next_sequence' => (int) $request->input('record_next_sequence'),
            'record_include_year' => $request->boolean('record_include_year'),
        ])->save();

        // Audited, unlike the other setup screens. Numbering governs the
        // identifier on every record filed from now on, so a later "where did
        // REC-2026-000412 come from" needs an answer that is not in the file
        // itself — changing the prefix never rewrites the records already issued.
        RecordActivityLog::query()->create([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_id' => null,
            'record_intake_item_id' => null,
            'user_id' => Auth::id(),
            'action' => 'record.numbering_changed',
            'description' => sprintf(
                'Record numbering set to %s.',
                $this->numbers->preview($company),
            ),
            'properties' => ['before' => $before],
            'ip_address' => request()?->ip(),
        ]);

        return back()
            ->with('setup_tab', 'numbering')
            ->with('status', 'Record numbering updated.');
    }
}
