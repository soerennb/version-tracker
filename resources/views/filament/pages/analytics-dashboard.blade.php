<x-filament-panels::page>
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-600 via-indigo-600 to-slate-900 text-white shadow-xl">
        <div class="absolute -top-12 -right-12 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-4 h-24 w-24 rounded-full bg-emerald-400/30 blur-2xl"></div>

        <div class="relative flex flex-col gap-8 p-6 md:p-10 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-4 max-w-2xl">
                <p class="text-xs uppercase tracking-[0.3em] text-white/70">
                    {{ __('filament.navigation.analytics') }}
                </p>
                <div>
                    <h1 class="text-3xl font-semibold leading-tight md:text-4xl">
                        {{ __('filament.navigation.analytics') }} Kompass
                    </h1>
                    <p class="mt-3 text-sm text-white/80">
                        {{ __('filament.messages.approval_pending') }}: {{ number_format($pendingApprovals) }} ·
                        {{ __('filament.navigation.security') }}: {{ number_format($openVulnerabilities) }}
                    </p>
                    @if ($activeSoftware)
                        <p class="mt-2 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-white/80">
                            <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                            Fokus: {{ $activeSoftware->name }}
                        </p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white/90">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        {{ __('filament.navigation.approval') }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white/90">
                        <span class="h-2 w-2 rounded-full bg-rose-300"></span>
                        {{ __('filament.navigation.security') }}
                    </span>
                </div>
            </div>

            <dl class="grid flex-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <dt class="text-xs uppercase tracking-wide text-white/70">{{ __('filament.navigation.software') }}</dt>
                    <dd class="mt-2 text-3xl font-semibold">{{ number_format($softwareCount) }}</dd>
                    <p class="text-xs text-white/70">Tracked assets</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <dt class="text-xs uppercase tracking-wide text-white/70">{{ __('filament.navigation.approval') }}</dt>
                    <dd class="mt-2 text-3xl font-semibold">{{ number_format($pendingApprovals) }}</dd>
                    <p class="text-xs text-white/70">Awaiting review</p>
                </div>
            </dl>
        </div>
</section>

    @if ($softwareFilters->isNotEmpty())
        @php
            $softwareQueryBase = collect(request()->query())->except('software')->toArray();
            $softwareFilterUrl = function (?int $softwareId) use ($softwareQueryBase): string {
                $query = $softwareQueryBase;

                if ($softwareId) {
                    $query['software'] = $softwareId;
                }

                $queryString = http_build_query($query);

                return url()->current() . ($queryString ? '?' . $queryString : '');
            };
        @endphp

        <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Software-Fokus</span>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $softwareFilterUrl(null) }}" class="inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition {{ $activeSoftwareId === null ? 'bg-gray-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Alle Produkte
                </a>
                @foreach ($softwareFilters as $software)
                    <a href="{{ $softwareFilterUrl($software->id) }}" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition {{ $activeSoftwareId === $software->id ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                        <span class="h-2 w-2 rounded-full bg-primary-400"></span>
                        {{ $software->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $statCards = [
            [
                'label' => __('filament.navigation.software'),
                'value' => $softwareCount,
                'description' => 'Active catalog entries',
                'icon' => 'heroicon-o-rectangle-stack',
                'accent' => 'text-primary-700 border-primary-100 bg-gradient-to-br from-primary-50 to-white',
            ],
            [
                'label' => __('filament.versions.fields.status'),
                'value' => $publishedVersions,
                'description' => 'Published versions',
                'icon' => 'heroicon-o-sparkles',
                'accent' => 'text-indigo-700 border-indigo-100 bg-gradient-to-br from-indigo-50 to-white',
            ],
            [
                'label' => __('filament.navigation.approval'),
                'value' => $pendingApprovals,
                'description' => __('filament.messages.approval_pending'),
                'icon' => 'heroicon-o-check-circle',
                'accent' => 'text-amber-700 border-amber-100 bg-gradient-to-br from-amber-50 to-white',
            ],
            [
                'label' => __('filament.navigation.security'),
                'value' => $openVulnerabilities,
                'description' => 'Open findings',
                'icon' => 'heroicon-o-shield-check',
                'accent' => 'text-rose-700 border-rose-100 bg-gradient-to-br from-rose-50 to-white',
            ],
        ];
    @endphp

    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $card)
            <div class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['accent'] }}">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                    <div class="rounded-full bg-white/80 p-2 text-gray-900">
                        <x-filament::icon :icon="$card['icon']" class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-4 text-3xl font-semibold text-gray-900">{{ number_format($card['value']) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $card['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('filament.software.fields.compliance_status') }}</p>
                    <p class="text-lg font-semibold text-gray-900">Compliance posture</p>
                </div>
                <span class="text-xs text-gray-400">{{ now()->format('d.m.Y') }}</span>
            </div>

            @php
                $statuses = [
                    'compliant' => ['label' => __('filament.software.compliance.compliant'), 'color' => 'bg-emerald-500'],
                    'non_compliant' => ['label' => __('filament.software.compliance.non_compliant'), 'color' => 'bg-rose-500'],
                    'unknown' => ['label' => __('filament.software.compliance.unknown'), 'color' => 'bg-gray-400'],
                ];
                $total = max($softwareCount, 1);
            @endphp

            <div class="mt-6 space-y-4">
                <div class="h-3 rounded-full bg-gray-100">
                    <div class="flex h-full overflow-hidden rounded-full">
                        @foreach ($statuses as $key => $meta)
                            @php
                                $count = $complianceBreakdown[$key] ?? 0;
                                $percentage = round(($count / $total) * 100);
                            @endphp
                            <span class="{{ $meta['color'] }}" style="width: {{ $percentage }}%"></span>
                        @endforeach
                    </div>
                </div>

                @foreach ($statuses as $key => $meta)
                    @php
                        $count = $complianceBreakdown[$key] ?? 0;
                        $percentage = round(($count / $total) * 100);
                    @endphp
                    <div class="flex items-center justify-between rounded-xl border border-gray-100/80 px-4 py-3">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $meta['label'] }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($count) }} {{ __('filament.navigation.software') }}</p>
                        </div>
                        <span class="text-lg font-semibold text-gray-900">{{ $percentage }}%</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm xl:col-span-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Release-Zeitstrahl</p>
                    <p class="text-lg font-semibold text-gray-900">Chronologisch pro Produkt</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                    {{ $activeSoftware?->name ?? 'Alle Produkte' }}
                </span>
            </div>

            <div class="mt-6">
                @php
                    $palette = ['bg-primary-500', 'bg-amber-500', 'bg-emerald-500', 'bg-rose-500', 'bg-indigo-500', 'bg-sky-500'];
                    $softwareColorMap = [];
                @endphp

                <div class="relative">
                    <div class="absolute left-[10px] top-0 h-full w-px bg-gray-200"></div>

                    <ul class="space-y-6">
                        @forelse ($timelineVersions as $version)
                            @php
                                $softwareId = $version->software_id ?? 0;

                                if (! isset($softwareColorMap[$softwareId])) {
                                    $softwareColorMap[$softwareId] = $palette[count($softwareColorMap) % count($palette)];
                                }

                                $colorClass = $softwareColorMap[$softwareId];
                            @endphp
                            <li class="relative flex gap-4">
                                <div class="relative flex flex-col items-center">
                                    <span class="relative flex h-3 w-3">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-40 {{ $colorClass }}"></span>
                                        <span class="relative inline-flex h-3 w-3 rounded-full border-2 border-white {{ $colorClass }}"></span>
                                    </span>
                                </div>
                                <div class="flex-1 rounded-2xl border border-gray-100 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ $version->software->name ?? '—' }} · v{{ $version->version_number }}
                                        </p>
                                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                            {{ $version->status?->label() ?? __('filament.versions.fields.status') }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs uppercase tracking-wide text-gray-500">
                                        {{ optional($version->release_date)->format('d.m.Y') ?? __('filament.versions.fields.release_date') }}
                                    </p>
                                    @if ($version->support_status)
                                        <p class="mt-1 text-xs text-gray-500">{{ $version->support_status }}</p>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="rounded-2xl border border-dashed border-gray-200 p-6 text-sm text-gray-500">
                                {{ __('filament.messages.approval_empty') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
