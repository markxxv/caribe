<?php

namespace App\Filament\Resources\Realestate\Tables;

use App\Actions\Realestate\AssignGeography;
use App\Filament\Resources\Realestate\Schemas\RealestateForm;
use App\Models\Realestate\City;
use App\Models\Realestate\Region;
use App\Models\Realestate\Realestate;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\ViewColumn;
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

                ViewColumn::make('title')
                    ->label('Объект')
                    ->view('filament.tables.columns.realestate-summary')
                    ->searchable(),

                IconColumn::make('active')
                    ->label('Публ.')
                    ->boolean()
                    ->sortable(),
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
                Action::make('preview')
                    ->label('Просмотр')
                    ->icon('tabler-external-link')
                    ->url(fn (Realestate $record): string => route('realestate.show', ['url' => $record->slug]))
                    ->openUrlInNewTab(),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_geography')
                        ->label('Изменить регион и город')
                        ->icon('tabler-map-pin')
                        ->form([
                            Select::make('region_id')
                                ->label('Регион')
                                ->options(fn (): array => Region::query()
                                    ->with('country')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Region $region): array => [
                                        $region->id => $region->name . ' · ' . ($region->country?->name ?? ''),
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->live()
                                ->required()
                                ->afterStateUpdated(fn (Set $set) => $set('city_id', null)),

                            Select::make('city_id')
                                ->label('Город')
                                ->options(fn (Get $get): array => filled($get('region_id'))
                                    ? City::query()
                                        ->where('region_id', $get('region_id'))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                    : [])
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->disabled(fn (Get $get): bool => blank($get('region_id'))),
                        ])
                        ->modalHeading('Массовое изменение географии')
                        ->modalDescription('Регион и город будут изменены у всех выбранных объектов. Страна определяется по региону, а несовместимый район будет сброшен.')
                        ->modalSubmitActionLabel('Применить ко всем')
                        ->action(function (Collection $records, array $data): void {
                            app(AssignGeography::class)->handle($records, (int) $data['region_id'], (int) $data['city_id']);

                            Notification::make()
                                ->title('География обновлена')
                                ->body('Объектов: ' . $records->count())
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

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

                    DeleteBulkAction::make()
                        ->label('Удалить выбранные')
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
