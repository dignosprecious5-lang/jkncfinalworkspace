<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\ServiceRequirement;

class TestRequirementsGate extends Command
{
    protected $signature = 'test:requirements-gate';
    protected $description = 'Run integration tests for Phase 3 #6 Requirements Gate';

    public function handle()
    {
        $this->info("Starting Integration Test for Requirements Gate...");

        // Setup temporary isolated test data with service_area included
        $service = Service::create([
            'service_code' => 'TEST-REQ-001',
            'name' => 'Test Service for Requirements',
            'service_area' => 'Tax Services',
            'engagement_behavior' => 'regular',
            'status' => 'draft',
        ]);

        $version = ServiceVersion::create([
            'service_id' => $service->id,
            'version_number' => 'V1.0',
            'status' => 'draft',
            'is_active' => true,
        ]);
        $service->update(['active_version_id' => $version->id]);

        $this->line("Created temporary Service #{$service->id} and Version #{$version->id}");

        // CASE A: ZERO requirements -> Gate FAIL
        $resultA = $version->isRequirementsComplete();
        $this->info("Case A (Zero requirements): " . ($resultA === false ? "PASS" : "FAIL") . " (Gate Result: " . ($resultA ? 'true' : 'false') . ")");

        // CASE C: Mandatory requirement, file_required=true, file_path=null -> Gate FAIL
        $req1 = ServiceRequirement::create([
            'service_version_id' => $version->id,
            'requirement_name' => 'ID Proof',
            'is_mandatory' => true,
            'file_required' => true,
            'file_path' => null,
            'status' => 'pending',
            'client_type' => 'All'
        ]);
        $resultC = $version->isRequirementsComplete();
        $this->info("Case C (Mandatory, file required, no path): " . ($resultC === false ? "PASS" : "FAIL") . " (Gate Result: " . ($resultC ? 'true' : 'false') . ")");

        // CASE D: Same requirement with valid file_path -> Requirement PASS
        $req1->update(['file_path' => 'uploads/id_proof.jpg']);
        $resultD = $version->isRequirementsComplete();
        $this->info("Case D (Mandatory, file required, has path): " . ($resultD === true ? "PASS" : "FAIL") . " (Gate Result: " . ($resultD ? 'true' : 'false') . ")");

        // CASE E: Mandatory requirement with rejected status -> Gate FAIL
        $req1->update(['status' => 'rejected']);
        $resultE = $version->isRequirementsComplete();
        $this->info("Case E (Mandatory, rejected status): " . ($resultE === false ? "PASS" : "FAIL") . " (Gate Result: " . ($resultE ? 'true' : 'false') . ")");

        // CASE F: Mandatory requirement with rejection_reason -> Gate FAIL
        $req1->update(['status' => 'pending', 'rejection_reason' => 'Blurry image']);
        $resultF = $version->isRequirementsComplete();
        $this->info("Case F (Mandatory, has rejection reason): " . ($resultF === false ? "PASS" : "FAIL") . " (Gate Result: " . ($resultF ? 'true' : 'false') . ")");

        // CASE G: Multiple mandatory requirements where one is incomplete -> Gate FAIL & identifies missing
        $req1->update(['rejection_reason' => null]); // req1 is now satisfied
        $req2 = ServiceRequirement::create([
            'service_version_id' => $version->id,
            'requirement_name' => 'Business Permit',
            'is_mandatory' => true,
            'file_required' => true,
            'file_path' => null, // incomplete
            'client_type' => 'All'
        ]);
        $resultG = $version->isRequirementsComplete();
        $missing = $version->getMissingRequirements();
        $missingNames = $missing->pluck('requirement_name')->implode(', ');
        $passedG = ($resultG === false && $missing->count() === 1 && $missingNames === 'Business Permit');
        $this->info("Case G (Multiple, 1 incomplete): " . ($passedG ? "PASS" : "FAIL") . " (Identified Missing: {$missingNames})");

        // CASE H: All mandatory requirements satisfied -> Gate PASS
        $req2->update(['file_path' => 'uploads/permit.pdf']);
        $resultH = $version->isRequirementsComplete();
        $this->info("Case H (All satisfied): " . ($resultH === true ? "PASS" : "FAIL") . " (Gate Result: " . ($resultH ? 'true' : 'false') . ")");

        // Controller submission simulation
        $this->line("--- Controller Submission Check ---");
        $req2->update(['file_path' => null]); // make incomplete again
        if (!$version->isRequirementsComplete()) {
            $miss = $version->getMissingRequirements()->pluck('requirement_name')->implode(', ');
            $this->info("PASS: Controller approval submission correctly BLOCKED. Error message would be: 'Requirements Gate Pending: Please complete all mandatory requirements before submitting this service for approval. Missing or unsatisfied: {$miss}'");
        } else {
            $this->error("FAIL: Controller submission was not blocked.");
        }

        $req2->update(['file_path' => 'uploads/permit.pdf']); // make complete
        if ($version->isRequirementsComplete()) {
            $this->info("PASS: Controller approval submission correctly ALLOWED (Gate passed).");
        } else {
            $this->error("FAIL: Controller approval was incorrectly blocked.");
        }

        // Cleanup temporary test data
        $this->line("Cleaning up temporary test records...");
        $req1->delete();
        $req2->delete();
        $version->delete();
        $service->delete();
        $this->info("Cleanup complete. No permanent database changes were left.");
    }
}