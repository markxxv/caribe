<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Country;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Recipe;
use App\Models\Tour;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Импорт полного экспорта Craft 3.1 (файлы {section}_{offset}.json, category_0.json) в Laravel.
 *
 *   php artisan import:craft --path=/data/export --uploads=/data/uploads
 *   php artisan import:craft --path=/data/export --uploads=/data/uploads --section=recipes --limit=5
 *
 * --uploads — папка, соответствующая URL-префиксу /uploads/ старого сайта
 *             (т.е. файл /uploads/images/node/cover/a.jpg лежит в {uploads}/images/node/cover/a.jpg).
 * Записи ищутся по craft_id, затем по slug.
 * Новые записи создаются полностью. У существующих БЕЗ --overwrite не трогается контент и медиа:
 * проставляются только craft_id, published_at, tags и восстанавливаются ссылки блоков attachNodes.
 */
class ImportCraft extends Command
{
    protected $signature = 'import:craft
        {--path=/root/export : Папка с JSON-файлами экспорта}
        {--uploads= : Локальная папка с файлами старого /uploads}
        {--base-url= : Если файла нет локально, скачать с этого домена (https://old.visitacaribe.com)}
        {--section=* : Секции (blog,news,personalBlog,recipes,tours). По умолчанию все}
        {--limit=0 : Максимум записей на секцию (0 = все)}
        {--overwrite : Пересобрать контент и медиа у УЖЕ существующих записей (по умолчанию они не трогаются)}
        {--skip-links : Не разрешать ссылки attachNodes в конце}';

    protected $description = 'Импорт всех секций Craft (blog, news, personalBlog, recipes, tours) в Laravel';

    /** section handle => [model class, collection prefix] */
    private const SECTIONS = [
        'blog'         => [Blog::class, 'blog'],
        'news'         => [News::class, 'news'],
        'personalBlog' => [PersonalBlog::class, 'personal_blog'],
        'recipes'      => [Recipe::class, 'recipe'],
        'tours'        => [Tour::class, 'tour'],
    ];

    private string $path;
    private ?string $uploads = null;
    private ?string $baseUrl = null;
    private array $categories = [];   // craft id => category item
    private array $missingFiles = [];
    private array $imported = [];     // section => [model ids]

    public function handle(): int
    {
        $this->path = rtrim((string) $this->option('path'), '/');
        $this->uploads = $this->option('uploads') ? rtrim((string) $this->option('uploads'), '/') : null;
        $this->baseUrl = $this->option('base-url') ? rtrim((string) $this->option('base-url'), '/') : null;
        $limit = max(0, (int) $this->option('limit'));

        if (! is_dir($this->path)) {
            $this->error("Нет папки {$this->path}");
            return self::FAILURE;
        }
        if (! $this->uploads && ! $this->baseUrl) {
            $this->warn('Не указаны --uploads и --base-url: картинки импортироваться не будут.');
        }

        $sections = $this->option('section') ?: array_keys(self::SECTIONS);

        $this->importCountries();

        foreach ($sections as $section) {
            if (! isset(self::SECTIONS[$section])) {
                $this->error("Неизвестная секция: {$section}");
                continue;
            }
            $this->importSection($section, $limit);
        }

        if (! $this->option('skip-links')) {
            $this->resolveLinks();
        }

        $this->newLine();
        foreach ($this->imported as $section => $ids) {
            $this->info(sprintf('%s: %d', $section, count($ids)));
        }
        if ($this->missingFiles) {
            $uniq = array_values(array_unique($this->missingFiles));
            $this->warn('Не найдено файлов: ' . count($uniq) . ' (первые 20):');
            foreach (array_slice($uniq, 0, 20) as $f) {
                $this->line("  {$f}");
            }
        }

        return self::SUCCESS;
    }

    /* ------------------------------ data loading ---------------------------- */

    private function loadItems(string $prefix): array
    {
        $files = glob("{$this->path}/{$prefix}_*.json") ?: [];
        usort($files, fn ($a, $b) => $this->fileOffset($a) <=> $this->fileOffset($b));

        $items = [];
        foreach ($files as $file) {
            $json = json_decode((string) file_get_contents($file), true);
            if (! is_array($json) || ! isset($json['items'])) {
                $this->warn("Пропущен (не JSON экспорта): {$file}");
                continue;
            }
            foreach ($json['items'] as $item) {
                $items[] = $item;
            }
        }
        return $items;
    }

    private function fileOffset(string $file): int
    {
        return (int) preg_replace('/\D+/', '', basename($file, '.json'));
    }

    /* -------------------------------- countries ----------------------------- */

    private function importCountries(): void
    {
        $items = $this->loadItems('category');
        foreach ($items as $c) {
            $this->categories[(int) $c['id']] = $c;
        }

        $n = 0;
        foreach ($items as $c) {
            if (($c['groupHandle'] ?? '') !== 'country') {
                continue;
            }
            Country::query()->firstOrCreate(
                ['slug' => $c['slug']],
                ['title' => $c['title'], 'active' => (bool) ($c['enabled'] ?? true)]
            );
            $n++;
        }
        $this->info("Страны: {$n}");
    }

    /* -------------------------------- sections ------------------------------ */

    private function importSection(string $section, int $limit): void
    {
        [$class, $prefix] = self::SECTIONS[$section];

        $items = $this->loadItems($section);
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }
        $this->info("== {$section}: " . count($items));

        foreach ($items as $entry) {
            try {
                $model = $this->importEntry($section, $class, $prefix, $entry);
                $this->imported[$section][] = $model->id;
                $this->line("OK {$section}: {$model->slug}");
            } catch (\Throwable $e) {
                $this->error("FAIL {$section} #" . ($entry['id'] ?? '?') . ': ' . $e->getMessage());
            }
        }
    }

    private function importEntry(string $section, string $class, string $prefix, array $entry): Model
    {
        $fields = (array) ($entry['fields'] ?? []);
        $slug = trim((string) $entry['slug']);

        /** @var Model $model */
        $model = $class::query()->where('craft_id', $entry['id'])->first()
            ?? $class::query()->where('slug', $slug)->first()
            ?? new $class();

        $isNew = ! $model->exists;

        if (! $isNew && ! $this->option('overwrite')) {
            return $this->touchExisting($model, $section, $entry, $fields);
        }

        $model->craft_id = (int) $entry['id'];
        $model->title = (string) $entry['title'];
        $model->slug = $slug;
        $model->active = ($entry['status'] ?? '') === 'live';
        $model->published_at = $entry['postDate'] ?? null;
        $model->created_at = $entry['dateCreated'] ?? now();
        $model->updated_at = $entry['dateUpdated'] ?? now();
        $model->content = [];

        if ($section === 'tours') {
            $row = $fields['duration'][0] ?? [];
            $model->duration_days = isset($row['days']) && $row['days'] !== '' ? (int) $row['days'] : null;
            $model->duration_nights = isset($row['nights']) && $row['nights'] !== '' ? (int) $row['nights'] : null;
        }
        if ($section === 'blog' || $section === 'recipes') {
            $model->tags = $this->termList($fields['tags'] ?? []);
        }
        if ($section === 'personalBlog') {
            $author = ($fields['nodeAuthor'][0] ?? null);
            $category = ($fields['category'][0] ?? null);
            $model->author_name = $author['title'] ?? null;
            $model->author_slug = $author['slug'] ?? null;
            $model->author_resume = $author
                ? ($this->categories[(int) $author['id']]['fields']['authorResumen'] ?? null)
                : null;
            $model->category_name = $category['title'] ?? null;
            $model->category_slug = $category['slug'] ?? null;
        }

        // quietly: не даём HasSlug перегенерировать slug и не трогаем даты
        $model->timestamps = false;
        $model->saveQuietly();

        if (! $isNew) {
            foreach (["{$prefix}_cover", "{$prefix}_builder", "{$prefix}_builder_image", "{$prefix}_builder_gallery"] as $c) {
                $model->clearMediaCollection($c);
            }
        }

        // страны
        if (method_exists($model, 'countries') && ! empty($fields['country'])) {
            $ids = [];
            foreach ($fields['country'] as $c) {
                $country = Country::query()->firstOrCreate(
                    ['slug' => $c['slug']],
                    ['title' => $c['title'] ?? $c['slug'], 'active' => true]
                );
                $ids[] = $country->id;
            }
            $model->countries()->sync($ids);
        } elseif (method_exists($model, 'countries')) {
            $model->countries()->sync([]);
        }

        // обложка
        $cover = $fields['cover'][0] ?? null;
        if ($cover) {
            $this->attachMedia($model, $cover, "{$prefix}_cover");
        }

        // билдер
        $model->content = $this->mapBuilder($fields['builder'] ?? [], $model, $prefix);
        $model->timestamps = false;
        $model->saveQuietly();

        return $model;
    }

    /**
     * Существующая запись: контент и картинки не трогаем.
     * Проставляем craft_id / published_at / tags и обновляем ссылки в блоках attach_record.
     */
    private function touchExisting(Model $model, string $section, array $entry, array $fields): Model
    {
        if (! $model->craft_id) {
            $model->craft_id = (int) $entry['id'];
        }
        if (! $model->published_at) {
            $model->published_at = $entry['postDate'] ?? null;
        }
        if (($section === 'blog' || $section === 'recipes') && empty($model->tags)) {
            $model->tags = $this->termList($fields['tags'] ?? []);
        }

        $refs = [];
        $blocks = (array) ($fields['builder'] ?? []);
        usort($blocks, fn ($a, $b) => ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0));
        foreach ($blocks as $block) {
            if (($block['blockType'] ?? '') !== 'attachNodes') {
                continue;
            }
            $items = [];
            foreach (($block['fields']['attachedNodes'] ?? []) as $n) {
                $items[] = [
                    'model' => null,
                    'record_id' => null,
                    '_craft' => ['section' => $n['sectionHandle'] ?? null, 'id' => $n['id'] ?? null],
                ];
            }
            $refs[] = $items;
        }

        $content = $model->content ?? [];
        $i = 0;
        foreach ($content as &$b) {
            if (($b['type'] ?? '') !== 'attach_record') {
                continue;
            }
            if (isset($refs[$i])) {
                $b['data']['items'] = $refs[$i];
            }
            $i++;
        }
        unset($b);

        if ($i !== count($refs)) {
            $this->warn("Блоков attach_record: в БД {$i}, в Craft " . count($refs) . " — {$section}/{$model->slug}");
        }

        $model->content = $content;
        $model->timestamps = false;
        $model->saveQuietly();

        return $model;
    }

    private function termList(array $terms): array
    {
        $out = [];
        foreach ($terms as $t) {
            if (isset($t['slug'])) {
                $out[] = ['slug' => $t['slug'], 'title' => $t['title'] ?? $t['slug']];
            }
        }
        return $out;
    }

    /* --------------------------------- builder ------------------------------ */

    private function mapBuilder(array $blocks, Model $model, string $prefix): array
    {
        usort($blocks, fn ($a, $b) => ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0));

        $out = [];
        foreach ($blocks as $block) {
            $type = $block['blockType'] ?? null;
            $f = (array) ($block['fields'] ?? []);

            switch ($type) {
                case 'b_text':
                    $text = $this->cleanHtml((string) ($f['tekstovyyBlok'] ?? ''));
                    if (trim(strip_tags($text, '<img>')) === '') {
                        break;
                    }
                    $out[] = ['type' => 'text', 'data' => ['text' => $text]];
                    break;

                case 'b_quote':
                    $out[] = ['type' => 'quote', 'data' => [
                        'text' => $this->cleanHtml((string) ($f['b_quote_item'] ?? '')),
                        'author' => (string) ($f['b_quote_footer'] ?? ''),
                    ]];
                    break;

                case 'b_image':
                    $asset = $f['b_single_image'][0] ?? null;
                    if (! $asset) {
                        break;
                    }
                    $media = $this->attachMedia($model, $asset, "{$prefix}_builder_image");
                    if ($media) {
                        $out[] = ['type' => 'image', 'data' => ['image' => [$media->id]]];
                    }
                    break;

                case 'b_gallery':
                    $ids = [];
                    foreach (($f['b_gallery_field'] ?? []) as $asset) {
                        $media = $this->attachMedia($model, $asset, "{$prefix}_builder_gallery");
                        if ($media) {
                            $ids[] = $media->id;
                        }
                    }
                    $cols = (string) ($f['b_columns'] ?? '');
                    if (! in_array($cols, ['columns-2', 'columns-3', 'columns-4'], true)) {
                        $cols = 'columns-2';
                    }
                    if ($ids) {
                        $out[] = ['type' => 'gallery', 'data' => ['images' => $ids, 'columns' => $cols]];
                    }
                    break;

                case 'theList':
                    $items = [];
                    foreach (($f['listItems'] ?? []) as $row) {
                        $rf = (array) ($row['fields'] ?? []);
                        $photo = null;
                        if (! empty($rf['listImage'][0])) {
                            $photo = $this->copyToPublic($rf['listImage'][0], "{$prefix}/list");
                        }
                        $items[] = [
                            'key' => (string) Str::uuid(),
                            'photo' => $photo,
                            'heading' => (string) ($rf['itemTitle'] ?? ''),
                            'text' => (string) ($rf['itemText'] ?? ''),
                        ];
                    }
                    $out[] = ['type' => 'list', 'data' => [
                        'title' => (string) ($f['listTitle'] ?? ''),
                        'items' => $items,
                    ]];
                    break;

                case 'attachNodes':
                    $items = [];
                    foreach (($f['attachedNodes'] ?? []) as $n) {
                        $items[] = [
                            'model' => null,
                            'record_id' => null,
                            '_craft' => ['section' => $n['sectionHandle'] ?? null, 'id' => $n['id'] ?? null],
                        ];
                    }
                    $out[] = ['type' => 'attach_record', 'data' => [
                        'title' => (string) ($f['attachedTitle'] ?? ''),
                        'items' => $items,
                    ]];
                    break;

                default:
                    // неизвестный блок не теряем
                    $out[] = ['type' => (string) $type, 'data' => $f];
            }
        }

        return $out;
    }

    private function cleanHtml(string $html): string
    {
        // U+2028/2029 из Redactor ломают JSON-вьюхи и редакторы
        return str_replace(["\u{2028}", "\u{2029}"], ' ', $html);
    }

    /* ----------------------------------- links ------------------------------ */

    private function resolveLinks(): void
    {
        $this->info('== Разрешение ссылок attachNodes');
        $resolved = 0;
        $dropped = 0;

        foreach ($this->imported as $section => $ids) {
            [$class] = self::SECTIONS[$section];
            foreach ($class::query()->whereIn('id', $ids)->cursor() as $model) {
                $content = $model->content ?? [];
                $changed = false;

                foreach ($content as &$block) {
                    if (($block['type'] ?? '') !== 'attach_record') {
                        continue;
                    }
                    $new = [];
                    foreach ($block['data']['items'] ?? [] as $item) {
                        $ref = $item['_craft'] ?? null;
                        if (! $ref) {
                            if (! empty($item['record_id'])) {
                                $new[] = $item;
                            }
                            continue;
                        }
                        $target = $ref && isset(self::SECTIONS[$ref['section'] ?? ''])
                            ? self::SECTIONS[$ref['section']][0]
                            : null;
                        $id = $target ? $target::query()->where('craft_id', $ref['id'])->value('id') : null;
                        if ($id) {
                            $new[] = ['model' => $target, 'record_id' => (int) $id];
                            $resolved++;
                        } else {
                            $dropped++;
                        }
                    }
                    $block['data']['items'] = $new;
                    $changed = true;
                }
                unset($block);

                if ($changed) {
                    $model->content = $content;
                    $model->timestamps = false;
                    $model->saveQuietly();
                }
            }
        }
        $this->info("Ссылок разрешено: {$resolved}, не найдено: {$dropped}");
    }

    /* ------------------------------------ files ----------------------------- */

    private function assetPath(array $asset): ?string
    {
        $url = (string) ($asset['url'] ?? '');
        if ($url === '') {
            return null;
        }
        return ltrim(Str::after($url, '/uploads/'), '/');
    }

    /** Возвращает [путь к локальному файлу|null, содержимое|null] */
    private function fetchAsset(array $asset): array
    {
        $rel = $this->assetPath($asset);
        if (! $rel) {
            return [null, null];
        }

        if ($this->uploads) {
            $local = "{$this->uploads}/{$rel}";
            if (is_file($local)) {
                return [$local, null];
            }
        }
        if ($this->baseUrl) {
            try {
                $res = Http::timeout(60)->get($this->baseUrl . '/uploads/' . $rel);
                if ($res->successful() && $res->body() !== '') {
                    return [null, $res->body()];
                }
            } catch (\Throwable $e) {
                // падаем в missing
            }
        }

        $this->missingFiles[] = $rel;
        return [null, null];
    }

    private function attachMedia(Model $model, array $asset, string $collection)
    {
        [$local, $body] = $this->fetchAsset($asset);
        $name = (string) ($asset['filename'] ?? basename((string) $this->assetPath($asset)));

        try {
            if ($local) {
                return $model->addMedia($local)->preservingOriginal()->usingFileName($name)->toMediaCollection($collection);
            }
            if ($body !== null) {
                return $model->addMediaFromString($body)->usingFileName($name)->toMediaCollection($collection);
            }
        } catch (\Throwable $e) {
            $this->warn("MEDIA ERROR {$name}: " . $e->getMessage());
        }
        return null;
    }

    private function copyToPublic(array $asset, string $dir): ?string
    {
        [$local, $body] = $this->fetchAsset($asset);
        $name = (string) ($asset['filename'] ?? 'file.jpg');
        $target = trim($dir, '/') . '/' . $name;

        if ($local) {
            Storage::disk('public')->put($target, (string) file_get_contents($local));
            return $target;
        }
        if ($body !== null) {
            Storage::disk('public')->put($target, $body);
            return $target;
        }
        return null;
    }
}
