<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogCategoriesTable
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

                TextColumn::make('blogs_count')
                    ->label('Статьи')
                    ->counts('blogs')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                IconColumn::make('active')
                    ->label('Публ.')
                    ->boolean(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->icon('tabler-link')
                    ->url(fn ($record): string => route('blog.category', ['category' => $record->slug]))
                    ->openUrlInNewTab()
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
