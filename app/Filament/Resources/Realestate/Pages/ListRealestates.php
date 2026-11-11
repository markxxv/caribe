<?php

namespace App\Filament\Resources\Realestate\Pages;

use App\Filament\Resources\Realestate\RealestateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRealestates extends ListRecords
{
    protected static string $resource = RealestateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
