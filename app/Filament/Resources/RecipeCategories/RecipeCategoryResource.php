<?php

namespace App\Filament\Resources\RecipeCategories;

use App\Filament\Resources\RecipeCategories\Pages\CreateRecipeCategory;
use App\Filament\Resources\RecipeCategories\Pages\EditRecipeCategory;
use App\Filament\Resources\RecipeCategories\Pages\ListRecipeCategories;
use App\Filament\Resources\RecipeCategories\Schemas\RecipeCategoryForm;
use App\Filament\Resources\RecipeCategories\Tables\RecipeCategoriesTable;
use App\Models\RecipeCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RecipeCategoryResource extends Resource
{
    protected static ?string $model = RecipeCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-category';

    protected static ?string $navigationLabel = 'Категории';

    protected static ?string $modelLabel = 'Категория рецептов';

    protected static ?string $pluralModelLabel = 'Категории рецептов';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return 'Рецепты';
    }

    public static function form(Schema $schema): Schema
    {
        return RecipeCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecipeCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecipeCategories::route('/'),
            'create' => CreateRecipeCategory::route('/create'),
            'edit' => EditRecipeCategory::route('/{record}/edit'),
        ];
    }
}
