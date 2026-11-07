<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class PaginationDesignTest extends TestCase
{
    public function test_numbered_pagination_uses_shared_light_view(): void
    {
        $paginator = new LengthAwarePaginator(
            range(1, 10),
            30,
            10,
            2,
            ['path' => '/tours', 'query' => ['country' => 1]]
        );

        $html = $paginator->links()->render();

        $this->assertStringContainsString('Навигация по страницам', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('bg-[#102a27]', $html);
        $this->assertStringContainsString('country=1', $html);
        $this->assertStringNotContainsString('dark:', $html);
    }

    public function test_simple_pagination_uses_the_same_shared_view(): void
    {
        $paginator = new Paginator(
            range(1, 11),
            10,
            2,
            ['path' => '/blog']
        );

        $html = $paginator->links()->render();

        $this->assertStringContainsString('Навигация по страницам', $html);
        $this->assertStringContainsString('Предыдущая страница', $html);
        $this->assertStringContainsString('Следующая страница', $html);
    }

    public function test_single_page_does_not_render_pagination(): void
    {
        $paginator = new LengthAwarePaginator(
            range(1, 3),
            3,
            10,
            1,
            ['path' => '/tours']
        );

        $this->assertSame('', trim($paginator->links()->render()));
    }
}
