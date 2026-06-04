<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PhilippineLocationController extends Controller
{
    private string $baseUrl = 'https://psgc.gitlab.io/api';

    public function regions(): JsonResponse
    {
        $data = Cache::remember('psgc_regions', now()->addDays(30), function () {
            return $this->getJson('/regions.json');
        });

        $regions = collect($data)
            ->map(fn ($item) => [
                'code' => $item['code'] ?? null,
                'name' => $item['name'] ?? null,
            ])
            ->filter(fn ($item) => filled($item['code']) && filled($item['name']))
            ->values();

        return response()->json($regions);
    }

    public function provincesOrDistricts(string $regionCode): JsonResponse
    {
        $cacheKey = "psgc_provinces_or_districts_{$regionCode}";

        $payload = Cache::remember($cacheKey, now()->addDays(30), function () use ($regionCode) {
            $provinces = $this->getJson("/regions/{$regionCode}/provinces.json");

            if (!empty($provinces)) {
                return [
                    'label' => 'Province',
                    'type' => 'province',
                    'items' => collect($provinces)->map(fn ($item) => [
                        'code' => $item['code'] ?? null,
                        'name' => $item['name'] ?? null,
                        'type' => 'province',
                    ])->values()->all(),
                ];
            }

            $districts = $this->getJson("/regions/{$regionCode}/districts.json");

            return [
                'label' => 'District',
                'type' => 'district',
                'items' => collect($districts)->map(fn ($item) => [
                    'code' => $item['code'] ?? null,
                    'name' => $item['name'] ?? null,
                    'type' => 'district',
                ])->values()->all(),
            ];
        });

        return response()->json($payload);
    }

    public function allProvincesOrDistricts(): JsonResponse
    {
        $items = Cache::remember('psgc_all_provinces_or_districts', now()->addDays(30), function () {
            $regions = collect($this->getJson('/regions.json'))
                ->filter(fn ($item) => filled($item['code'] ?? null));

            return $regions->flatMap(function ($region) {
                $regionCode = $region['code'];
                $regionName = $region['name'] ?? '';
                $provinces = collect($this->getJson("/regions/{$regionCode}/provinces.json"))
                    ->map(fn ($item) => [
                        'code' => $item['code'] ?? null,
                        'name' => $item['name'] ?? null,
                        'type' => 'province',
                        'region_code' => $regionCode,
                        'region_name' => $regionName,
                    ]);

                if ($provinces->isNotEmpty()) {
                    return $provinces;
                }

                return collect($this->getJson("/regions/{$regionCode}/districts.json"))
                    ->map(fn ($item) => [
                        'code' => $item['code'] ?? null,
                        'name' => $item['name'] ?? null,
                        'type' => 'district',
                        'region_code' => $regionCode,
                        'region_name' => $regionName,
                    ]);
            })
                ->filter(fn ($item) => filled($item['code'] ?? null) && filled($item['name'] ?? null))
                ->sortBy('name')
                ->values()
                ->all();
        });

        return response()->json($items);
    }

    public function citiesMunicipalities(string $type, string $code): JsonResponse
    {
        $type = strtolower($type);

        if (!in_array($type, ['province', 'district'])) {
            return response()->json([
                'message' => 'Invalid type.',
            ], 422);
        }

        $cacheKey = "psgc_cities_municipalities_{$type}_{$code}";

        $data = Cache::remember($cacheKey, now()->addDays(30), function () use ($type, $code) {
            $endpoint = $type === 'district'
                ? "/districts/{$code}/cities-municipalities.json"
                : "/provinces/{$code}/cities-municipalities.json";

            return $this->getJson($endpoint);
        });

        $items = collect($data)->map(fn ($item) => [
            'code' => $item['code'] ?? null,
            'name' => $item['name'] ?? null,
        ])->values();

        return response()->json($items);
    }

    public function barangays(string $cityCode): JsonResponse
    {
        $cacheKey = "psgc_barangays_{$cityCode}";

        $data = Cache::remember($cacheKey, now()->addDays(30), function () use ($cityCode) {
            return $this->getJson("/cities-municipalities/{$cityCode}/barangays.json");
        });

        $items = collect($data)->map(fn ($item) => [
            'code' => $item['code'] ?? null,
            'name' => $item['name'] ?? null,
        ])->values();

        return response()->json($items);
    }

    private function getJson(string $endpoint): array
    {
        $response = Http::timeout(20)->acceptJson()->get($this->baseUrl . $endpoint);

        if (!$response->successful()) {
            return [];
        }

        return is_array($response->json()) ? $response->json() : [];
    }
}
