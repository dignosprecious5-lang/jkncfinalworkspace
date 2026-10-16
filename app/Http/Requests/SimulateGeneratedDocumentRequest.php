<?php

namespace App\Http\Requests;

use App\Support\GeneratedDocumentMappings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Simulate Generated Document", from the Integration tab.
 *
 * One mapping row, not a free-text document, so what gets filed is always a
 * pairing the table already shows rather than something invented at the button.
 */
class SimulateGeneratedDocumentRequest extends FormRequest
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
            'mapping' => [
                'required',
                'integer',
                Rule::in(range(0, GeneratedDocumentMappings::count() - 1)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mapping.required' => 'Choose which generated document to simulate.',
            'mapping.in' => 'That mapping is not one this tab offers.',
        ];
    }
}
