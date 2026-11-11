<?php

namespace Tests\Feature;

use App\Filament\Resources\Realestate\Cities\CityResource;
use App\Filament\Resources\Realestate\Countries\CountryResource;
use App\Filament\Resources\Realestate\Districts\DistrictResource;
use App\Filament\Resources\Realestate\Regions\RegionResource;
use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\District;
use App\Models\Realestate\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealestateGeographyResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_resources_belong_to_realestate_group_and_have_distinct_routes(): void
    {
        $resources = [
            [CountryResource::class, Country::class, 'property-countries'],
            [RegionResource::class, Region::class, 'property-regions'],
            [CityResource::class, City::class, 'property-cities'],
            [DistrictResource::class, District::class, 'property-districts'],
        ];

        foreach ($resources as [$resource, $model, $slug]) {
            $this->assertSame('Недвижимость', $resource::getNavigationGroup());
            $this->assertSame($model, $resource::getModel());
            $this->assertSame($slug, $resource::getSlug());
            $this->assertSame(['index', 'create', 'edit'], array_keys($resource::getPages()));
        }

        $this->assertNotSame(
            \App\Filament\Resources\Countries\CountryResource::getSlug(),
            CountryResource::getSlug(),
        );
    }

    public function test_geographic_relations_are_compatible_with_imported_data(): void
    {
        $country = Country::create(['name' => 'Argentina']);
        $region = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Pinamar', 'region_id' => $region->id]);
        $district = District::create(['name' => 'Pinamar Norte', 'city_id' => $city->id]);

        $this->assertSame($country->id, $region->country->id);
        $this->assertSame($country->id, $city->region->country->id);
        $this->assertSame($region->id, $district->city->region->id);
        $this->assertSame('pinamar-norte', $district->slug);
    }
}
