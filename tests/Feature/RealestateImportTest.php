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

    public function test_import_creates_a_draft_and_upserts_by_canonical_source_url(): void
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
            ->assertJsonPath('updated', 1);

        $this->assertSame(1, Realestate::count());
        $this->assertTrue($model->fresh()->active);
        $this->assertSame('Проверенный объект', $model->fresh()->title);
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
