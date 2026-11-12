<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    /**
     * Proxy pencarian alamat Nominatim dengan caching & User-Agent
     * untuk mematuhi kebijakan penggunaan OpenStreetMap Nominatim.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $query = trim($validated['q']);
        $cacheKey = 'nominatim:'.md5(strtolower($query));

        $results = Cache::remember($cacheKey, now()->addHours(12), function () use ($query) {
            $response = Http::withHeaders([
                'User-Agent' => 'ForistaAttendance/1.0 (Forum OSIS Surakarta)',
                'Accept-Language' => 'id',
            ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 5,
                    'countrycodes' => 'id',
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json())
                ->map(fn (array $item) => [
                    'display_name' => $item['display_name'],
                    'latitude' => (float) $item['lat'],
                    'longitude' => (float) $item['lon'],
                ])
                ->values()
                ->all();
        });

        return response()->json(['results' => $results]);
    }
}
