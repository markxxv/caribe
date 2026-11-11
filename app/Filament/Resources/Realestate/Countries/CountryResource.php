<?php

namespace App\Filament\Resources\Realestate\Countries;

use App\Filament\Resources\Realestate\Countries\Pages\CreateCountry;
use App\Filament\Resources\Realestate\Countries\Pages\EditCountry;
use App\Filament\Resources\Realestate\Countries\Pages\ListCountries;
use App\Filament\Resources\Realestate\Geography\GeographyForm;
use App\Filament\Resources\Realestate\Geography\GeographyTable;
use App\Models\Realestate\Country;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $slug = 'property-countries';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-world';

    protected static ?string $navigationLabel = 'Страны';

    protected static ?string $modelLabel = 'Страна';

    protected static ?string $pluralModelLabel = 'Страны';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return 'Недвижимость';
    }

    public static function form(Schema $schema): Schema
    {
        return GeographyForm::configure($schema, 'country');
    }

    public static function table(Table $table): Table
    {
        return GeographyTable::configure($table, 'country');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
