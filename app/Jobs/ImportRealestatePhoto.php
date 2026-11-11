<?php

namespace App\Jobs;

use App\Models\Realestate\Realestate;
use App\Support\Realestate\PhotoQuality;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportRealestatePhoto implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 70;
    public int $tries = 1;
    public int $uniqueFor = 3600;

    public function __construct(
        public int $realestateId,
        public string $url,
    ) {}

    public function uniqueId(): string
    {
        return $this->realestateId . ':' . hash('sha256', $this->url);
    }

    public function handle(): void
    {
        $property = Realestate::find($this->realestateId);

        if (! $property) {
            return;
        }

        $sourceKey = basename((string) parse_url($this->url, PHP_URL_PATH));

        $existing = $property->getMedia('photos')->first(
            fn ($media): bool => $media->getCustomProperty('source_url') === $this->url
                || ($sourceKey !== '' && $media->getCustomProperty('source_key') === $sourceKey)
        );

        if ($existing && ! PhotoQuality::isUpgrade(
            $this->url, $existing->getCustomProperty('source_url')
        )) {
            return;
        }

        try {
            $path = $this->downloadImage($this->url);

            try {
                $extension = match (mime_content_type($path)) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                // Save the replacement first. If the CDN or media conversion
                // fails, the existing photograph remains available.
                $media = $property->addMedia($path)
                    ->usingFileName(Str::random(24) . '.' . $extension)
                    ->withCustomProperties([
                        'source_url' => $this->url,
                        'source_key' => $sourceKey,
                    ])
                    ->toMediaCollection('photos', 'public');

                if ($existing) {
                    $media->order_column = $existing->order_column;
                    $media->save();
                    $existing->delete();
                }

                $property->unsetRelation('media');

                if (! $property->getFirstMedia('cover') && ($first = $property->getFirstMedia('photos'))) {
                    $first->copy($property, 'cover', 'public');
                }
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        } catch (Throwable $e) {
            // One failed image must not fail the whole object or the queue.
            Log::warning('Realestate photo import failed', [
                'realestate_id' => $this->realestateId,
                'image_url' => $this->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function downloadImage(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed = config('property_import.image_hosts', []);

        if (($parts['scheme'] ?? null) !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || ! in_array($host, $allowed, true)) {
            throw new RuntimeException('Image host not allowed');
        }

        $response = Http::withOptions(['stream' => true, 'allow_redirects' => false])
            ->connectTimeout(5)
            ->timeout(20)
            ->get($url);

        if (! $response->successful() || ! str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/')) {
            throw new RuntimeException('Invalid image response');
        }

        $path = tempnam(sys_get_temp_dir(), 'property-photo-');
        if (! $path) {
            throw new RuntimeException('Cannot create temp file');
        }

        try {
            $stream = $response->toPsrResponse()->getBody();
            $maxBytes = config('property_import.max_image_bytes', 15728640);
            $count = 0;
            $file = fopen($path, 'wb');

            if (! $file) {
                throw new RuntimeException('Cannot open temp file');
            }

            try {
                while (! $stream->eof()) {
                    $chunk = $stream->read(8192);
                    if ($chunk === '') {
                        break;
                    }

                    $count += strlen($chunk);
                    if ($count > $maxBytes) {
                        throw new RuntimeException('Image is too large');
                    }
                    if (fwrite($file, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException('Failed writing image');
                    }
                }
            } finally {
                fclose($file);
            }

            $mime = mime_content_type($path);
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || ! @getimagesize($path)) {
                throw new RuntimeException('Unsupported image type');
            }

            return $path;
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }
}
