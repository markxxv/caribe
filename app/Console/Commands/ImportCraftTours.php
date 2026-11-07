<?php

namespace App\Console\Commands;

use App\Models\Tour;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportCraftTours extends Command
{
    protected $signature = 'import:craft-tours
        {--php artisan import:craft-tours --limit=1}
        {--source=https://visitacaribe.com/craft_db_export.php : URL на Craft export}
        {--base-url=https://visitacaribe.com : Домен для доклейки относительных url картинок}
        {--section=tours : Секция (если export поддерживает)}
        {--limit=1 : Сколько записей импортировать}
        {--timeout=60 : Таймаут HTTP (сек)}
        {--skip-images : Не скачивать изображения (для теста)}';

    protected $description = 'Импорт туров из Craft JSON: по slug без дублей, страны по slug, изображения в Media Library + list в public disk';

    public function handle(): int
    {
        $source     = (string) $this->option('source');
        $baseUrl    = rtrim((string) $this->option('base-url'), '/');
        $section    = (string) $this->option('section');
        $limit      = max(1, (int) $this->option('limit'));
        $timeout    = max(5, (int) $this->option('timeout'));
        $skipImages = (bool) $this->option('skip-images');

        $this->info("Источник: {$source}");
        $this->info("Base URL: {$baseUrl}");
        $this->info("Limit: {$limit}");
        if ($skipImages) $this->warn('Images: SKIP');

        $payload = $this->fetchJson($source, $timeout);
        if (! is_array($payload)) {
            $this->error('Не удалось получить JSON.');
            return self::FAILURE;
        }

        $entries = $this->extractEntries($payload, $section);

        if (empty($entries)) {
            $this->warn('Записей не найдено в JSON.');
            return self::SUCCESS;
        }

        $entries = array_slice($entries, 0, $limit);

        $created = 0;
        $skipped = 0;

        foreach ($entries as $entry) {
            $slug = trim((string) ($entry['slug'] ?? ''));
            if ($slug === '') {
                $this->warn('SKIP: пустой slug');
                continue;
            }

            if (Tour::query()->where('slug', $slug)->exists()) {
                $skipped++;
                $this->line("SKIP (exists): {$slug}");
                continue;
            }

            $title  = (string) ($entry['title'] ?? $slug);
            $status = (string) ($entry['status'] ?? '');
            $fields = (array) ($entry['fields'] ?? $entry);

            // 1) Создаём запись
            $tour = new Tour();
            $tour->title  = $title;
            $tour->slug   = $slug;
            $tour->active = ($status === 'live' || $status === 'active' || $status === 'published');
            $tour->content = [];

            // Duration
            $duration = $this->extractDuration($fields);
            $tour->duration_days = $duration['days'];
            $tour->duration_nights = $duration['nights'];

            $tour->save();

            // 2) Страны (по slug)
            $countrySlugs = $this->extractCountrySlugs($fields);
            if (! empty($countrySlugs)) {
                $countryIds = [];
                foreach ($countrySlugs as $c) {
                    $country = Country::query()->firstOrCreate(
                        ['slug' => $c['slug']],
                        ['title' => $c['title'] ?? $c['slug'], 'active' => true]
                    );
                    $countryIds[] = $country->id;
                }
                $tour->countries()->sync($countryIds);
            }

            // 3) Cover
            if (! $skipImages) {
                $coverUrl = $this->extractCoverUrl($fields);
                if ($coverUrl) {
                    $this->attachMediaFromUrlString(
                        tour: $tour,
                        url: $this->absoluteUrl($coverUrl, $baseUrl),
                        collection: 'tour_cover',
                        filenameHint: $this->guessFilename($coverUrl, 'cover')
                    );
                }
            }

            // 4) Builder -> content
            $builder = $this->extractBuilder($fields);
            $tour->content = $this->mapBuilder(
                builder: $builder,
                tour: $tour,
                baseUrl: $baseUrl,
                timeout: $timeout,
                skipImages: $skipImages
            );

            $tour->save();

            $created++;
            $this->info("OK: {$slug}");
        }

        $this->newLine();
        $this->info("Готово. created={$created}, skipped={$skipped}");

        return self::SUCCESS;
    }

    /* ------------------------------ JSON fetch ------------------------------ */

    private function fetchJson(string $url, int $timeout): ?array
    {
        try {
            $res = Http::timeout($timeout)->acceptJson()->get($url);
            if (! $res->successful()) {
                $this->error("HTTP {$res->status()} при запросе JSON");
                return null;
            }

            $json = $res->json();
            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return null;
        }
    }

    private function extractEntries(array $payload, string $section): array
    {
        // Формат A
        if (isset($payload['items']) && is_array($payload['items'])) {
            if (isset($payload['meta']['section']) && $payload['meta']['section'] !== $section) {
                // секция другая
            }
            return $payload['items'];
        }

        // Формат B
        if (isset($payload['sections']) && is_array($payload['sections'])) {
            foreach ($payload['sections'] as $sec) {
                if (($sec['handle'] ?? null) !== $section) continue;
                $entryType = $sec['entryTypes'][0] ?? null;
                if (isset($entryType['entries']) && is_array($entryType['entries'])) {
                    return $entryType['entries'];
                }
            }
        }

        return [];
    }

    /* --------------------------- Field extraction --------------------------- */

    private function extractDuration(array $fields): array
    {
        $duration = $fields['duration'][0] ?? null;

        if (!is_array($duration)) {
            return ['days' => null, 'nights' => null];
        }

        return [
            'days' => isset($duration['days']) ? (int) $duration['days'] : null,
            'nights' => isset($duration['nights']) ? (int) $duration['nights'] : null,
        ];
    }

    private function extractCountrySlugs(array $fields): array
    {
        $countries = $fields['country'] ?? $fields['countries'] ?? null;
        if (! is_array($countries)) return [];

        $out = [];
        foreach ($countries as $c) {
            if (! is_array($c)) continue;
            $slug = $c['slug'] ?? null;
            if (! $slug) continue;
            $out[] = [
                'slug'  => (string) $slug,
                'title' => $c['title'] ?? null,
            ];
        }

        $uniq = [];
        foreach ($out as $c) $uniq[$c['slug']] = $c;
        return array_values($uniq);
    }

    private function extractCoverUrl(array $fields): ?string
    {
        $cover = $fields['cover'] ?? $fields['tour_cover'] ?? null;
        if (is_array($cover)) {
            $first = $cover[0] ?? null;
            if (is_array($first) && ! empty($first['url'])) return (string) $first['url'];
            if (is_string($first)) return $first;
        }
        if (is_string($cover) && $cover !== '') return $cover;

        return null;
    }

    private function extractBuilder(array $fields): array
    {
        $builder = $fields['builder'] ?? $fields['contentBuilder'] ?? $fields['content'] ?? [];
        return is_array($builder) ? $builder : [];
    }

    /* ------------------------------ URL helpers ----------------------------- */

    private function absoluteUrl(string $maybeRelative, string $baseUrl): string
    {
        $u = trim($maybeRelative);
        if ($u === '') return $baseUrl;

        if (Str::startsWith($u, ['http://', 'https://'])) return $u;
        if (Str::startsWith($u, '//')) return 'https:' . $u;

        return rtrim($baseUrl, '/') . '/' . ltrim($u, '/');
    }

    private function guessFilename(string $url, string $fallbackStem): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $name = basename($path);
        if ($name && $name !== '/' && ! str_contains($name, '.php')) return $name;

        return $fallbackStem . '-' . Str::ulid() . '.jpg';
    }

    /* -------------------------- Media attach helpers ------------------------ */

    private function attachMediaFromUrlString(
        Tour $tour,
        string $url,
        string $collection,
        string $filenameHint,
        int $timeout = 60
    ): ?Media {
        try {
            $res = Http::timeout($timeout)->get($url);
            if (! $res->successful()) {
                $this->warn("IMG SKIP {$res->status()}: {$url}");
                return null;
            }

            $body = $res->body();
            if ($body === '' || $body === null) {
                $this->warn("IMG EMPTY: {$url}");
                return null;
            }

            return $tour
                ->addMediaFromString($body)
                ->usingFileName($filenameHint)
                ->toMediaCollection($collection);
        } catch (\Throwable $e) {
            $this->warn("IMG ERROR: {$url} :: " . $e->getMessage());
            return null;
        }
    }

    private function downloadToPublicDisk(string $url, string $dir, string $filenameHint, int $timeout = 60): ?string
    {
        try {
            $res = Http::timeout($timeout)->get($url);
            if (! $res->successful()) {
                $this->warn("FILE SKIP {$res->status()}: {$url}");
                return null;
            }

            $path = trim($dir, '/') . '/' . $filenameHint;
            Storage::disk('public')->put($path, $res->body());

            return $path;
        } catch (\Throwable $e) {
            $this->warn("FILE ERROR: {$url} :: " . $e->getMessage());
            return null;
        }
    }

    /* ------------------------------- Builder map ---------------------------- */

    private function mapBuilder(array $builder, Tour $tour, string $baseUrl, int $timeout, bool $skipImages): array
    {
        $out = [];

        foreach ($builder as $block) {
            if (! is_array($block)) continue;

            $type   = $block['type'] ?? $block['handle'] ?? null;
            $fields = (array) ($block['fields'] ?? $block);

            if (! $type) continue;

            // TEXT
            if ($type === 'b_text' || $type === 'text') {
                $out[] = [
                    'type' => 'text',
                    'data' => [
                        'text' => (string) ($fields['tekstovyyBlok'] ?? $fields['text'] ?? ''),
                    ],
                ];
                continue;
            }

            // QUOTE
            if ($type === 'b_quote' || $type === 'quote') {
                $out[] = [
                    'type' => 'quote',
                    'data' => [
                        'text'   => (string) ($fields['b_quote_item'] ?? $fields['text'] ?? ''),
                        'author' => (string) ($fields['b_quote_footer'] ?? $fields['author'] ?? ''),
                    ],
                ];
                continue;
            }

            // IMAGE (MediaLibrary)
            if ($type === 'b_image' || $type === 'image') {
                $asset = $fields['b_single_image'][0] ?? $fields['image'][0] ?? $fields['image'] ?? null;
                $rel   = is_array($asset) ? ($asset['url'] ?? null) : (is_string($asset) ? $asset : null);

                $ids = [];

                if (! $skipImages && $rel) {
                    $abs = $this->absoluteUrl((string) $rel, $baseUrl);
                    $media = $this->attachMediaFromUrlString(
                        tour: $tour,
                        url: $abs,
                        collection: 'tour_builder_image',
                        filenameHint: $this->guessFilename((string) $rel, 'image'),
                        timeout: $timeout
                    );
                    if ($media) $ids[] = $media->id;
                }

                $out[] = [
                    'type' => 'image',
                    'data' => [
                        'image' => $ids,
                    ],
                ];
                continue;
            }

            // GALLERY (MediaLibrary) + columns
            if ($type === 'b_gallery' || $type === 'gallery') {
                $assets  = $fields['b_gallery_field'] ?? $fields['images'] ?? $fields['gallery'] ?? [];
                $columns = (string) ($fields['b_columns'] ?? $fields['columns'] ?? 'columns-2');

                if (! in_array($columns, ['columns-2', 'columns-3', 'columns-4'], true)) {
                    $columns = 'columns-2';
                }

                $ids = [];

                if (! $skipImages && is_array($assets)) {
                    foreach ($assets as $a) {
                        $rel = is_array($a) ? ($a['url'] ?? null) : (is_string($a) ? $a : null);
                        if (! $rel) continue;

                        $abs = $this->absoluteUrl((string) $rel, $baseUrl);
                        $media = $this->attachMediaFromUrlString(
                            tour: $tour,
                            url: $abs,
                            collection: 'tour_builder_gallery',
                            filenameHint: $this->guessFilename((string) $rel, 'gallery'),
                            timeout: $timeout
                        );
                        if ($media) $ids[] = $media->id;
                    }
                }

                $out[] = [
                    'type' => 'gallery',
                    'data' => [
                        'images'  => $ids,
                        'columns' => $columns,
                    ],
                ];
                continue;
            }

            // LIST
            if ($type === 'theList' || $type === 'list') {
                $title = (string) ($fields['listTitle'] ?? $fields['title'] ?? '');

                $itemsRaw = $fields['listItems'] ?? $fields['items'] ?? [];
                $items = [];

                if (is_array($itemsRaw)) {
                    foreach ($itemsRaw as $row) {
                        $rf = (array) ($row['fields'] ?? $row);

                        $img = $rf['listImage'][0] ?? $rf['photo'][0] ?? $rf['photo'] ?? null;
                        $rel = is_array($img) ? ($img['url'] ?? null) : (is_string($img) ? $img : null);

                        $path = null;
                        if (! $skipImages && $rel) {
                            $abs = $this->absoluteUrl((string) $rel, $baseUrl);
                            $path = $this->downloadToPublicDisk(
                                url: $abs,
                                dir: 'tour/list',
                                filenameHint: $this->guessFilename((string) $rel, 'list'),
                                timeout: $timeout
                            );
                        }

                        $items[] = [
                            'photo'   => $path,
                            'heading' => (string) ($rf['itemTitle'] ?? $rf['heading'] ?? ''),
                            'text'    => (string) ($rf['itemText'] ?? $rf['text'] ?? ''),
                        ];
                    }
                }

                $out[] = [
                    'type' => 'list',
                    'data' => [
                        'title' => $title,
                        'items' => $items,
                    ],
                ];
                continue;
            }

            // ATTACH RECORDS (Blog + Tour)
            if ($type === 'attachNodes' || $type === 'attach_record') {
                $title = (string) ($fields['attachedTitle'] ?? $fields['title'] ?? '');

                $nodes = $fields['attachedNodes'] ?? $fields['items'] ?? [];
                $items = [];

                if (is_array($nodes)) {
                    foreach ($nodes as $n) {
                        $nf = (array) ($n['fields'] ?? $n);
                        $slug = $nf['slug'] ?? null;
                        $sectionHandle = $nf['sectionHandle'] ?? null;

                        if (! $slug) continue;

                        $model = null;
                        $id = null;

                        // Определяем модель по sectionHandle
                        if ($sectionHandle === 'tours') {
                            $model = \App\Models\Tour::class;
                            $id = Tour::query()->where('slug', $slug)->value('id');
                        } elseif ($sectionHandle === 'blog') {
                            $model = \App\Models\Blog::class;
                            $id = \App\Models\Blog::query()->where('slug', $slug)->value('id');
                        }

                        if (! $id || ! $model) continue;

                        $items[] = [
                            'model'     => $model,
                            'record_id' => (int) $id,
                        ];
                    }
                }

                $out[] = [
                    'type' => 'attach_record',
                    'data' => [
                        'title' => $title,
                        'items' => $items,
                    ],
                ];
                continue;
            }

            // UNKNOWN
            $out[] = [
                'type' => (string) $type,
                'data' => $fields,
            ];
        }

        return $out;
    }
}
