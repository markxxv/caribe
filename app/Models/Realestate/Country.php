<?php

namespace App\Models\Realestate;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends BaseModel
{
    protected $table = 'property_countries';

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function realestates(): HasMany
    {
        return $this->hasMany(Realestate::class);
    }
}
