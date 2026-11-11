<?php

namespace App\Filament\Resources\Realestate\Districts;

use App\Filament\Resources\Realestate\Districts\Pages\CreateDistrict;
use App\Filament\Resources\Realestate\Districts\Pages\EditDistrict;
use App\Filament\Resources\Realestate\Districts\Pages\ListDistricts;
use App\Filament\Resources\Realestate\Geography\GeographyForm;
use App\Filament\Resources\Realestate\Geography\GeographyTable;
use App\Models\Realestate\District;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $slug = 'property-districts';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-map-pin';

    protected static ?string $navigationLabel = 'Районы';

    protected static ?string $modelLabel = 'Район';

    protected static ?string $pluralModelLabel = 'Районы';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string
    {
        return 'Недвижимость';
    }

    public static function form(Schema $schema): Schema
    {
        return GeographyForm::configure($schema, 'district');
    }

    public static function table(Table $table): Table
    {
        return GeographyTable::configure($table, 'district');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDistricts::route('/'),
            'create' => CreateDistrict::route('/create'),
            'edit' => EditDistrict::route('/{record}/edit'),
        ];
    }
}
