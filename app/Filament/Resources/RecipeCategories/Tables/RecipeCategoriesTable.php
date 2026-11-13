<?php

namespace App\Filament\Resources\RecipeCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecipeCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('recipes_count')
                    ->label('Рецепты')
                    ->counts('recipes')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                IconColumn::make('active')
                    ->label('Публ.')
                    ->boolean(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->icon('tabler-link')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Изменено')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
