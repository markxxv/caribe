<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportCraftBlogs extends Command
{
    protected $signature = 'import:craft-blogs
        {--php artisan import:craft-blogs --limit=4}
        {--source=https://visitacaribe.com/craft_db_export.php : URL на Craft export}
        {--base-url=https://visitacaribe.com : Домен для доклейки относительных url картинок}
        {--section=blog : Секция (если export поддерживает)}
        {--limit=1 : Сколько записей импортировать}
        {--timeout=60 : Таймаут HTTP (сек)}
        {--skip-images : Не скачивать изображения (для теста)}';

    protected $description = 'Импорт блог-постов из Craft JSON: по slug без дублей, страны по slug, изображения в Media Library + list в public disk';

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

            if (Blog::query()->where('slug', $slug)->exists()) {
                $skipped++;
                $this->line("SKIP (exists): {$slug}");
                continue;
            }

            $title  = (string) ($entry['title'] ?? $slug);
            $status = (string) ($entry['status'] ?? '');
            $fields = (array) ($entry['fields'] ?? $entry); // иногда поля лежат прямо на верхнем уровне

            // 1) Создаём запись (сразу, чтобы media привязывались к существующей модели)
            $blog = new Blog();
            $blog->title  = $title;
            $blog->slug   = $slug;
            $blog->active = ($status === 'live' || $status === 'active' || $status === 'published');
            $blog->content = [];
            $blog->save();

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
                $blog->countries()->sync($countryIds);
            }

            // 3) Cover
            if (! $skipImages) {
                $coverUrl = $this->extractCoverUrl($fields);
                if ($coverUrl) {
                    $this->attachMediaFromUrlString(
                        blog: $blog,
                        url: $this->absoluteUrl($coverUrl, $baseUrl),
                        collection: 'blog_cover',
                        filenameHint: $this->guessFilename($coverUrl, 'cover')
                    );
                }
            }

            // 4) Builder -> content
            $builder = $this->extractBuilder($fields);
            $blog->content = $this->mapBuilder(
                builder: $builder,
                blog: $blog,
                baseUrl: $baseUrl,
                timeout: $timeout,
                skipImages: $skipImages
            );

            $blog->save();

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

    /**
     * Поддерживаем 2 формата:
     * A) { meta:{section}, items:[...] }  (как в твоих примерах)
     * B) { sections:[{handle, entryTypes:[{entries:[...]}]}] } (старый дамп)
     */
    private function extractEntries(array $payload, string $section): array
    {
        // Формат A
        if (isset($payload['items']) && is_array($payload['items'])) {
            // если export отдаёт только blog, просто берём items
            // если отдаёт несколько секций, можно фильтровать по meta.section
            if (isset($payload['meta']['section']) && $payload['meta']['section'] !== $section) {
                // секция другая — но всё равно вернём items, вдруг тебе нужно
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

        // уникализируем
        $uniq = [];
        foreach ($out as $c) $uniq[$c['slug']] = $c;
        return array_values($uniq);
    }

    private function extractCoverUrl(array $fields): ?string
    {
        // cover может быть массивом assets
        $cover = $fields['cover'] ?? $fields['blog_cover'] ?? null;
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

        // уже абсолютный
        if (Str::startsWith($u, ['http://', 'https://'])) return $u;

        // протокольный //cdn...
        if (Str::startsWith($u, '//')) return 'https:' . $u;

        // относительный
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
        Blog $blog,
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

            // без tmp файлов:
            return $blog
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

            return $path; // для FileUpload
        } catch (\Throwable $e) {
            $this->warn("FILE ERROR: {$url} :: " . $e->getMessage());
            return null;
        }
    }

    /* ------------------------------- Builder map ---------------------------- */

    private function mapBuilder(array $builder, Blog $blog, string $baseUrl, int $timeout, bool $skipImages): array
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
                        blog: $blog,
                        url: $abs,
                        collection: 'blog_builder_image',
                        filenameHint: $this->guessFilename((string) $rel, 'image'),
                        timeout: $timeout
                    );
                    if ($media) $ids[] = $media->id; // Filament Spatie upload дружит с id
                }

                $out[] = [
                    'type' => 'image',
                    'data' => [
                        'image' => $ids, // массив, даже для single
                    ],
                ];
                continue;
            }

            // GALLERY (MediaLibrary) + columns
            if ($type === 'b_gallery' || $type === 'gallery') {
                $assets  = $fields['b_gallery_field'] ?? $fields['images'] ?? $fields['gallery'] ?? [];
                $columns = (string) ($fields['b_columns'] ?? $fields['columns'] ?? 'columns-2');

                // нормализуем columns
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
                            blog: $blog,
                            url: $abs,
                            collection: 'blog_builder_gallery',
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

            // LIST (FileUpload-style path in JSON) — чтобы не мучаться с MediaLibrary внутри repeater
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
                                dir: 'blog/list',
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

            // ATTACH RECORDS (пока только Blog; резолв по slug)
            if ($type === 'attachNodes' || $type === 'attach_record') {
                $title = (string) ($fields['attachedTitle'] ?? $fields['title'] ?? '');

                $nodes = $fields['attachedNodes'] ?? $fields['items'] ?? [];
                $items = [];

                if (is_array($nodes)) {
                    foreach ($nodes as $n) {
                        $nf = (array) ($n['fields'] ?? $n);
                        $slug = $nf['slug'] ?? null;
                        if (! $slug) continue;

                        $id = Blog::query()->where('slug', $slug)->value('id');

                        // если не нашли — просто пропускаем, чтобы не ломать required в форме
                        if (! $id) continue;

                        $items[] = [
                            'model'     => Blog::class,
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

            // UNKNOWN: сохраняем сырьё (не теряем)
            $out[] = [
                'type' => (string) $type,
                'data' => $fields,
            ];
        }

        return $out;
    }
}
