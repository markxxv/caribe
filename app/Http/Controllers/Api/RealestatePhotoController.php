<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Realestate\Realestate;
use App\Support\Realestate\PhotoQuality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RealestatePhotoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = config('property_import.token');

        if (! is_string($expected) || $expected === ''
            || ! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'realestate_id' => ['required', 'integer', 'exists:property_realestates,id'],
            'source_url' => ['required', 'url:https', 'max:2048'],
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $url = $data['source_url'];
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || ! in_array($host, config('property_import.image_hosts', []), true)
            || ! str_starts_with($parts['path'] ?? '', '/avisos/')) {
            throw ValidationException::withMessages([
                'source_url' => 'Источник фотографии не разрешён.',
            ]);
        }

        $property = Realestate::findOrFail($data['realestate_id']);
        $sourceKey = basename((string) $parts['path']);

        $existing = $property->getMedia('photos')->first(
            fn ($media): bool => $media->getCustomProperty('source_url') === $url
                || $media->getCustomProperty('source_key') === $sourceKey
        );

        if ($existing && ! PhotoQuality::isUpgrade(
            $url, $existing->getCustomProperty('source_url')
        )) {
            return response()->json(['id' => $existing->id, 'skipped' => true]);
        }

        // The uploaded bytes come from Firefox. No outgoing CDN HTTP request
        // and no queue worker are involved.
        $image = $data['photo'];
        $media = $property->addMediaFromRequest('photo')
            ->usingFileName(Str::random(24) . '.' . $image->guessExtension())
            ->withCustomProperties([
                'source_url' => $url,
                'source_key' => $sourceKey,
            ])
            ->toMediaCollection('photos', 'public');

        if ($existing) {
            $media->order_column = $existing->order_column;
            $media->save();
            $existing->delete();
        }

        $property->unsetRelation('media');

        if (! $property->getFirstMedia('cover')
            && ($first = $property->getFirstMedia('photos'))) {
            $first->copy($property, 'cover', 'public');
        }

        return response()->json(['id' => $media->id, 'skipped' => false]);
    }
}
