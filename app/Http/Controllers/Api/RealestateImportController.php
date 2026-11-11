<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Realestate\ImportRealestate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RealestateImportController extends Controller
{
    public function __invoke(Request $request, ImportRealestate $import): JsonResponse
    {
        $expected = config('property_import.token');

        if (! is_string($expected) || $expected === '' || ! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1', 'max:5'],
            'items.*.source_platform' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/i'],
            'items.*.source_url' => ['required', 'url:http,https', 'max:512', 'distinct'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:100000'],
            'items.*.property_type' => ['nullable', 'string', 'max:64'],
            'items.*.operation' => ['nullable', 'in:sale,rent'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'string', 'size:3'],
            'items.*.expenses' => ['nullable', 'numeric', 'min:0'],
            'items.*.expenses_currency' => ['nullable', 'string', 'size:3'],
            'items.*.land_area' => ['nullable', 'numeric', 'min:0'],
            'items.*.covered_area' => ['nullable', 'numeric', 'min:0'],
            'items.*.semi_covered_area' => ['nullable', 'numeric', 'min:0'],
            'items.*.land_front' => ['nullable', 'numeric', 'min:0'],
            'items.*.land_depth' => ['nullable', 'numeric', 'min:0'],
            'items.*.rooms' => ['nullable', 'integer', 'between:0,255'],
            'items.*.bedrooms' => ['nullable', 'integer', 'between:0,255'],
            'items.*.bathrooms' => ['nullable', 'integer', 'between:0,255'],
            'items.*.toilets' => ['nullable', 'integer', 'between:0,255'],
            'items.*.parking_spaces' => ['nullable', 'integer', 'between:0,255'],
            'items.*.floors' => ['nullable', 'integer', 'between:0,255'],
            'items.*.floor_number' => ['nullable', 'integer', 'between:0,65535'],
            'items.*.construction_year' => ['nullable', 'integer', 'between:1800,2200'],
            'items.*.parking_type' => ['nullable', 'string', 'max:64'],
            'items.*.condition' => ['nullable', 'string', 'max:64'],
            'items.*.orientation' => ['nullable', 'string', 'max:64'],
            'items.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'items.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'items.*.address' => ['nullable', 'string', 'max:255'],
            'items.*.country' => ['nullable', 'string', 'max:255'],
            'items.*.region' => ['nullable', 'string', 'max:255'],
            'items.*.city' => ['nullable', 'string', 'max:255'],
            'items.*.district' => ['nullable', 'string', 'max:255'],
            'items.*.features' => ['nullable', 'array'],
            'items.*.amenities' => ['nullable', 'array'],
            'items.*.photos' => ['nullable', 'array', 'max:40'],
            'items.*.photos_complete' => ['sometimes', 'boolean'],
            'items.*.photos.*' => ['required', 'url:http,https', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data', 'errors' => $validator->errors()], 422);
        }

        $result = ['created' => 0, 'updated' => 0, 'photos_added' => 0, 'items' => [], 'errors' => []];

        foreach ($validator->validated()['items'] as $item) {
            try {
                $response = $import->handle($item);
                ++$result[$response['created'] ? 'created' : 'updated'];
                $result['photos_added'] += $response['photos_added'];
                $result['items'][] = $response;
            } catch (\Throwable $e) {
                report($e);
                $result['errors'][] = [
                    'source_url' => $item['source_url'],
                    'message' => 'Import failed. Check server logs.',
                ];
            }
        }

        return response()->json($result, $result['errors'] ? 207 : 200);
    }
}
