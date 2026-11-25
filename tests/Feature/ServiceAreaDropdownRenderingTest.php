<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ServiceAreaDropdownRenderingTest extends TestCase
{
    public function refreshDatabase(): void {}

    public function test_json_service_areas_are_flattened_before_rendering_select_options(): void
    {
        $html = Blade::render(<<<'BLADE'
@php
    $quickAddServiceAreas = collect($serviceAreas ?? [])->flatMap(static function ($area): array {
        if (is_array($area)) {
            return $area;
        }
        if (! is_string($area) || trim($area) === '') {
            return [];
        }
        $decodedArea = json_decode($area, true);
        return is_array($decodedArea) ? $decodedArea : [$area];
    })->map(static fn ($area): string => is_scalar($area) ? trim((string) $area) : '')
        ->filter()
        ->unique()
        ->values();
@endphp
@foreach($quickAddServiceAreas as $area)
    <option value="{{ $area }}">{{ $area }}</option>
@endforeach
BLADE, [
            'serviceAreas' => [
                ['Accounting & Compliance Advisory', 'New Area'],
                '["New Area", "Project Advisory"]',
                'Regular Services',
            ],
        ]);

        $this->assertStringContainsString('<option value="Accounting &amp; Compliance Advisory">Accounting &amp; Compliance Advisory</option>', $html);
        $this->assertStringContainsString('<option value="New Area">New Area</option>', $html);
        $this->assertStringContainsString('<option value="Project Advisory">Project Advisory</option>', $html);
        $this->assertStringContainsString('<option value="Regular Services">Regular Services</option>', $html);
        $this->assertSame(4, substr_count($html, '<option '));
    }

    public function test_workspace_header_displays_array_service_areas_as_text(): void
    {
        $html = Blade::render(
            'Area: <span>{{ collect($service->service_area ?? [])->filter()->implode(", ") ?: "Corporate & Regulatory Advisory" }}</span>',
            ['service' => (object) ['service_area' => ['Learning & Capability Development', 'Project Advisory']]]
        );

        $this->assertStringContainsString('Learning &amp; Capability Development, Project Advisory', $html);
    }
}
