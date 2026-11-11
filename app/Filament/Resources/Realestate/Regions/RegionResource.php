<?php

namespace App\Filament\Resources\Realestate\Regions;

use App\Filament\Resources\Realestate\Regions\Pages\CreateRegion;
use App\Filament\Resources\Realestate\Regions\Pages\EditRegion;
use App\Filament\Resources\Realestate\Regions\Pages\ListRegions;
use App\Filament\Resources\Realestate\Geography\GeographyForm;
use App\Filament\Resources\Realestate\Geography\GeographyTable;
use App\Models\Realestate\Region;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $slug = 'property-regions';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-map';

    protected static ?string $navigationLabel = 'Регионы';

    protected static ?string $modelLabel = 'Регион';

    protected static ?string $pluralModelLabel = 'Регионы';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return 'Недвижимость';
    }

    public static function form(Schema $schema): Schema
    {
        return GeographyForm::configure($schema, 'region');
    }

    public static function table(Table $table): Table
    {
        return GeographyTable::configure($table, 'region');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'create' => CreateRegion::route('/create'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
    }
}
