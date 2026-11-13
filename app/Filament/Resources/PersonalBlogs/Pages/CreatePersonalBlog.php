<?php

namespace App\Filament\Resources\PersonalBlogs\Pages;

use App\Filament\Resources\PersonalBlogs\PersonalBlogResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePersonalBlog extends CreateRecord
{
    protected static string $resource = PersonalBlogResource::class;
}
