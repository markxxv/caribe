<?php

namespace Tests\Feature;

use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\District;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Tests\TestCase;

class RealestateModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_geography_is_related_and_realestate_has_the_expected_fields(): void
    {
        $country = Country::create(['name' => 'Argentina']);
        $region = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Pinamar', 'region_id' => $region->id]);
        $district = District::create(['name' => 'Nayades', 'city_id' => $city->id]);

        $property = Realestate::create([
            'title' => 'Casa en Pinamar',
            'country_id' => $country->id,
            'region_id' => $region->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'land_area' => 615,
            'covered_area' => 135,
            'semi_covered_area' => 20,
            'source_platform' => 'zonaprop',
            'source_url' => 'https://example.com/propiedades/60391956',
            'features' => ['pool' => true],
        ]);

        $property->refresh();

        $this->assertSame('argentina', $country->slug);
        $this->assertSame($country->id, $property->country->id);
        $this->assertSame($region->id, $property->region->id);
        $this->assertSame($city->id, $property->city->id);
        $this->assertSame($district->id, $property->district->id);
        $this->assertCount(1, $country->regions);
        $this->assertCount(1, $region->cities);
        $this->assertCount(1, $city->districts);
        $this->assertCount(1, $district->realestates);
        $this->assertTrue($property->active);
        $this->assertFalse($property->newbuild);
        $this->assertFalse($property->sold);
        $this->assertSame('615.00', $property->land_area);
        $this->assertSame('135.00', $property->covered_area);
        $this->assertSame('20.00', $property->semi_covered_area);
        $this->assertSame(['pool' => true], $property->features);
        $this->assertFalse(\Schema::hasColumn('property_realestates', 'total_area'));
    }

    public function test_source_url_is_unique(): void
    {
        Realestate::create([
            'title' => 'First house',
            'source_url' => 'https://example.com/house-1',
        ]);

        $this->expectException(QueryException::class);

        Realestate::create([
            'title' => 'Second house',
            'source_url' => 'https://example.com/house-1',
        ]);
    }

    public function test_all_models_support_cover_media_and_realestate_supports_photos(): void
    {
        foreach ([Country::class, Region::class, City::class, District::class, Realestate::class] as $class) {
            $model = new $class();
            $this->assertInstanceOf(HasMedia::class, $model);
            $model->registerMediaCollections();
            $this->assertNotNull($model->getMediaCollection('cover'));
        }

        $property = new Realestate();
        $property->registerMediaCollections();
        $this->assertNotNull($property->getMediaCollection('photos'));
    }
}
