<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'record_classification_id' => ['required', 'string', 'max:26'],
            'record_index_id' => ['required', 'string', 'max:26'],
            'title' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:255'],
            'external_reference_number' => ['nullable', 'string', 'max:255'],
            'identifiers' => ['nullable', 'array', 'max:25'],
            'identifiers.*' => ['nullable', 'string', 'max:255'],
            'additional_dates' => ['nullable', 'array', 'max:25'],
            'additional_dates.*' => ['nullable', 'string', 'max:255'],
            'signatories' => ['nullable', 'array', 'max:25'],
            'signatories.*' => ['nullable', 'string', 'max:255'],
            'financials' => ['nullable', 'array', 'max:25'],
            'financials.*' => ['nullable', 'string', 'max:255'],
            'is_confidential' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'record_classification_id.required' => 'Select a Classification Code.',
            'record_index_id.required' => 'Select an Index Code.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'record_classification_id' => 'classification',
            'record_index_id' => 'index',
            'document_date' => 'document date',
            'external_reference_number' => 'external reference number',
        ];
    }
}
