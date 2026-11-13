<?php

namespace App\Filament\Resources\RecipeCategories\Pages;

use App\Filament\Resources\RecipeCategories\RecipeCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecipeCategory extends EditRecord
{
    protected static string $resource = RecipeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
