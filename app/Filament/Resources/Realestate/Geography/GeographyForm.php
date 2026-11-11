<?php

namespace App\Filament\Resources\Realestate\Geography;

use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\Region;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class GeographyForm
{
    public static function configure(Schema $schema, string $level): Schema
    {
        $name = match ($level) {
            'country' => 'Страна',
            'region' => 'Регион',
            'city' => 'Город',
            'district' => 'Район',
        };

        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Section::make('Описание')
                        ->icon('tabler-align-left')
                        ->schema([
                            TextInput::make('name')
                                ->label('Название: ' . mb_strtolower($name))
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
                                    self::slugField($level),

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
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 2]),

                    Grid::make(1)
                        ->schema([
                            ...($level === 'country' ? [] : [
                                Section::make('Расположение')
                                    ->icon('tabler-map-pin')
                                    ->schema(self::parentFields($level)),
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
                                        ->openable()
                                        ->removeUploadedFileButtonPosition('right'),
                                ]),
                        ])
                        ->columnSpan(['default' => 1, 'xl' => 1]),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function slugField(string $level): TextInput
    {
        $field = TextInput::make('slug')
            ->label('Адрес страницы (slug)')
            ->helperText('Создаётся автоматически из названия. Можно изменить вручную.')
            ->maxLength(255);

        $parent = match ($level) {
            'region' => 'country_id',
            'city' => 'region_id',
            'district' => 'city_id',
            default => null,
        };

        if ($parent === null) {
            return $field->unique(ignoreRecord: true);
        }

        return $field->unique(
            ignoreRecord: true,
            modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where($parent, $get($parent)),
        );
    }

    private static function parentFields(string $level): array
    {
        if ($level === 'region') {
            return [
                Select::make('country_id')
                    ->label('Страна')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
            ];
        }

        $fields = [
            Select::make('country_id')
                ->label('Страна')
                ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->native(false)
                ->live()
                ->required()
                ->dehydrated(false)
                ->afterStateHydrated(function (Set $set, ?Model $record) use ($level): void {
                    if (! $record) {
                        return;
                    }

                    $countryId = $level === 'city'
                        ? $record->region?->country_id
                        : $record->city?->region?->country_id;

                    $set('country_id', $countryId);
                })
                ->afterStateUpdated(function (Set $set): void {
                    $set('region_id', null);
                    $set('city_id', null);
                }),

            Select::make('region_id')
                ->label('Регион')
                ->options(fn (Get $get): array => filled($get('country_id'))
                    ? Region::query()->where('country_id', $get('country_id'))->orderBy('name')->pluck('name', 'id')->all()
                    : [])
                ->searchable()
                ->native(false)
                ->live()
                ->required()
                ->disabled(fn (Get $get): bool => blank($get('country_id')))
                ->afterStateUpdated(fn (Set $set) => $set('city_id', null)),
        ];

        if ($level === 'city') {
            return $fields;
        }

        // The district only persists city_id. country_id and region_id are
        // selection helpers; they must never be written to property_districts.
        $fields[1] = $fields[1]
            ->dehydrated(false)
            ->afterStateHydrated(function (Set $set, ?Model $record): void {
                if ($record) {
                    $set('region_id', $record->city?->region_id);
                }
            });

        $fields[] = Select::make('city_id')
            ->label('Город')
            ->options(fn (Get $get): array => filled($get('region_id'))
                ? City::query()->where('region_id', $get('region_id'))->orderBy('name')->pluck('name', 'id')->all()
                : [])
            ->searchable()
            ->native(false)
            ->required()
            ->disabled(fn (Get $get): bool => blank($get('region_id')));

        return $fields;
    }
}
