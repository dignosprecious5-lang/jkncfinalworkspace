<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TransmittalV2AcknowledgmentContractTest extends TestCase
{
    private function source(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);
        self::assertIsString($contents, "Unable to read {$path}");

        return $contents;
    }

    public function test_controller_is_disabled_and_uses_expiring_scoped_tokens(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
        self::assertStringContainsString("config('transmittal_v2.enabled', false)", $controller);
        self::assertStringContainsString("where('acknowledgment_token_hash', TransmittalAcknowledgmentToken::hash(\$token))", $controller);
        self::assertStringContainsString("where('acknowledgment_expires_at', '>', now())", $controller);
        self::assertStringContainsString("whereNull('acknowledgment_revoked_at')", $controller);
        self::assertStringContainsString('$recipient->sent_at !== null', $controller);
        self::assertStringContainsString('TransmittalAcknowledgmentToken::isValid($token)', $controller);
    }

    public function test_duplicate_acknowledgments_are_checked_under_locks_before_writes(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
        $storeStart = strpos($controller, 'public function store(');
        $storeEnd = strpos($controller, 'private function recipientFor(', $storeStart);
        self::assertIsInt($storeStart);
        self::assertIsInt($storeEnd);
        $store = substr($controller, $storeStart, $storeEnd - $storeStart);

        self::assertGreaterThan(strpos($store, 'DB::transaction('), strpos($store, '$transmittal->receipts()->create('));
        self::assertStringContainsString('->lockForUpdate()', $store);
        self::assertStringContainsString('$locked->receipt()->exists()', $store);
        self::assertStringContainsString('$receiptNumbers->generate()', $store);
        self::assertStringContainsString("'receipt_no' =>", $store);
        $transaction = strpos($store, 'DB::transaction(');
        self::assertLessThan(strpos($store, 'ExternalAcknowledgment::query()->create('), $transaction);
        self::assertLessThan(strpos($store, 'TransmittalHistory::query()->create('), $transaction);
        self::assertStringNotContainsString('catch (', $store);
    }

    public function test_acknowledgment_flow_has_no_external_geolocation_or_document_path_access(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
        self::assertStringNotContainsString('ip-api.com', $controller);
        self::assertStringNotContainsString('resolveLocationFromIp', $controller);
        self::assertStringNotContainsString('Http::', $controller);
        self::assertStringNotContainsString('storage_path', $controller);
        self::assertStringNotContainsString('Storage::disk', $controller);
    }

    public function test_receipt_pdf_uses_only_v2_recipient_associations(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');
        $start = strpos($controller, 'public function receiptPdf(');
        $end = strpos($controller, 'public function receiptPdfForRecipient(', $start);
        self::assertIsInt($start);
        self::assertIsInt($end);
        $method = substr($controller, $start, $end - $start);

        self::assertStringContainsString("whereNotNull('recipient_id')", $method);
        self::assertStringContainsString('->whereKey($receipt)', $method);
        self::assertStringNotContainsString("load(['receipt'])", $method);
    }

    public function test_receipt_pdf_disables_dompdf_php_and_remote_resource_execution(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');
        $start = strpos($controller, 'private function renderReceiptPdf(');
        $end = strpos($controller, 'public function', $start + 1);
        self::assertIsInt($start);
        self::assertIsInt($end);
        $method = substr($controller, $start, $end - $start);

        self::assertStringContainsString("setOptions(['isPhpEnabled' => false, 'isRemoteEnabled' => false])", $method);
    }

    public function test_acknowledgment_and_email_views_escape_dynamic_values(): void
    {
        $acknowledgment = $this->source('resources/views/transmittal-v2/acknowledgments/show.blade.php');
        $email = $this->source('resources/views/emails/transmittal-v2/acknowledgment-request.blade.php');

        self::assertStringContainsString('@csrf', $acknowledgment);
        self::assertStringNotContainsString('{!!', $acknowledgment);
        self::assertStringNotContainsString('{!!', $email);
        self::assertStringNotContainsString('storage_path', $acknowledgment);
        self::assertStringNotContainsString('Storage::disk', $acknowledgment);
        self::assertStringContainsString('emails.transmittal-v2.acknowledgment-request', $this->source('app/Services/TransmittalV2/BrevoTransmittalService.php'));
    }

    public function test_v2_acknowledgment_routes_are_registered_behind_feature_gate(): void
    {
        $routes = $this->source('routes/transmittal-v2.php');
        self::assertStringContainsString('ExternalAcknowledgmentController', $routes);
        self::assertStringContainsString('EnsureTransmittalV2Enabled::class', $routes);
        self::assertStringContainsString("env('ORDO_TRANSMITTAL_V2_ENABLED', false)", $this->source('config/transmittal_v2.php'));
    }

    public function test_receipt_number_uses_random_suffix_and_existing_unique_index(): void
    {
        $generator = $this->source('app/Services/TransmittalV2/TransmittalReceiptNumber.php');
        $migration = $this->source('database/migrations/2026_04_10_173623_create_transmittal_receipts_table.php');

        self::assertStringContainsString('random_bytes(16)', $generator);
        self::assertStringNotContainsString("max('id')", $generator);
        self::assertMatchesRegularExpression("/receipt_no.*unique/s", $migration);
    }
}
