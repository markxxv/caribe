<?php

namespace App\Filament\Resources\PersonalBlogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\IconColumn;

class PersonalBlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('personal_blog_cover')
                    ->label('Обложка')
                    ->collection('personal_blog_cover')
                    ->conversion('thumb')
                    ->imageWidth(96)
                    ->imageHeight(64)
                    ->extraImgAttributes(['style' => 'object-fit: cover; object-position: center top;']),
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable(),
                IconColumn::make('active')
                    ->boolean(),
                IconColumn::make('active')
                    ->label('Публ.')
                    ->boolean(),
                TextColumn::make('countries.title')
                    ->label('Страны')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3),
                TextColumn::make('slug')
                    ->icon('tabler-link')
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
