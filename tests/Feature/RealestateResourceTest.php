<?php

namespace Tests\Feature;

use App\Filament\Resources\Realestate\Pages\CreateRealestate;
use App\Filament\Resources\Realestate\Pages\EditRealestate;
use App\Filament\Resources\Realestate\Pages\ListRealestates;
use App\Filament\Resources\Realestate\RealestateResource;
use App\Models\Realestate\Realestate;
use Tests\TestCase;

class RealestateResourceTest extends TestCase
{
    public function test_realestate_is_isolated_in_its_own_navigation_group(): void
    {
        $this->assertSame('Недвижимость', RealestateResource::getNavigationGroup());
        $this->assertSame(Realestate::class, RealestateResource::getModel());
        $this->assertSame('Объекты', RealestateResource::getNavigationLabel());
    }

    public function test_realestate_resource_has_list_create_and_edit_pages(): void
    {
        $pages = RealestateResource::getPages();

        $this->assertSame(ListRealestates::class, $pages['index']->getPage());
        $this->assertSame(CreateRealestate::class, $pages['create']->getPage());
        $this->assertSame(EditRealestate::class, $pages['edit']->getPage());
    }
}
