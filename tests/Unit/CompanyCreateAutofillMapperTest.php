<?php

use App\Http\Controllers\CompanyController;
use App\Models\Contact;

function invokeCompanyAutofillMapper(Contact $contact, array $cifData = [], array $bifData = []): array
{
    $controller = app(CompanyController::class);
    $method = new ReflectionMethod($controller, 'buildCompanyAutofillPayload');
    $method->setAccessible(true);

    return $method->invoke($controller, $contact, $cifData, $bifData);
}

it('prefers client contact form values for business organization when available', function () {
    $contact = new Contact([
        'first_name' => 'Chris',
        'last_name' => 'Lee',
        'company_name' => 'Contact Company',
        'organization_type' => 'Partnership',
        'organization_type_other' => null,
        'business_type_organization' => null,
        'company_address' => 'Contact HQ',
        'contact_address' => 'Fallback Address',
        'phone' => '09171234567',
        'email' => 'contact@example.com',
        'tin' => '123456789',
    ]);

    $payload = invokeCompanyAutofillMapper($contact, [], [
        'business_organization' => 'corporation',
        'business_organization_other' => 'Legacy Org',
    ]);

    expect($payload['business_organization'])->toBe('partnership')
        ->and($payload['business_name'])->toBe('Contact Company')
        ->and($payload['business_address'])->toBe('Contact HQ');
});

it('falls back to linked bif values when contact form does not provide the field', function () {
    $contact = new Contact([
        'first_name' => 'Chris',
        'last_name' => 'Lee',
        'company_name' => null,
        'organization_type' => null,
        'organization_type_other' => null,
        'business_type_organization' => null,
        'company_address' => null,
        'contact_address' => 'Fallback Address',
        'phone' => '09171234567',
        'email' => 'contact@example.com',
        'tin' => null,
    ]);

    $payload = invokeCompanyAutofillMapper($contact, [], [
        'business_name' => 'Linked BIF Company',
        'business_organization' => 'corporation',
        'office_type' => 'branch',
        'alternative_business_name' => 'Trade Style',
        'zip_code' => '6000',
        'business_address' => 'Linked BIF Address',
        'tin_no' => '987654321',
        'nationality_status' => 'foreign',
    ]);

    expect($payload['business_name'])->toBe('Linked BIF Company')
        ->and($payload['business_organization'])->toBe('corporation')
        ->and($payload['office_type'])->toBe('branch')
        ->and($payload['alternative_business_name'])->toBe('Trade Style')
        ->and($payload['zip_code'])->toBe('6000')
        ->and($payload['business_address'])->toBe('Linked BIF Address')
        ->and($payload['tin_no'])->toBe('987654321')
        ->and($payload['nationality_status'])->toBe('foreign');
});

it('uses contact precedence over bif for business organization while preserving bif fallback for other fields', function () {
    $contact = new Contact([
        'first_name' => 'Chris',
        'last_name' => 'Lee',
        'company_name' => 'Contact Company',
        'organization_type' => 'Others',
        'organization_type_other' => 'Joint Venture',
        'business_type_organization' => null,
        'company_address' => null,
        'contact_address' => 'Fallback Address',
        'phone' => '09171234567',
        'email' => 'contact@example.com',
        'tin' => '123456789',
    ]);

    $payload = invokeCompanyAutofillMapper($contact, ['zip_code' => '7000'], [
        'business_organization' => 'corporation',
        'office_type' => 'head_office',
        'alternative_business_name' => 'Trade Style',
    ]);

    expect($payload['business_organization'])->toBe('other')
        ->and($payload['business_organization_other'])->toBe('Joint Venture')
        ->and($payload['office_type'])->toBe('head_office')
        ->and($payload['alternative_business_name'])->toBe('Trade Style')
        ->and($payload['zip_code'])->toBe('7000');
});

it('does not fall back business name to the contact full name when no company value exists', function () {
    $contact = new Contact([
        'first_name' => 'Chris',
        'last_name' => 'Lee',
        'company_name' => null,
        'position' => 'President',
        'contact_address' => 'Fallback Address',
        'phone' => '09171234567',
        'email' => 'contact@example.com',
    ]);

    $payload = invokeCompanyAutofillMapper($contact, [], []);

    expect($payload['business_name'])->toBeNull()
        ->and($payload['authorized_contact_person_name'])->toBe('Chris Lee');
});

it('prefers CIF signature position then contact position for authorized contact person position', function () {
    $contact = new Contact([
        'first_name' => 'Chris',
        'last_name' => 'Lee',
        'company_name' => 'Contact Company',
        'position' => 'Managing Director',
        'phone' => '09171234567',
        'email' => 'contact@example.com',
    ]);

    $payloadFromSignature = invokeCompanyAutofillMapper($contact, [
        'sig_position_left' => 'Authorized Representative',
        'nature_of_work_business' => 'Consulting',
    ], []);

    $payloadFromContact = invokeCompanyAutofillMapper($contact, [
        'nature_of_work_business' => 'Consulting',
    ], []);

    expect($payloadFromSignature['authorized_contact_person_position'])->toBe('Authorized Representative')
        ->and($payloadFromContact['authorized_contact_person_position'])->toBe('Managing Director');
});
