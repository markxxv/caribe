<?php

namespace App\Filament\Resources\PersonalBlogs\Pages;

use App\Filament\Resources\PersonalBlogs\PersonalBlogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonalBlog extends EditRecord
{
    protected static string $resource = PersonalBlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
