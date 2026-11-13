<?php

namespace App\Filament\Resources\Tours;

use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Filament\Resources\Tours\Pages\EditTour;
use App\Filament\Resources\Tours\Pages\ListTours;
use App\Filament\Resources\Tours\Schemas\TourForm;
use App\Filament\Resources\Tours\Tables\ToursTable;
use App\Models\Tour;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Collection;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Builder as Matrix;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Repeater\TableColumn;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Tour';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'default' => 1,
                'xl' => 3,
                '2xl' => 3,
            ])->schema([

                // ЛЕВАЯ КОЛОНКА (2/3): контент + SEO в табах
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255),

                        Matrix::make('content')
                            ->label('Блоки')
                            ->blocks([
                                Matrix\Block::make('text')
                                    ->label('Текст')
                                    ->schema([
                                        RichEditor::make('text')
                                            ->toolbarButtons([
                                                ['h2', 'h3'],
                                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                                ['bulletList', 'orderedList'],
                                                ['undo', 'redo'],
                                            ])
                                            ->hiddenLabel()
                                            ->required(),
                                    ]),

                                Matrix\Block::make('image')
                                    ->label('Изображение')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('image')
                                            ->hiddenLabel()
                                            ->collection('tour_builder_image')
                                            ->image()
                                            ->imageEditor()
                                            ->downloadable()
                                            ->openable()
                                            ->maxFiles(1),
                                    ]),

                                Matrix\Block::make('gallery')
                                    ->label('Галерея')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('images')
                                            ->hiddenLabel()
                                            ->collection('tour_builder_gallery')
                                            ->multiple()
                                            ->image()
                                            ->imageEditor()
                                            ->reorderable()
                                            ->appendFiles()
                                            ->downloadable()
                                            ->imagePreviewHeight('140')
                                            ->openable()
                                            ->filterMediaUsing(function (Collection $media, $get): Collection {
                                                $uuids = (array) ($get('images') ?? []);
                                                return empty($uuids)
                                                    ? $media->take(0)
                                                    : $media->whereIn('id', $uuids);
                                            }),

                                        Forms\Components\Radio::make('columns')
                                            ->hiddenLabel()
                                            ->options([
                                                'columns-2' => '2 колонки',
                                                'columns-3' => '3 колонки',
                                                'columns-4' => '4 колонки',
                                            ])
                                            ->default('columns-2')
                                            ->inline(),
                                    ]),

                                Matrix\Block::make('quote')
                                    ->label('Цитата')
                                    ->schema([
                                        RichEditor::make('text')
                                            ->hiddenLabel()
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'underline', 'strike'],
                                                ['undo', 'redo'],
                                            ])
                                            ->required(),
                                        TextInput::make('author')
                                            ->label('Автор')
                                            ->prefixIcon('tabler-user')
                                            ->maxLength(255),
                                    ]),

                                Matrix\Block::make('attach_record')
                                    ->label('Прикрепить записи')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Заголовок блока')
                                            ->maxLength(255),

                                        Repeater::make('items')
                                            ->hiddenLabel()
                                            ->table([
                                                TableColumn::make('Тип'),
                                                TableColumn::make('record_id')->hiddenHeaderLabel(),
                                            ])
                                            ->compact()
                                            ->schema([
                                                Select::make('model')
                                                    ->label('Тип')
                                                    ->hiddenLabel()
                                                    ->options([
                                                        \App\Models\Blog::class => 'Статья',
                                                        \App\Models\Tour::class => 'Тур',
                                                        \App\Models\News::class => 'Новость',
                                                        \App\Models\PersonalBlog::class => 'Личный блог',
                                                        \App\Models\Recipe::class => 'Рецепт',
                                                    ])
                                                    ->native(false)
                                                    ->searchable()
                                                    ->live()
                                                    ->required(),

                                                Select::make('record_id')
                                                    ->label('Запись')
                                                    ->hiddenLabel()
                                                    ->native(false)
                                                    ->searchable()
                                                    ->options(fn (Get $get) => match ($get('model')) {
                                                        \App\Models\Blog::class => \App\Models\Blog::query()->orderByDesc('id')->pluck('title', 'id')->all(),
                                                        \App\Models\Tour::class => \App\Models\Tour::query()->orderByDesc('id')->pluck('title', 'id')->all(),
                                                        \App\Models\News::class => \App\Models\News::query()->orderByDesc('id')->pluck('title', 'id')->all(),
                                                        \App\Models\PersonalBlog::class => \App\Models\PersonalBlog::query()->orderByDesc('id')->pluck('title', 'id')->all(),
                                                        \App\Models\Recipe::class => \App\Models\Recipe::query()->orderByDesc('id')->pluck('title', 'id')->all(),
                                                        default => [],
                                                    })
                                                    ->required(),
                                            ])
                                            ->addActionLabel('Добавить запись')
                                            ->reorderable(),
                                    ]),

                                Matrix\Block::make('list')
                                    ->label('Список')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Заголовок списка')
                                            ->maxLength(255),

                                        Repeater::make('items')
                                            ->hiddenLabel()
                                            ->schema([
                                                Hidden::make('key')
                                                    ->dehydrated(true)
                                                    ->afterStateHydrated(function ($state, $set) {
                                                        if (blank($state)) {
                                                            $set('key', (string) Str::uuid());
                                                        }
                                                    }),

                                                FileUpload::make('photo')
                                                    ->label('Фото')
                                                    ->disk('public')
                                                    ->directory('tour/list')
                                                    ->visibility('public')
                                                    ->image()
                                                    ->imagePreviewHeight('120'),

                                                TextInput::make('heading')
                                                    ->label('Заголовок')
                                                    ->maxLength(255),

                                                Textarea::make('text')
                                                    ->hiddenLabel()
                                                    ->rows(3),
                                            ])
                                            ->addActionLabel('Добавить')
                                            ->reorderable(),

                                    ]),

                            ])
                            ->collapsible()
                            ->collapsed(false),

                        Section::make('SEO')
                            ->icon('tabler-brand-google')
                            ->schema([
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('tabler-link'),

                                Forms\Components\TextInput::make('meta_title')
                                    ->label('Meta title')
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Meta description')
                                    ->maxLength(255)
                                    ->rows(3),
                            ])->collapsible()->collapsed(true),




                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 2,
                    ]),

                // ПРАВАЯ КОЛОНКА (1/3): настройки
                Section::make('Настройки')
                    ->schema([
                        Toggle::make('active')
                            ->label('Опубликовано')
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('tabler-bolt')
                            ->offIcon('tabler-power')
                            ->default(true),

                        // many-to-many с отдельным pivot blog_country
                        Select::make('countries')
                            ->label('Страны')
                            ->prefixIcon('tabler-world')
                            ->relationship('countries', 'title')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->native(false),

                        SpatieMediaLibraryFileUpload::make('tour_cover')
                            ->label('Обложка')
                            ->collection('tour_cover')
                            ->image()
                            ->imagePreviewHeight('280')
                            ->imageEditor()
                            ->downloadable()
                            ->openable()
                            ->removeUploadedFileButtonPosition('right'),
                    ])
                    ->columnSpan(['xl' => 1, '2xl' => 1]),
            ])->columnSpanFull(),
        ]);
    }


    public static function table(Table $table): Table
    {
        return ToursTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTours::route('/'),
            'create' => CreateTour::route('/create'),
            'edit' => EditTour::route('/{record}/edit'),
        ];
    }
}
