<?php

namespace Tests\Feature;

use App\Models\Realestate\Realestate;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_invalid_batch_is_rejected(): void
    {
        config()->set('property_import.token', 'secret-key');
        $this->withToken('secret-key')
            ->postJson('/api/realestate/import', ['items' => []])
            ->assertUnprocessable();
    }
}
