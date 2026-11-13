<?php

namespace App\Filament\Resources\BlogCategories\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Section::make('Описание')
                        ->icon('tabler-align-left')
                        ->schema([
                            TextInput::make('title')
                                ->label('Название категории')
                                ->required()
                                ->maxLength(255),

                            RichEditor::make('body')
                                ->label('Описание')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'strike', 'link'],
                                    ['h2', 'h3'],
                                    ['bulletList', 'orderedList', 'blockquote'],
                                    ['undo', 'redo'],
                                ])
                                ->columnSpanFull(),

                            Section::make('SEO')
                                ->icon('tabler-brand-google')
                                ->schema([
                                    TextInput::make('slug')
                                        ->label('Адрес страницы (slug)')
                                        ->helperText('Создаётся автоматически из названия. Можно изменить вручную.')
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true)
                                        ->prefixIcon('tabler-link'),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 2]),

                    Section::make('Настройки')
                        ->icon('tabler-settings')
                        ->schema([
                            Toggle::make('active')
                                ->label('Опубликовано')
                                ->onColor('success')
                                ->offColor('danger')
                                ->onIcon('tabler-bolt')
                                ->offIcon('tabler-power')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Порядок')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->prefixIcon('tabler-arrows-sort'),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 1]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
