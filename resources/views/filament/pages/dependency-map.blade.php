<x-filament-panels::page>
    @php
        $nodesById = collect($map['nodes'])->keyBy('id');
    @endphp

    <div class="space-y-4">
        <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 md:flex-row md:items-end md:justify-between">
            <label class="w-full max-w-sm">
                <span class="block text-sm font-medium text-gray-700">{{ __('filament.dependency_map.software') }}</span>
                <select
                    wire:model.live="selectedSoftwareId"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-950 focus:ring-gray-950"
                >
                    @foreach ($softwareOptions as $softwareId => $softwareName)
                        <option value="{{ $softwareId }}">{{ $softwareName }}</option>
                    @endforeach
                </select>
            </label>

            <dl class="grid grid-cols-4 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('filament.dependency_map.nodes') }}</dt>
                    <dd class="font-semibold text-gray-950">{{ $map['stats']['nodes'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('filament.dependency_map.edges') }}</dt>
                    <dd class="font-semibold text-gray-950">{{ $map['stats']['edges'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('filament.dependency_map.vulnerable') }}</dt>
                    <dd class="font-semibold text-red-700">{{ $map['stats']['vulnerable'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('filament.dependency_map.eol') }}</dt>
                    <dd class="font-semibold text-amber-700">{{ $map['stats']['eol'] }}</dd>
                </div>
            </dl>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                @if ($map['nodes'] === [])
                    <p class="px-4 py-10 text-center text-sm text-gray-500">{{ __('filament.dependency_map.empty') }}</p>
                @else
                    <svg viewBox="0 0 720 520" role="img" aria-label="{{ __('filament.navigation.dependency_map') }}" class="h-[520px] w-full bg-white">
                        <defs>
                            <marker id="dependency-map-arrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto" markerUnits="strokeWidth">
                                <path d="M0,0 L0,6 L9,3 z" fill="#6b7280" />
                            </marker>
                        </defs>

                        @foreach ($map['edges'] as $edge)
                            @php
                                $from = $nodesById[$edge['from']] ?? null;
                                $to = $nodesById[$edge['to']] ?? null;
                            @endphp

                            @if ($from && $to)
                                <line
                                    x1="{{ $from['x'] }}"
                                    y1="{{ $from['y'] }}"
                                    x2="{{ $to['x'] }}"
                                    y2="{{ $to['y'] }}"
                                    stroke="{{ $edge['direction'] === 'outgoing' ? '#4b5563' : '#9ca3af' }}"
                                    stroke-width="{{ $edge['direction'] === 'outgoing' ? 2.5 : 1.5 }}"
                                    marker-end="url(#dependency-map-arrow)"
                                />
                                <text
                                    x="{{ (int) (($from['x'] + $to['x']) / 2) }}"
                                    y="{{ (int) (($from['y'] + $to['y']) / 2) - 8 }}"
                                    text-anchor="middle"
                                    class="fill-gray-500 text-[11px]"
                                >{{ $edge['type'] }}</text>
                            @endif
                        @endforeach

                        @foreach ($map['nodes'] as $node)
                            <g wire:click="$set('selectedSoftwareId', {{ $node['id'] }})" class="cursor-pointer">
                                <circle
                                    cx="{{ $node['x'] }}"
                                    cy="{{ $node['y'] }}"
                                    r="{{ $node['selected'] ? 42 : 34 }}"
                                    fill="{{ $node['selected'] ? '#111827' : ($node['status'] === 'active' ? '#f9fafb' : '#f3f4f6') }}"
                                    stroke="{{ $node['has_vulnerability'] ? '#b91c1c' : ($node['has_eol_risk'] ? '#b45309' : '#9ca3af') }}"
                                    stroke-width="{{ $node['has_vulnerability'] || $node['has_eol_risk'] ? 4 : 1.5 }}"
                                />
                                <text
                                    x="{{ $node['x'] }}"
                                    y="{{ $node['y'] - 2 }}"
                                    text-anchor="middle"
                                    class="{{ $node['selected'] ? 'fill-white' : 'fill-gray-950' }} text-[12px] font-semibold"
                                >{{ \Illuminate\Support\Str::limit($node['name'], 18) }}</text>
                                <text
                                    x="{{ $node['x'] }}"
                                    y="{{ $node['y'] + 15 }}"
                                    text-anchor="middle"
                                    class="{{ $node['selected'] ? 'fill-gray-200' : 'fill-gray-500' }} text-[10px]"
                                >{{ $node['status_label'] }}</text>
                            </g>
                        @endforeach
                    </svg>
                @endif
            </section>

            <aside class="space-y-4">
                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-950">{{ __('filament.dependency_map.legend') }}</h2>
                    </div>
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="grid grid-cols-[24px_1fr] gap-3 px-4 py-3">
                            <dt><span class="block h-4 w-4 rounded-full border-4 border-red-700"></span></dt>
                            <dd class="text-gray-700">{{ __('filament.dependency_map.legend_vulnerability') }}</dd>
                        </div>
                        <div class="grid grid-cols-[24px_1fr] gap-3 px-4 py-3">
                            <dt><span class="block h-4 w-4 rounded-full border-4 border-amber-700"></span></dt>
                            <dd class="text-gray-700">{{ __('filament.dependency_map.legend_eol') }}</dd>
                        </div>
                        <div class="grid grid-cols-[24px_1fr] gap-3 px-4 py-3">
                            <dt><span class="block h-4 w-4 rounded-full bg-gray-950"></span></dt>
                            <dd class="text-gray-700">{{ __('filament.dependency_map.legend_selected') }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-950">{{ __('filament.dependency_map.dependencies') }}</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($map['edges'] as $edge)
                            @php
                                $from = $nodesById[$edge['from']] ?? null;
                                $to = $nodesById[$edge['to']] ?? null;
                            @endphp

                            <div class="px-4 py-3 text-sm">
                                <p class="font-medium text-gray-950">{{ $from['name'] ?? '?' }} -> {{ $to['name'] ?? '?' }}</p>
                                <p class="text-gray-600">
                                    {{ $edge['direction'] === 'outgoing' ? __('filament.dependency_map.outgoing') : __('filament.dependency_map.incoming') }}
                                    · {{ $edge['type'] }}
                                    · {{ $edge['min_version'] ?? '*' }} - {{ $edge['max_version'] ?? '*' }}
                                </p>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.dependency_map.empty') }}</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
