<x-filament-panels::page>
    @php
        $definitions = [
            'due_reviews' => ['label' => __('filament.work_queue.due_reviews'), 'description' => __('filament.work_queue.due_reviews_description'), 'tone' => 'text-amber-700'],
            'pending_approvals' => ['label' => __('filament.work_queue.pending_approvals'), 'description' => __('filament.work_queue.pending_approvals_description'), 'tone' => 'text-blue-700'],
            'security_blockers' => ['label' => __('filament.work_queue.security_blockers'), 'description' => __('filament.work_queue.security_blockers_description'), 'tone' => 'text-red-700'],
            'eol_soon' => ['label' => __('filament.work_queue.eol_soon'), 'description' => __('filament.work_queue.eol_soon_description'), 'tone' => 'text-orange-700'],
            'incomplete_notes' => ['label' => __('filament.work_queue.incomplete_notes'), 'description' => __('filament.work_queue.incomplete_notes_description'), 'tone' => 'text-violet-700'],
            'broken_dependencies' => ['label' => __('filament.work_queue.broken_dependencies'), 'description' => __('filament.work_queue.broken_dependencies_description'), 'tone' => 'text-red-700'],
        ];
    @endphp

    <div class="border-y border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <dl class="grid grid-cols-2 divide-x divide-y divide-gray-200 md:grid-cols-3 xl:grid-cols-6 xl:divide-y-0 dark:divide-white/10">
            @foreach ($definitions as $key => $definition)
                <div class="min-w-0 px-4 py-3">
                    <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $definition['label'] }}</dt>
                    <dd class="mt-1 text-2xl font-semibold {{ $definition['tone'] }}">{{ number_format($queues[$key]->count()) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach ($definitions as $key => $definition)
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <header class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $definition['label'] }}</h2>
                        <span class="text-xs font-semibold {{ $definition['tone'] }}">{{ $queues[$key]->count() }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $definition['description'] }}</p>
                </header>

                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($queues[$key] as $item)
                        @php
                            if ($key === 'security_blockers') {
                                $title = $item->cve_id;
                                $context = ($item->affectedVersion?->software?->name ?? '—').' · '.($item->affectedVersion?->version_number ?? '—');
                                $detail = strtoupper($item->severity?->value ?? '').' · CVSS '.($item->cvss_score ?? '—');
                                $url = \App\Filament\Resources\Vulnerabilities\VulnerabilityResource::getUrl('edit', ['record' => $item]);
                            } elseif ($key === 'broken_dependencies') {
                                $title = ($item->software?->name ?? '—').' → '.($item->dependsOnSoftware?->name ?? '—');
                                $context = $item->dependency_type;
                                $detail = __('dependencies.health.broken');
                                $url = \App\Filament\Resources\SoftwareDependencies\SoftwareDependencyResource::getUrl('edit', ['record' => $item]);
                            } else {
                                $title = ($item->software?->name ?? '—').' · '.$item->version_number;
                                $context = match ($key) {
                                    'eol_soon' => __('filament.work_queue.eol_on', ['date' => $item->eol_date?->format('d.m.Y')]),
                                    'incomplete_notes' => __('filament.work_queue.languages_present', ['count' => $item->textContents->pluck('language')->unique()->count()]),
                                    default => __('filament.work_queue.release_on', ['date' => $item->release_date?->format('d.m.Y') ?? '—']),
                                };
                                $detail = $item->approval_status?->label() ?? $item->support_status?->label();
                                $url = \App\Filament\Resources\Versions\VersionResource::getUrl('edit', ['record' => $item]);
                            }
                        @endphp
                        <li>
                            <a href="{{ $url }}" class="group grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-4 py-3 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 dark:hover:bg-white/5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-950 group-hover:text-primary-700 dark:text-white">{{ $title }}</p>
                                    <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $context }}</p>
                                </div>
                                <span class="max-w-36 truncate text-right text-xs font-medium text-gray-500 dark:text-gray-400">{{ $detail }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-7 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('filament.work_queue.empty') }}</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
