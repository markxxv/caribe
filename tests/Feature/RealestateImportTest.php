<?php

namespace Tests\Feature;

use App\Models\Realestate\Realestate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\ImportRealestatePhoto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    public function test_images_are_queued_without_blocking_the_import_api(): void
    {
        config()->set('property_import.token', 'secret-key');
        Queue::fake();
        Http::fake();

        $payload = $this->payload();
        $payload['items'][0]['photos'] = [
            'https://imgar.zonapropcdn.com/avisos/test/photo-1.jpg',
            'https://imgar.zonapropcdn.com/avisos/test/photo-2.jpg',
        ];

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk()
            ->assertJsonPath('items.0.photos_queued', 2)
            ->assertJsonPath('items.0.photos_added', 0);

        $this->assertSame(1, Realestate::count());
        Queue::assertPushed(ImportRealestatePhoto::class, 2);
        Http::assertNothingSent();
    }

    public function test_failed_photo_download_does_not_delete_the_property(): void
    {
        Http::fake(['imgar.zonapropcdn.com/*' => Http::response('Unavailable', 503)]);

        $property = Realestate::create(['title' => 'Casa con fotos', 'active' => false]);
        $job = new ImportRealestatePhoto(
            $property->id,
            'https://imgar.zonapropcdn.com/avisos/test/missing.jpg',
        );

        $job->handle();

        $this->assertDatabaseHas('property_realestates', ['id' => $property->id]);
        $this->assertCount(0, $property->fresh()->getMedia('photos'));
    }

    public function test_repeat_import_queues_upgrades_and_removes_unrelated_draft_photos(): void
    {
        config()->set('property_import.token', 'secret-key');
        Queue::fake();

        $payload = $this->payload();
        $old = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/360x266/2046926477.jpg';
        $large = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg';
        $other = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926480.jpg';
        $wrong = 'https://imgar.zonapropcdn.com/avisos/1/00/60/64/32/28/360x266/2090606899.jpg';

        $record = Realestate::create([
            'title' => 'Casa antigua',
            'source_url' => 'https://www.zonaprop.com.ar/propiedades/casa-123.html',
            'active' => false,
        ]);

        $thumbnail = $record->media()->create([
            'collection_name' => 'photos',
            'name' => '2046926477.jpg',
            'file_name' => 'old-thumb.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1234,
            'manipulations' => [],
            'custom_properties' => ['source_url' => $old, 'source_key' => '2046926477.jpg'],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $unrelated = $record->media()->create([
            'collection_name' => 'photos',
            'name' => '2090606899.jpg',
            'file_name' => 'wrong-thumb.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1234,
            'manipulations' => [],
            'custom_properties' => ['source_url' => $wrong, 'source_key' => '2090606899.jpg'],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $payload['items'][0]['photos'] = [$large, $other];
        $payload['items'][0]['photos_complete'] = true;

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('items.0.photos_queued', 2);

        $this->assertDatabaseHas('media', ['id' => $thumbnail->id]);
        $this->assertDatabaseMissing('media', ['id' => $unrelated->id]);

        Queue::assertPushed(ImportRealestatePhoto::class, 2);
        Queue::assertPushed(ImportRealestatePhoto::class, fn (ImportRealestatePhoto $job) =>
            $job->url === $large && $job->realestateId === $record->id
        );
    }

    public function test_partial_gallery_does_not_delete_existing_photos(): void
    {
        config()->set('property_import.token', 'secret-key');
        Queue::fake();

        $payload = $this->payload();
        $payload['items'][0]['photos'] = [
            'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg',
        ];
        $payload['items'][0]['photos_complete'] = false;

        $record = Realestate::create([
            'title' => 'Casa antigua',
            'source_url' => 'https://www.zonaprop.com.ar/propiedades/casa-123.html',
            'active' => false,
        ]);

        $other = $record->media()->create([
            'collection_name' => 'photos',
            'name' => 'another.jpg',
            'file_name' => 'another.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1234,
            'manipulations' => [],
            'custom_properties' => [
                'source_url' => 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/360x266/another.jpg',
                'source_key' => 'another.jpg',
            ],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', $payload)
            ->assertOk();

        $this->assertDatabaseHas('media', ['id' => $other->id]);
    }

    public function test_invalid_batch_is_rejected(): void
    {
        config()->set('property_import.token', 'secret-key');
        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', ['items' => []])
            ->assertUnprocessable();
    }
}
