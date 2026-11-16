<x-filament-widgets::widget>
    @if (count($items))
        <section aria-labelledby="quick-create-heading" class="space-y-4">
            <div>
                <h2 id="quick-create-heading" class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">Создать материал</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Выберите тип публикации</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $item)
                    <a href="{{ $item['url'] }}" wire:key="quick-create-{{ $loop->index }}" data-create-material class="group flex min-w-0 items-center gap-3 rounded-xl bg-white p-4 shadow-sm transition hover:bg-gray-50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-500 dark:bg-gray-900 dark:hover:bg-gray-800 sm:p-5">
                        <span @class(['flex size-12 shrink-0 items-center justify-center rounded-lg', $item['tone']])>
                            <x-filament::icon :icon="$item['icon']" class="size-6" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gray-950 dark:text-white">{{ $item['title'] }}</span>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $item['description'] }}</span>
                        </span>

                        <x-filament::icon icon="tabler-plus" class="size-5 shrink-0 text-gray-400 transition group-hover:text-gray-950 dark:group-hover:text-white" />
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-filament-widgets::widget>
