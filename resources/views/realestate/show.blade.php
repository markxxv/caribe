@section('metaTitle', $realestate->meta_title ?: $realestate->title)
@section('metaDescription', $realestate->meta_description ?: '')

@if($realestate->active)
    @section('canonicalUrl', route('realestate.show', ['url' => $realestate->slug]))
@endif

<x-layout>
    @php
        $type = \App\Filament\Resources\Realestate\Schemas\RealestateForm::propertyTypes()[$realestate->property_type ?? ''] ?? $realestate->property_type;
        $operation = \App\Filament\Resources\Realestate\Schemas\RealestateForm::operationTypes()[$realestate->operation ?? ''] ?? $realestate->operation;
        $location = collect([
            $realestate->district?->name,
            $realestate->city?->name,
            $realestate->region?->name,
            $realestate->country?->name,
        ])->filter()->unique()->join(', ');
        $description = strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>'], "\n", $realestate->description ?? ''));
    @endphp

    <main class="min-h-screen bg-white pt-32 pb-20 text-zinc-900 md:pt-40">
        <div class="container mx-auto max-w-5xl px-5 md:px-8">
            <p class="mb-5 text-sm text-zinc-500">
                <a href="/" class="hover:text-zinc-900">Главная</a>
                <span class="mx-2">/</span>
                Недвижимость
            </p>

            @unless($realestate->active)
                <div class="mb-6 rounded-xl bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    Предпросмотр — объект пока не опубликован.
                </div>
            @endunless

            <div class="mb-10">
                <div class="mb-3 flex flex-wrap gap-x-4 gap-y-2 text-sm text-zinc-500">
                    @if($type)<span>{{ $type }}</span>@endif
                    @if($operation)<span>{{ $operation }}</span>@endif
                    @if($location)<span>{{ $location }}</span>@endif
                    @if($realestate->newbuild)<span>Новостройка</span>@endif
                    @if($realestate->sold)<span>Продано</span>@endif
                </div>

                <h1 class="max-w-4xl text-3xl font-semibold tracking-tight md:text-4xl">{{ $realestate->title }}</h1>

                @if($realestate->address)
                    <p class="mt-3 text-base text-zinc-500">{{ $realestate->address }}</p>
                @endif

                @if($realestate->price !== null)
                    <div class="mt-6 text-2xl font-semibold">
                        {{ number_format((float) $realestate->price, 0, ',', ' ') }} {{ $realestate->currency }}
                    </div>
                @endif
            </div>

            <section class="mb-12">
                <h2 class="mb-5 text-xl font-semibold">Характеристики</h2>
                <dl class="grid grid-cols-2 gap-x-8 gap-y-5 text-sm sm:grid-cols-3">
                    @foreach([
                        'Комнаты' => $realestate->rooms,
                        'Спальни' => $realestate->bedrooms,
                        'Ванные' => $realestate->bathrooms,
                        'Машиноместа' => $realestate->parking_spaces,
                        'Крытая площадь' => $realestate->covered_area !== null ? number_format((float) $realestate->covered_area, 0, ',', ' ') . ' м²' : null,
                        'Полукрытая площадь' => $realestate->semi_covered_area !== null ? number_format((float) $realestate->semi_covered_area, 0, ',', ' ') . ' м²' : null,
                        'Участок' => $realestate->land_area !== null ? number_format((float) $realestate->land_area, 0, ',', ' ') . ' м²' : null,
                        'Год постройки' => $realestate->construction_year,
                    ] as $label => $value)
                        @if($value !== null)
                            <div>
                                <dt class="text-zinc-500">{{ $label }}</dt>
                                <dd class="mt-1 font-medium">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>

            @if(trim($description) !== '')
                <section class="max-w-3xl">
                    <h2 class="mb-5 text-xl font-semibold">Описание</h2>
                    <div class="whitespace-pre-line text-base text-zinc-700">{{ trim($description) }}</div>
                </section>
            @endif
        </div>
    </main>
</x-layout>
