<?php

namespace Tests\Feature;

use App\Models\Realestate\Realestate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealestateImportTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['items' => [[
            'source_platform' => 'zonaprop',
            'source_url' => 'https://www.zonaprop.com.ar/propiedades/casa-123.html?tracking=1',
            'title' => 'Casa en Pinamar',
            'price' => 215000,
            'currency' => 'USD',
            'country' => 'Argentina',
            'region' => 'Buenos Aires',
            'city' => 'Pinamar',
            'land_area' => 615,
            'covered_area' => 135,
            'semi_covered_area' => 20,
            'photos' => [],
        ]]];
    }

    public function test_endpoint_requires_configured_token(): void
    {
        $this->postJson('/api/realestate/import', $this->payload())->assertUnauthorized();

        config()->set('property_import.token', 'secret-key');
        $this->postJson('/api/realestate/import', $this->payload())->assertUnauthorized();

        config()->set('property_import.token', null);
        $this->withToken('secret-key')->postJson('/api/realestate/import', $this->payload())->assertUnauthorized();
    }

    public function test_import_creates_a_draft_and_reuses_existing_object_without_overwriting(): void
    {
        config()->set('property_import.token', 'secret-key');

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $this->payload())
            ->assertOk()
            ->assertJsonPath('created', 1);

        $model = Realestate::firstOrFail();
        $this->assertFalse($model->active);
        $this->assertFalse($model->sold);
        $this->assertFalse($model->newbuild);
        $this->assertSame('https://www.zonaprop.com.ar/propiedades/casa-123.html', $model->source_url);
        $this->assertSame('Pinamar', $model->city->name);
        $this->assertSame('Buenos Aires', $model->region->name);
        $this->assertSame('615.00', $model->land_area);
        $this->assertSame('135.00', $model->covered_area);
        $this->assertSame('20.00', $model->semi_covered_area);

        $model->update(['active' => true, 'title' => 'Проверенный объект']);

        $payload = $this->payload();
        $payload['items'][0]['source_url'] .= '&test=2';
        $payload['items'][0]['title'] = 'От партнёра';
        $payload['items'][0]['active'] = false;

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('existing', 1)
            ->assertJsonPath('updated', 0);

        $this->assertSame(1, Realestate::count());
        $this->assertTrue($model->fresh()->active);
        $this->assertSame('Проверенный объект', $model->fresh()->title);
    }

    public function test_reimport_protects_every_field_of_an_existing_draft_and_published_property(): void
    {
        config()->set('property_import.token', 'secret-key');

        foreach ([false, true] as $published) {
            $item = $this->payload();
            $item['items'][0]['source_url'] = 'https://www.zonaprop.com.ar/propiedades/casa-protegida-' . (int) $published . '.html';

            $this->withToken('secret-key')
                ->postJson('/api/realestate/import', $item)
                ->assertOk()
                ->assertJsonPath('created', 1);

            $property = Realestate::query()
                ->where('source_url', $item['items'][0]['source_url'])
                ->firstOrFail();

            $property->update([
                'title' => 'Título SEO editado ' . (int) $published,
                'slug' => 'manual-seo-slug-' . (int) $published,
                'description' => '<p>Авторское SEO-описание, не трогать.</p>',
                'meta_title' => 'Manual SEO meta title',
                'meta_description' => 'Manual SEO meta description',
                'price' => 321987,
                'currency' => 'EUR',
                'address' => 'Dirección editada',
                'land_area' => 444,
                'features' => ['pool' => true],
                'amenities' => ['security' => true],
                'active' => $published,
                'sold' => true,
                'newbuild' => true,
            ]);

            $before = $property->fresh()->getRawOriginal();

            $maliciousReimport = $item;
            $maliciousReimport['items'][0] = array_replace($maliciousReimport['items'][0], [
                'title' => 'Текст партнёра, который нельзя применить',
                'description' => 'Текст партнёра вместо SEO',
                'price' => 100,
                'currency' => 'USD',
                'region' => 'Capital Federal',
                'city' => 'Buenos Aires',
                'district' => 'Recoleta',
                'address' => 'No longer correct',
                'land_area' => 1000,
                'features' => ['pool' => false],
                'amenities' => ['security' => false],
                'active' => ! $published,
                'sold' => false,
                'newbuild' => false,
            ]);
            $maliciousReimport['items'][0]['source_url'] .= '?tracking=reimport';

            $this->withToken('secret-key')
                ->postJson('/api/realestate/import', $maliciousReimport)
                ->assertOk()
                ->assertJsonPath('created', 0)
                ->assertJsonPath('existing', 1)
                ->assertJsonPath('updated', 0);

            $this->assertSame($before, $property->fresh()->getRawOriginal());
        }

        $this->assertSame(2, Realestate::count());
        $this->assertSame(1, \App\Models\Realestate\Region::count());
        $this->assertSame(1, \App\Models\Realestate\City::count());
    }

    public function test_photo_reimport_does_not_delete_existing_gallery_images(): void
    {
        config()->set('property_import.token', 'secret-key');

        $payload = $this->payload();
        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk();

        $property = Realestate::firstOrFail();
        $originalUrl = 'https://imgar.zonapropcdn.com/avisos/1/00/60/64/32/28/360x266/2090606899.jpg';

        $photo = $property->media()->create([
            'collection_name' => 'photos',
            'name' => 'Photo imported earlier',
            'file_name' => 'old-image.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1234,
            'manipulations' => [],
            'custom_properties' => [
                'source_url' => $originalUrl,
                'source_key' => '2090606899.jpg',
            ],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $payload['items'][0]['photos_complete'] = true;
        $payload['items'][0]['photos'] = [
            'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg',
        ];

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk()
            ->assertJsonPath('items.0.photo_count', 1)
            ->assertJsonPath('items.0.photo_sources.0.source_url', $originalUrl);

        $this->assertDatabaseHas('media', ['id' => $photo->id]);
    }

    public function test_metadata_import_never_fetches_images_or_dispatches_jobs(): void
    {
        config()->set('property_import.token', 'secret-key');
        Http::fake();

        $payload = $this->payload();
        $payload['items'][0]['photos'] = [
            'https://imgar.zonapropcdn.com/avisos/test/photo-1.jpg',
            'https://imgar.zonapropcdn.com/avisos/test/photo-2.jpg',
        ];

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk()
            ->assertJsonPath('items.0.photos_added', 0)
            ->assertJsonPath('items.0.photo_count', 0)
            ->assertJsonCount(0, 'items.0.photo_sources');

        $this->assertSame(1, Realestate::count());
        $this->assertDatabaseCount('jobs', 0);
        Http::assertNothingSent();
    }

    public function test_firefox_can_upload_a_photo_directly_without_any_http_fetch_from_laravel(): void
    {
        config()->set('property_import.token', 'secret-key');
        Http::fake();
        Storage::fake('public');

        $property = Realestate::create([
            'title' => 'Casa con fotos',
            'active' => false,
        ]);
        $image = UploadedFile::fake()->image('large.jpg', 1200, 900);

        $this->withToken('secret-key')
            ->post('/api/realestate/import/photo', [
                'realestate_id' => $property->id,
                'source_url' => 'https://imgar.zonapropcdn.com/avisos/test/1200x1200/photo-1.jpg',
                'photo' => $image,
            ])
            ->assertOk()
            ->assertJsonPath('skipped', false);

        $this->assertCount(1, $property->fresh()->getMedia('photos'));
        $this->assertCount(1, $property->fresh()->getMedia('cover'));
        Http::assertNothingSent();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_duplicate_photo_is_skipped_without_replacement(): void
    {
        config()->set('property_import.token', 'secret-key');
        Storage::fake('public');

        $property = Realestate::create(['title' => 'Casa con fotos']);
        $url = 'https://imgar.zonapropcdn.com/avisos/test/1200x1200/photo-1.jpg';

        foreach ([1, 2] as $attempt) {
            $this->withToken('secret-key')
                ->post('/api/realestate/import/photo', [
                    'realestate_id' => $property->id,
                    'source_url' => $url,
                    'photo' => UploadedFile::fake()->image('large.jpg', 1200, 900),
                ])
                ->assertOk()
                ->assertJsonPath('skipped', $attempt === 2);
        }

        $this->assertCount(1, $property->fresh()->getMedia('photos'));
    }

    public function test_rejected_photo_does_not_remove_the_property(): void
    {
        config()->set('property_import.token', 'secret-key');
        $property = Realestate::create(['title' => 'Casa con fotos']);

        $this->withToken('secret-key')
            ->post('/api/realestate/import/photo', [
                'realestate_id' => $property->id,
                'source_url' => 'https://untrusted.example.org/unsafe.jpg',
                'photo' => UploadedFile::fake()->image('bad.jpg'),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('property_realestates', ['id' => $property->id]);
        $this->assertCount(0, $property->fresh()->getMedia('photos'));
    }

    public function test_invalid_batch_is_rejected(): void
    {
        config()->set('property_import.token', 'secret-key');
        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', ['items' => []])
            ->assertUnprocessable();
    }
}
