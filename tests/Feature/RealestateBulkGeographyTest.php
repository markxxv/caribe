<?php

namespace Tests\Feature;

use App\Actions\Realestate\AssignGeography;
use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\District;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RealestateBulkGeographyTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_assignment_updates_country_region_city_and_clears_only_foreign_districts(): void
    {
        $country = Country::create(['name' => 'Argentina']);
        $oldRegion = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $oldCity = City::create(['name' => 'Recoleta', 'region_id' => $oldRegion->id]);
        $oldDistrict = District::create(['name' => 'Old District', 'city_id' => $oldCity->id]);

        $newRegion = Region::create(['name' => 'Capital Federal', 'country_id' => $country->id]);
        $newCity = City::create(['name' => 'Buenos Aires', 'region_id' => $newRegion->id]);
        $newDistrict = District::create(['name' => 'Recoleta', 'city_id' => $newCity->id]);

        $first = Realestate::create([
            'title' => 'Объект один',
            'active' => false,
            'region_id' => $oldRegion->id,
            'city_id' => $oldCity->id,
            'district_id' => $oldDistrict->id,
        ]);

        $second = Realestate::create([
            'title' => 'Объект два',
            'active' => true,
            'region_id' => $newRegion->id,
            'city_id' => $newCity->id,
            'district_id' => $newDistrict->id,
        ]);

        app(AssignGeography::class)->handle(
            new Collection([$first, $second]),
            $newRegion->id,
            $newCity->id,
        );

        $this->assertDatabaseHas('property_realestates', [
            'id' => $first->id,
            'country_id' => $country->id,
            'region_id' => $newRegion->id,
            'city_id' => $newCity->id,
            'district_id' => null,
            'active' => false,
        ]);

        $this->assertDatabaseHas('property_realestates', [
            'id' => $second->id,
            'country_id' => $country->id,
            'region_id' => $newRegion->id,
            'city_id' => $newCity->id,
            'district_id' => $newDistrict->id,
            'active' => true,
        ]);
    }

    public function test_city_from_another_region_is_rejected_without_modifying_records(): void
    {
        $country = Country::create(['name' => 'Argentina']);
        $firstRegion = Region::create(['name' => 'Capital Federal', 'country_id' => $country->id]);
        $secondRegion = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Pinamar', 'region_id' => $secondRegion->id]);
        $property = Realestate::create(['title' => 'Не менять']);

        $this->expectException(ValidationException::class);

        try {
            app(AssignGeography::class)->handle(
                new Collection([$property]),
                $firstRegion->id,
                $city->id,
            );
        } finally {
            $this->assertNull($property->fresh()->region_id);
            $this->assertNull($property->fresh()->city_id);
        }
    }
}
