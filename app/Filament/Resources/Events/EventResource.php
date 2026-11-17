<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string | BackedEnum | null $navigationIcon = 'tabler-calendar-event';

    protected static ?string $navigationLabel = 'Мероприятия';

    protected static ?string $modelLabel = 'Подборка мероприятий';

    protected static ?string $pluralModelLabel = 'Подборки мероприятий';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Подборка')
                ->schema([
                    TextInput::make('title')
                        ->label('Заголовок подборки')
                        ->placeholder('Куда сходить в ноябре')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->prefixIcon('tabler-link')
                        ->helperText('Создаётся автоматически при сохранении. Можно изменить вручную.')
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->required(fn (?Event $record): bool => $record !== null),
                    DateTimePicker::make('created_at')
                        ->label('Дата создания')
                        ->native(false)
                        ->locale('ru')
                        ->displayFormat('d.m.Y H:i')
                        ->seconds(false)
                        ->default(fn () => now())
                        ->required(),
                    Select::make('country_id')
                        ->label('Страна')
                        ->relationship('country', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Toggle::make('active')
                        ->label('Опубликовано')
                        ->default(true),
                ])
                ->columnSpanFull(),

            Section::make('Мероприятия')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->schema([
                            DatePicker::make('date')
                                ->label('Дата')
                                ->native(false)
                                ->locale('ru')
                                ->displayFormat('j F Y')
                                ->format('Y-m-d')
                                ->required(),
                            TextInput::make('title')
                                ->label('Название')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true),
                            Textarea::make('announcement')
                                ->label('Короткий анонс')
                                ->rows(3)
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('location')
                                ->label('Место проведения')
                                ->prefixIcon('tabler-map-pin')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('map_url')
                                ->label('Ссылка на Google Maps')
                                ->prefixIcon('tabler-link')
                                ->url()
                                ->rule('url:http,https')
                                ->maxLength(2048),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Добавить мероприятие')
                        ->itemLabel(fn (array $state): string => ($state['title'] ?? null) ?: 'Новое мероприятие')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->url(fn (Event $record): ?string => $record->active ? $record->url : null)
                    ->openUrlInNewTab(),
                TextColumn::make('country.title')
                    ->label('Страна')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Мероприятий')
                    ->state(fn (Event $record): int => count($record->items)),
                IconColumn::make('active')
                    ->label('Опубликовано')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
