@php
    /** @var \App\Models\Realestate\Realestate $record */
    $record = $getRecord();
    $propertyType = \App\Filament\Resources\Realestate\Schemas\RealestateForm::propertyTypes()[$record->property_type ?? ''] ?? $record->property_type;
    $operation = \App\Filament\Resources\Realestate\Schemas\RealestateForm::operationTypes()[$record->operation ?? ''] ?? $record->operation;
    $location = collect([$record->district?->name, $record->city?->name, $record->region?->name])->filter()->unique()->join(', ');
@endphp

<div class="min-w-0 space-y-2 py-1">
    <a href="{{ route('realestate.show', ['url' => $record->slug]) }}" target="_blank" rel="noopener noreferrer"
       class="block text-sm font-semibold text-gray-950 hover:underline dark:text-white">
        {{ $record->title }}
    </a>

    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
        <span class="font-mono">#{{ $record->id }}</span>

        @if($propertyType)
            <span>{{ $propertyType }}</span>
        @endif

        @if($operation)
            <span>{{ $operation }}</span>
        @endif

        @if($record->price !== null)
            <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ number_format((float) $record->price, 0, ',', ' ') }} {{ $record->currency }}
            </span>
        @endif

        @if($location)
            <span>{{ $location }}</span>
        @endif

        @if($record->covered_area !== null)
            <span>Крытая: {{ number_format((float) $record->covered_area, 0, ',', ' ') }} м²</span>
        @endif

        @if($record->land_area !== null)
            <span>Участок: {{ number_format((float) $record->land_area, 0, ',', ' ') }} м²</span>
        @endif

        @if($record->bedrooms !== null)
            <span>Спален: {{ $record->bedrooms }}</span>
        @endif

        @if($record->newbuild)
            <span class="font-medium text-primary-600 dark:text-primary-400">Новостройка</span>
        @endif

        @if($record->sold)
            <span class="font-medium text-danger-600 dark:text-danger-400">Продано</span>
        @endif
    </div>

    @if($record->address)
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $record->address }}</p>
    @endif
</div>
