<?php

namespace App\Filament\Resources\Realestate\Geography;

use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\District;
use App\Models\Realestate\Region;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GeographyTable
{
    public static function configure(Table $table, string $level): Table
    {
        $childRelation = match ($level) {
            'country' => 'regions',
            'region' => 'cities',
            'city' => 'districts',
            'district' => null,
        };

        $childLabel = match ($level) {
            'country' => 'Регионы',
            'region' => 'Города',
            'city' => 'Районы',
            'district' => null,
        };

        return $table
            ->defaultSort('name')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 200])
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('Обложка')
                    ->collection('cover')
                    ->conversion('thumb')
                    ->size(64),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Model $record): ?string => match ($level) {
                        'country' => null,
                        'region' => $record->country?->name,
                        'city' => collect([$record->region?->name, $record->region?->country?->name])->filter()->join(' · '),
                        'district' => collect([
                            $record->city?->name,
                            $record->city?->region?->name,
                            $record->city?->region?->country?->name,
                        ])->filter()->join(' · '),
                    }),

                TextColumn::make('realestates_count')
                    ->label('Объекты')
                    ->counts('realestates')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                ...($childRelation ? [
                    TextColumn::make($childRelation . '_count')
                        ->label($childLabel)
                        ->counts($childRelation)
                        ->sortable(),
                ] : []),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Изменено')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(match ($level) {
                'country' => [],
                'region' => [
                    SelectFilter::make('country_id')
                        ->label('Страна')
                        ->relationship('country', 'name')
                        ->searchable()
                        ->preload(),
                ],
                'city' => [
                    SelectFilter::make('region_id')
                        ->label('Регион')
                        ->relationship('region', 'name')
                        ->searchable()
                        ->preload(),
                ],
                'district' => [
                    SelectFilter::make('city_id')
                        ->label('Город')
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload(),
                ],
            })
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные')
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
