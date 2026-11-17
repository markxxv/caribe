<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'active', 'items'];

    protected $casts = [
        'active' => 'boolean',
        'items' => 'array',
    ];

    protected $attributes = [
        'active' => true,
    ];
}
