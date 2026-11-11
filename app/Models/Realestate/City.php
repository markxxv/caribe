<?php

namespace App\Models\Realestate;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends BaseModel
{
    protected $table = 'property_cities';

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function realestates(): HasMany
    {
        return $this->hasMany(Realestate::class);
    }
}
