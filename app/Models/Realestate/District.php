<?php

namespace App\Models\Realestate;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends BaseModel
{
    protected $table = 'property_districts';

    protected ?string $publicationParent = 'city';

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function realestates(): HasMany
    {
        return $this->hasMany(Realestate::class);
    }
}
