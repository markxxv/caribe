<?php

namespace App\Filament\Resources\Realestate;

use App\Filament\Resources\Realestate\Pages\CreateRealestate;
use App\Filament\Resources\Realestate\Pages\EditRealestate;
use App\Filament\Resources\Realestate\Pages\ListRealestates;
use App\Filament\Resources\Realestate\Schemas\RealestateForm;
use App\Filament\Resources\Realestate\Tables\RealestatesTable;
use App\Models\Realestate\Realestate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RealestateResource extends Resource
{
    protected static ?string $model = Realestate::class;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-building-community';

    protected static ?string $navigationLabel = 'Объекты';

    protected static ?string $modelLabel = 'Объект недвижимости';

    protected static ?string $pluralModelLabel = 'Объекты недвижимости';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return 'Недвижимость';
    }

    public static function form(Schema $schema): Schema
    {
        return RealestateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RealestatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRealestates::route('/'),
            'create' => CreateRealestate::route('/create'),
            'edit' => EditRealestate::route('/{record}/edit'),
        ];
    }
}
