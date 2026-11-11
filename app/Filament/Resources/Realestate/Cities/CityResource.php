<?php

namespace App\Filament\Resources\Realestate\Cities;

use App\Filament\Resources\Realestate\Cities\Pages\CreateCity;
use App\Filament\Resources\Realestate\Cities\Pages\EditCity;
use App\Filament\Resources\Realestate\Cities\Pages\ListCities;
use App\Filament\Resources\Realestate\Geography\GeographyForm;
use App\Filament\Resources\Realestate\Geography\GeographyTable;
use App\Models\Realestate\City;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $slug = 'property-cities';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-building-skyscraper';

    protected static ?string $navigationLabel = 'Города';

    protected static ?string $modelLabel = 'Город';

    protected static ?string $pluralModelLabel = 'Города';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): string
    {
        return 'Недвижимость';
    }

    public static function form(Schema $schema): Schema
    {
        return GeographyForm::configure($schema, 'city');
    }

    public static function table(Table $table): Table
    {
        return GeographyTable::configure($table, 'city');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCities::route('/'),
            'create' => CreateCity::route('/create'),
            'edit' => EditCity::route('/{record}/edit'),
        ];
    }
}
