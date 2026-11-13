<?php

namespace App\Filament\Resources\PersonalBlogs\Pages;

use App\Filament\Resources\PersonalBlogs\PersonalBlogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalBlogs extends ListRecords
{
    protected static string $resource = PersonalBlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
