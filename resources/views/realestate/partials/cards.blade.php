<div class="grid gap-x-6 gap-y-9 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($properties as $property)
                        @php
                            $media = $property->getFirstMedia('photos') ?: $property->getFirstMedia('cover');
                            $conversion = $media?->collection_name === 'cover' ? 'cover' : 'photo_thumb';
                            $image = $media ? ($media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl()) : null;
                            $placeName = $property->city?->name ?? $property->region?->name ?? $property->country?->name;
                        @endphp
                        <article data-catalog-card class="group min-w-0">
                            <a href="{{ route('realestate.show', ['url' => $property->slug]) }}" class="block">
                                <div class="relative aspect-[1.3] overflow-hidden rounded-[18px] bg-[#dce7dd]">
                                    @if($image)
                                        <img src="{{ $image }}" alt="{{ $property->title }}" loading="lazy" decoding="async" width="680" height="520" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.045]">
                                    @else
                                        <div class="flex h-full flex-col items-center justify-center gap-3 bg-[linear-gradient(145deg,#c1d0c3,#e8ede3)] text-[#7c9b85]">
                                            <x-tabler-building-estate class="size-12" stroke-width="1" />
                                            <span class="text-xs">Фото объекта готовится</span>
                                        </div>
                                    @endif
                                    <div class="absolute left-3.5 top-3.5 flex flex-wrap gap-1.5">
                                        @if($property->newbuild)
                                            <span class="rounded-full bg-[#d9c998] px-3 py-1.5 text-[10px] font-medium text-[#193b32]">Новостройка</span>
                                        @endif
                                        @if($property->operation === 'rent')
                                            <span class="rounded-full bg-white/95 px-3 py-1.5 text-[10px] font-medium text-[#193b32]">Аренда</span>
                                        @endif
                                    </div>
                                    <span class="absolute bottom-3.5 right-3.5 flex size-10 items-center justify-center rounded-full bg-white text-[#214438] opacity-0 transition-all group-hover:opacity-100 group-focus-within:opacity-100">
                                        <x-tabler-arrow-up-right class="size-5" stroke-width="1.5" />
                                    </span>
                                </div>
                                <div class="px-1 pt-4">
                                    <div class="mb-2.5 flex items-center justify-between gap-3 text-xs text-[#839489]">
                                        <span class="truncate">{{ $placeName ?: 'Латинская Америка' }}</span>
                                        <span class="shrink-0">{{ $types[$property->property_type ?? ''] ?? 'Недвижимость' }}</span>
                                    </div>
                                    <h3 class="line-clamp-2 min-h-[48px] text-lg font-normal leading-[1.35] tracking-[-0.02em] text-[#173b35] transition-colors group-hover:text-[#47745a]">{{ $property->title }}</h3>
                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-[#829487]">
                                        @if($property->bedrooms !== null)<span>{{ $property->bedrooms }} сп.</span>@endif
                                        @if($property->bathrooms !== null)<span>{{ $property->bathrooms }} ван.</span>@endif
                                        @if($property->covered_area !== null)<span>{{ number_format((float) $property->covered_area, 0, ',', ' ') }} м²</span>@endif
                                        @if($property->land_area !== null)<span>Участок {{ number_format((float) $property->land_area, 0, ',', ' ') }} м²</span>@endif
                                    </div>
                                    <p class="mt-4 text-xl font-medium tracking-tight text-[#173b35]">
                                        @if($property->price !== null)
                                            {{ number_format((float) $property->price, 0, ',', ' ') }} <span class="text-sm font-normal text-[#819489]">{{ $property->currency }}</span>
                                        @else
                                            <span class="text-base">Цена по запросу</span>
                                        @endif
                                    </p>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
