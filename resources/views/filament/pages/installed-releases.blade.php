<x-filament-panels::page>
    <div class="space-y-5">
        <label class="block max-w-lg">
            <span class="text-sm font-medium text-gray-700">{{ __('filament.composition.environment') }}</span>
            <select wire:model.live="selectedEnvironmentId" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-950 focus:ring-gray-950">
                @foreach ($environments as $environment)
                    <option value="{{ $environment->id }}">{{ $environment->customer?->name ? $environment->customer->name.' · ' : '' }}{{ $environment->name }}</option>
                @endforeach
            </select>
        </label>

        @if ($installations->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white px-6 py-10 text-sm text-gray-500">{{ __('filament.composition.no_installations') }}</div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($installations as $deployment)
                    @php
                        $composition = $deployment->version?->composition;
                        $ted = $composition?->activeEformsSdkVersion?->tedAcceptance();
                    @endphp
                    <section wire:key="installed-{{ $deployment->id }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-4">
                            <div>
                                <h2 class="font-semibold text-gray-950">{{ $deployment->software?->name }}</h2>
                                <p class="mt-1 font-mono text-xl text-gray-900">{{ $deployment->version?->version_number }}</p>
                            </div>
                            <span class="text-xs text-gray-500">{{ $deployment->completed_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-gray-500">{{ __('filament.composition.baseline') }}</dt><dd class="font-mono font-medium">{{ $composition?->baselineVersion?->version_label ?? '–' }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('filament.composition.eforms_component') }}</dt><dd class="font-mono font-medium">{{ $composition?->eformsComponentVersion?->version_label ?? '–' }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('filament.composition.eforms_sdk') }}</dt><dd class="font-mono font-medium">{{ $composition?->activeEformsSdkVersion?->version_label ?? '–' }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('filament.composition.customization') }}</dt><dd class="font-mono font-medium">{{ $deployment->customizationVersion?->version_label ?? '–' }}</dd></div>
                        </dl>
                        @if ($composition)
                            <div class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-600">
                                {{ __('filament.composition.supported') }}:
                                {{ $composition->supportedInterfaces->map(fn ($item) => $item->componentVersion?->component?->name.' '.$item->componentVersion?->version_label)->implode(' · ') }}
                            </div>
                            <p class="mt-2 text-xs {{ $ted['status'] === 'not_accepted' ? 'font-semibold text-red-700' : 'text-gray-500' }}">{{ __('filament.composition.ted_'.$ted['status']) }} @if ($ted['checked_at']) · {{ \Illuminate\Support\Carbon::parse($ted['checked_at'])->format('d.m.Y') }} @endif</p>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
