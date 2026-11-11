<?php

namespace App\Filament\Resources\Realestate\Tables;

use App\Filament\Resources\Realestate\Schemas\RealestateForm;
use App\Models\Realestate\Realestate;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RealestatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 200])
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('Фото')
                    ->collection('cover')
                    ->conversion('thumb')
                    ->size(72),

                TextColumn::make('title')
                    ->label('Объект')
                    ->searchable()
                    ->description(fn (Realestate $record): ?string => $record->address)
                    ->wrap()
                    ->limit(55),

                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('property_type')
                    ->label('Тип')
                    ->formatStateUsing(fn (?string $state): string => RealestateForm::propertyTypes()[$state ?? ''] ?? $state ?? '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('operation')
                    ->label('Сделка')
                    ->formatStateUsing(fn (?string $state): string => RealestateForm::operationTypes()[$state ?? ''] ?? $state ?? '—')
                    ->badge(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state, Realestate $record): string => $state === null
                        ? '—'
                        : number_format((float) $state, 0, ',', ' ') . ($record->currency ? ' ' . $record->currency : ''))
                    ->sortable(),

                TextColumn::make('city.name')
                    ->label('Город')
                    ->placeholder('—'),

                TextColumn::make('covered_area')
                    ->label('Крытая площадь')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' м²')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('active')
                    ->label('Публ.')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('newbuild')
                    ->label('Нов.')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('sold')
                    ->label('Продано')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('source_platform')
                    ->label('Источник')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property_type')
                    ->label('Вид недвижимости')
                    ->options(RealestateForm::propertyTypes()),

                SelectFilter::make('operation')
                    ->label('Тип сделки')
                    ->options(RealestateForm::operationTypes()),

                SelectFilter::make('country_id')
                    ->label('Страна')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('region_id')
                    ->label('Регион')
                    ->relationship('region', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('active')
                    ->label('Публикация')
                    ->trueLabel('Опубликовано')
                    ->falseLabel('Скрыто'),

                TernaryFilter::make('newbuild')
                    ->label('Новостройка')
                    ->trueLabel('Да')
                    ->falseLabel('Нет'),

                TernaryFilter::make('sold')
                    ->label('Продано')
                    ->trueLabel('Да')
                    ->falseLabel('Нет'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Опубликовать')
                        ->icon('tabler-bolt')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unpublish')
                        ->label('Снять с публикации')
                        ->icon('tabler-power')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
