<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Visitor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VisitorContactResolver
{
    /**
     * Resolve the single Visitor identity for a Contact without creating Contacts.
     *
     * @return array{visitor: Visitor, created: bool}
     */
    public function resolve(int $contactId): array
    {
        try {
            return DB::transaction(function () use ($contactId): array {
                $contact = Contact::query()->lockForUpdate()->findOrFail($contactId);
                $visitor = Visitor::query()
                    ->where('contact_id', $contact->id)
                    ->lockForUpdate()
                    ->first();

                if ($visitor) {
                    return ['visitor' => $visitor->load('contact'), 'created' => false];
                }

                $visitor = Visitor::query()->create([
                    'name' => $contact->full_name ?: $contact->company_name ?: 'Contact '.$contact->id,
                    'mobile' => $contact->phone,
                    'email' => $contact->email,
                    'company_name' => $contact->company_name,
                    'contact_id' => $contact->id,
                ]);

                return ['visitor' => $visitor->load('contact'), 'created' => true];
            }, 3);
        } catch (QueryException $exception) {
            // The unique contact_id constraint is the final guard if another request
            // creates the Visitor immediately before this transaction commits.
            $visitor = Visitor::query()->where('contact_id', $contactId)->first();

            if ($visitor) {
                return ['visitor' => $visitor->load('contact'), 'created' => false];
            }

            throw $exception;
        }
    }
}
