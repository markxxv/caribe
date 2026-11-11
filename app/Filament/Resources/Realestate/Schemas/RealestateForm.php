<?php

namespace App\Filament\Resources\Realestate\Schemas;

use App\Models\Realestate\City;
use App\Models\Realestate\District;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RealestateForm
{
    public static function propertyTypes(): array
    {
        return [
            'house' => 'Дом',
            'apartment' => 'Квартира',
            'townhouse' => 'Таунхаус',
            'penthouse' => 'Пентхаус',
            'duplex' => 'Дуплекс',
            'land' => 'Участок',
            'commercial' => 'Коммерческая недвижимость',
            'building' => 'Здание',
            'other' => 'Другое',
        ];
    }

    public static function operationTypes(): array
    {
        return [
            'sale' => 'Продажа',
            'rent' => 'Аренда',
        ];
    }

    private static function currencies(): array
    {
        return [
            'USD' => 'USD · Доллар',
            'ARS' => 'ARS · Аргентинское песо',
            'EUR' => 'EUR · Евро',
            'BRL' => 'BRL · Бразильский реал',
            'UYU' => 'UYU · Уругвайское песо',
            'CLP' => 'CLP · Чилийское песо',
        ];
    }

    private static function featureOptions(): array
    {
        return [
            'pool' => 'Бассейн',
            'heated_pool' => 'Бассейн с подогревом',
            'garden' => 'Сад',
            'terrace' => 'Терраса',
            'balcony' => 'Балкон',
            'barbecue' => 'Зона барбекю',
            'laundry' => 'Прачечная',
            'furnished' => 'Меблирована',
            'air_conditioning' => 'Кондиционер',
            'heating' => 'Отопление',
            'underfloor_heating' => 'Тёплый пол',
            'natural_gas' => 'Природный газ',
            'double_glazing' => 'Двойные стеклопакеты',
            'fireplace' => 'Камин',
            'storage' => 'Кладовая',
            'irrigation' => 'Автополив',
        ];
    }

    private static function amenityOptions(): array
    {
        return [
            'shared_pool' => 'Общий бассейн',
            'gym' => 'Тренажёрный зал',
            'coworking' => 'Коворкинг',
            'security' => 'Охрана',
            'gated_community' => 'Закрытый комплекс',
            'doorman' => 'Консьерж',
            'elevator' => 'Лифт',
            'playground' => 'Детская площадка',
            'sports_court' => 'Спортивная площадка',
            'spa' => 'Спа-зона',
            'electricity' => 'Электричество',
            'water' => 'Водоснабжение',
            'sewer' => 'Канализация',
        ];
    }

    private static function availableOptions(array $known, ?array $stored): array
    {
        // Unknown partner attributes remain editable instead of disappearing.
        foreach ($stored ?? [] as $key => $value) {
            $option = is_string($key) ? $key : (is_string($value) ? $value : null);

            if ($option !== null && ! array_key_exists($option, $known)) {
                $known[$option] = Str::headline($option);
            }
        }

        return $known;
    }

    private static function selectedOptions(?array $stored): array
    {
        $selected = [];

        foreach ($stored ?? [] as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $selected[] = $value;
            } elseif (is_string($key) && ($value === true || $value === 1)) {
                $selected[] = $key;
            }
        }

        return $selected;
    }

    private static function area(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->suffix('м²');
    }

    private static function count(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->integer()
            ->minValue(0);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Grid::make(1)
                        ->schema([
                        Section::make('Описание объекта')
                            ->icon('tabler-align-left')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Название объекта')
                                    ->required()
                                    ->maxLength(255),

                                RichEditor::make('description')
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
                                            ->helperText('Создаётся автоматически из названия. При необходимости можно изменить.')
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        TextInput::make('meta_title')
                                            ->label('Meta title')
                                            ->maxLength(255),

                                        Textarea::make('meta_description')
                                            ->label('Meta description')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                            Section::make('Площади')
                                ->icon('tabler-ruler-measure')
                                ->schema([
                                    self::area('land_area', 'Участок'),
                                    self::area('covered_area', 'Крытая площадь'),
                                    self::area('semi_covered_area', 'Полукрытая площадь'),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),

                            Section::make('Планировка')
                                ->icon('tabler-home-stats')
                                ->schema([
                                    self::count('rooms', 'Комнаты'),
                                    self::count('bedrooms', 'Спальни'),
                                    self::count('bathrooms', 'Ванные'),
                                    self::count('toilets', 'Гостевые туалеты'),
                                    self::count('parking_spaces', 'Машиноместа'),
                                    Select::make('parking_type')
                                        ->label('Тип парковки')
                                        ->native(false)
                                        ->options([
                                            'covered' => 'Крытая',
                                            'semi_covered' => 'Полукрытая',
                                            'uncovered' => 'Открытая',
                                        ]),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 2]),

                    Grid::make(1)
                        ->schema([
                            Section::make('Публикация')
                                ->icon('tabler-settings')
                                ->schema([
                                    Toggle::make('active')
                                        ->label('Опубликовано')
                                        ->default(true)
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->onIcon('tabler-bolt')
                                        ->offIcon('tabler-power'),

                                    Toggle::make('newbuild')
                                        ->label('Новостройка')
                                        ->default(false),

                                    Toggle::make('sold')
                                        ->label('Продано')
                                        ->default(false)
                                        ->live(),

                                    DatePicker::make('sold_at')
                                        ->label('Дата продажи')
                                        ->native(false)
                                        ->visible(fn (Get $get): bool => (bool) $get('sold')),

                                    DateTimePicker::make('published_at')
                                        ->label('Дата публикации')
                                        ->native(false),
                                ]),

                            Section::make('Тип и стоимость')
                                ->icon('tabler-cash')
                                ->schema([
                                    Select::make('operation')
                                        ->label('Тип сделки')
                                        ->options(self::operationTypes())
                                        ->native(false),

                                    Select::make('property_type')
                                        ->label('Вид недвижимости')
                                        ->options(self::propertyTypes())
                                        ->native(false)
                                        ->searchable(),

                                    TextInput::make('price')
                                        ->label('Цена')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01),

                                    Select::make('currency')
                                        ->label('Валюта')
                                        ->options(self::currencies())
                                        ->native(false)
                                        ->searchable(),

                                    TextInput::make('expenses')
                                        ->label('Ежемесячные расходы / expensas')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01),

                                    Select::make('expenses_currency')
                                        ->label('Валюта расходов')
                                        ->options(self::currencies())
                                        ->native(false)
                                        ->searchable(),
                                ]),

                            Section::make('Обложка')
                                ->icon('tabler-photo')
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('cover')
                                        ->label('Главное изображение')
                                        ->collection('cover')
                                        ->image()
                                        ->imageEditor()
                                        ->imagePreviewHeight('220')
                                        ->downloadable()
                                        ->openable(),
                                ]),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 1]),

                    Section::make('Здание и участок')
                        ->icon('tabler-building')
                        ->schema([
                            self::count('floors', 'Этажей в здании'),
                            self::count('floor_number', 'Этаж объекта'),
                            TextInput::make('construction_year')
                                ->label('Год постройки')
                                ->numeric()
                                ->integer()
                                ->minValue(1800)
                                ->maxValue(2200),

                            Select::make('condition')
                                ->label('Состояние')
                                ->native(false)
                                ->options([
                                    'new' => 'Новое / a estrenar',
                                    'excellent' => 'Отличное',
                                    'good' => 'Хорошее',
                                    'to_renovate' => 'Требует ремонта',
                                    'under_construction' => 'Строится',
                                ]),

                            Select::make('orientation')
                                ->label('Ориентация')
                                ->native(false)
                                ->options([
                                    'north' => 'Север',
                                    'south' => 'Юг',
                                    'east' => 'Восток',
                                    'west' => 'Запад',
                                    'northeast' => 'Северо-восток',
                                    'northwest' => 'Северо-запад',
                                    'southeast' => 'Юго-восток',
                                    'southwest' => 'Юго-запад',
                                ]),

                            self::area('land_front', 'Ширина участка'),
                            self::area('land_depth', 'Глубина участка'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),

                    Grid::make(['default' => 1, 'xl' => 3])
                        ->schema([
                            Section::make('Адрес')
                                ->icon('tabler-map-pin')
                                ->schema([
                                    Select::make('country_id')
                                        ->label('Страна')
                                        ->relationship('country', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function (Set $set): void {
                                            $set('region_id', null);
                                            $set('city_id', null);
                                            $set('district_id', null);
                                        }),

                                    Select::make('region_id')
                                        ->label('Регион')
                                        ->options(fn (Get $get): array => filled($get('country_id'))
                                            ? Region::query()->where('country_id', $get('country_id'))->orderBy('name')->pluck('name', 'id')->all()
                                            : [])
                                        ->searchable()
                                        ->native(false)
                                        ->disabled(fn (Get $get): bool => blank($get('country_id')))
                                        ->live()
                                        ->afterStateUpdated(function (Set $set): void {
                                            $set('city_id', null);
                                            $set('district_id', null);
                                        }),

                                    Select::make('city_id')
                                        ->label('Город')
                                        ->options(fn (Get $get): array => filled($get('region_id'))
                                            ? City::query()->where('region_id', $get('region_id'))->orderBy('name')->pluck('name', 'id')->all()
                                            : [])
                                        ->searchable()
                                        ->native(false)
                                        ->disabled(fn (Get $get): bool => blank($get('region_id')))
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('district_id', null)),

                                    Select::make('district_id')
                                        ->label('Район')
                                        ->options(fn (Get $get): array => filled($get('city_id'))
                                            ? District::query()->where('city_id', $get('city_id'))->orderBy('name')->pluck('name', 'id')->all()
                                            : [])
                                        ->searchable()
                                        ->native(false)
                                        ->disabled(fn (Get $get): bool => blank($get('city_id'))),

                                    TextInput::make('address')
                                        ->label('Улица и номер дома')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Section::make('Координаты')
                                ->icon('tabler-map')
                                ->description('Для отображения объекта на карте.')
                                ->schema([
                                    TextInput::make('latitude')
                                        ->label('Широта')
                                        ->numeric()
                                        ->minValue(-90)
                                        ->maxValue(90)
                                        ->step(0.0000001),

                                    TextInput::make('longitude')
                                        ->label('Долгота')
                                        ->numeric()
                                        ->minValue(-180)
                                        ->maxValue(180)
                                        ->step(0.0000001),
                                ])
                                ->columnSpan(['default' => 1, 'xl' => 1]),
                        ])
                        ->columnSpanFull(),

                    Section::make('Характеристики и удобства')
                        ->icon('tabler-list-check')
                        ->schema([
                            CheckboxList::make('features')
                                ->label('Особенности объекта')
                                ->options(fn (?Realestate $record): array => self::availableOptions(self::featureOptions(), $record?->features))
                                ->formatStateUsing(fn (?array $state): array => self::selectedOptions($state))
                                ->dehydrateStateUsing(fn (?array $state): array => array_fill_keys($state ?? [], true))
                                ->columns(3)
                                ->bulkToggleable(),

                            CheckboxList::make('amenities')
                                ->label('Инфраструктура и общие удобства')
                                ->options(fn (?Realestate $record): array => self::availableOptions(self::amenityOptions(), $record?->amenities))
                                ->formatStateUsing(fn (?array $state): array => self::selectedOptions($state))
                                ->dehydrateStateUsing(fn (?array $state): array => array_fill_keys($state ?? [], true))
                                ->columns(3)
                                ->bulkToggleable(),
                        ])
                        ->columnSpanFull(),

                    Section::make('Фотогалерея')
                        ->icon('tabler-photo')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('photos')
                                ->label('Фотографии объекта')
                                ->hiddenLabel()
                                ->collection('photos')
                                ->multiple()
                                ->image()
                                ->imageEditor()
                                ->reorderable()
                                ->appendFiles()
                                ->downloadable()
                                ->openable()
                                ->imagePreviewHeight('140')
                                ->helperText('Перетаскивайте изображения для изменения порядка. Первое изображение — главное в галерее.'),
                        ])
                        ->columnSpanFull(),

                    Section::make('Источник данных')
                        ->icon('tabler-database-import')
                        ->schema([
                            TextInput::make('source_platform')
                                ->label('Площадка / партнёр')
                                ->maxLength(64)
                                ->placeholder('zonaprop'),

                            TextInput::make('source_url')
                                ->label('URL оригинального объявления')
                                ->url()
                                ->maxLength(512)
                                ->unique(ignoreRecord: true)
                                ->helperText('Уникальный ключ импорта. Используйте постоянный URL без рекламных параметров.'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
