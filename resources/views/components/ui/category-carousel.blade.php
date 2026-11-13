@props(['categories', 'active' => null])

@if($categories->isNotEmpty())
    <div data-category-carousel role="region" aria-roledescription="carousel" aria-label="Категории рецептов"
        class="relative mb-8 md:mb-12">
        <div data-carousel-viewport class="overflow-hidden md:mx-12">
            <ul class="flex">
                @foreach($categories as $category)
                    @php($isActive = $active === $category->slug)
                    <li data-carousel-slide @if($isActive) data-active @endif
                        class="min-w-0 shrink-0 grow-0 basis-[22%] px-1.5 md:basis-[116px] md:px-2">
                        {{-- Повторный клик по выбранной категории снимает фильтр. --}}
                        <a href="{{ $isActive ? route('recipes.index') : route('recipes.category', ['category' => $category->slug]) }}"
                            @if($isActive) aria-current="page" @endif
                            class="group block text-center">
                            <span class="isolate mx-auto block aspect-square w-full max-w-[100px] overflow-hidden rounded-full bg-[#edf3ef] transition {{ $isActive ? 'ring-2 ring-[#153b35] ring-offset-2' : '' }}">
                                @if($category->cover_url)
                                    <img src="{{ $category->cover_url }}" alt="" width="100" height="100"
                                        loading="lazy" decoding="async" draggable="false"
                                        class="size-full object-cover transition duration-300 group-hover:scale-105">
                                @endif
                            </span>
                            <span class="mt-2.5 line-clamp-3 block text-[11px] leading-tight transition md:line-clamp-3 md:text-[12px] {{ $isActive ? 'font-medium text-[#102a27]' : 'text-[#102a27]/70 group-hover:text-[#102a27]' }}">
                                {{ $category->title }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <button type="button" data-carousel-prev aria-label="Назад"
            class="absolute left-0 top-[50px] z-10 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full text-[#102a27] transition hover:bg-[#edf3ef] disabled:pointer-events-none disabled:opacity-25 md:flex">
            <x-tabler-chevron-left class="size-6" stroke-width="1.5" />
        </button>
        <button type="button" data-carousel-next aria-label="Вперёд"
            class="absolute right-0 top-[50px] z-10 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full text-[#102a27] transition hover:bg-[#edf3ef] disabled:pointer-events-none disabled:opacity-25 md:flex">
            <x-tabler-chevron-right class="size-6" stroke-width="1.5" />
        </button>
    </div>
@endif
