<?php

namespace App\Filament\Resources\Recipes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\IconColumn;

class RecipesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('recipe_cover')
                    ->label('Обложка')
                    ->collection('recipe_cover')
                    ->conversion('thumb')
                    ->imageWidth(96)
                    ->imageHeight(64)
                    ->extraImgAttributes(['style' => 'object-fit: cover; object-position: center top;']),
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable(),
                TextColumn::make('category.title')
                    ->label('Категория')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Публ.')
                    ->boolean(),
                TextColumn::make('slug')
                    ->icon('tabler-link')
                    ->url(fn ($record): string => $record->url)
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('recipe_category_id')
                    ->label('Категория')
                    ->relationship('category', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
