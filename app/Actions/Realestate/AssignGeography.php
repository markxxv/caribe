<?php

namespace App\Actions\Realestate;

use App\Models\Realestate\City;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignGeography
{
    public function handle(Collection $records, int $regionId, int $cityId): void
    {
        $region = Region::query()->find($regionId);

        if (! $region) {
            throw ValidationException::withMessages([
                'region_id' => 'Выбранный регион не найден.',
            ]);
        }

        $city = City::query()
            ->where('region_id', $region->id)
            ->find($cityId);

        if (! $city) {
            throw ValidationException::withMessages([
                'city_id' => 'Выбранный город не относится к указанному региону.',
            ]);
        }

        DB::transaction(function () use ($records, $region, $city): void {
            /** @var Realestate $record */
            foreach ($records as $record) {
                $record->update([
                    'country_id' => $region->country_id,
                    'region_id' => $region->id,
                    'city_id' => $city->id,
                    // Keep an existing district only when it belongs to the selected city.
                    'district_id' => $record->district?->city_id === $city->id
                        ? $record->district_id
                        : null,
                ]);
            }
        });
    }
}
