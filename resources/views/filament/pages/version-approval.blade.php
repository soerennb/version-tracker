<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section>
            {{ $this->table }}
        </section>

        <aside class="space-y-4">
            @if ($cockpit)
                @php
                    /** @var \App\Models\Version $version */
                    $version = $cockpit['version'];
                    $readiness = $cockpit['readiness'];
                @endphp

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <p class="text-xs font-medium uppercase text-gray-500">{{ __('filament.approval_cockpit.selected_release') }}</p>
                        <h2 class="mt-1 text-base font-semibold text-gray-950">
                            {{ $version->software?->name }} {{ $version->version_number }}
                        </h2>
                    </div>

                    <div class="grid grid-cols-2 gap-3 p-4 text-sm">
                        <div>
                            <p class="text-gray-500">{{ __('filament.versions.fields.release_date') }}</p>
                            <p class="font-medium text-gray-950">{{ $version->release_date?->format('d.m.Y') ?? 'n/a' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">{{ __('versions.readiness.label') }}</p>
                            <p class="font-medium text-gray-950">{{ $readiness['score'] }}% · {{ $readiness['passed'] }}/{{ $readiness['total'] }}</p>
                        </div>
                    </div>

                    @if (! $readiness['is_ready'])
                        <div class="border-t border-gray-100 px-4 py-3">
                            <ul class="space-y-1 text-sm text-amber-700">
                                @foreach ($readiness['blockers'] as $blocker)
                                    <li>{{ $blocker['label'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.release_notes') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['release_notes'] as $textContent)
                            <article class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-950">{{ $textContent->title }}</p>
                                <p class="mt-1 line-clamp-3 text-sm text-gray-600">{{ $textContent->content }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $textContent->language?->label() }}</p>
                            </article>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.attachments') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['attachments'] as $attachment)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                <span class="font-medium text-gray-950">{{ $attachment->filename }}</span>
                                <span class="text-gray-500">{{ number_format($attachment->size / 1024, 1) }} KB</span>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.security') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['vulnerabilities'] as $vulnerability)
                            <div class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-medium text-gray-950">{{ $vulnerability->cve_id }}</span>
                                    <span class="text-gray-600">{{ $vulnerability->severity?->label() }} · {{ $vulnerability->cvss_score ?? 'n/a' }}</span>
                                </div>
                                <p class="mt-1 text-gray-600">
                                    {{ $vulnerability->status?->label() }} ·
                                    {{ $vulnerability->fixedVersion?->version_number ?? __('vulnerabilities.dashboard.no_fix') }}
                                </p>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.dependencies') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['dependencies'] as $dependency)
                            <div class="px-4 py-3 text-sm">
                                <p class="font-medium text-gray-950">{{ $dependency->dependsOnSoftware?->name }}</p>
                                <p class="text-gray-600">
                                    {{ $dependency->dependency_type }} ·
                                    {{ $dependency->minVersion?->version_number ?? '*' }} - {{ $dependency->maxVersion?->version_number ?? '*' }}
                                </p>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.impact') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['impact'] as $impact)
                            <div class="px-4 py-3 text-sm">
                                <p class="font-medium text-gray-950">{{ $impact['software']['name'] }}</p>
                                <p class="text-gray-600">{{ __('filament.approval_cockpit.depth') }} {{ $impact['depth'] }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.review_history') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['reviews'] as $review)
                            <div class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-medium text-gray-950">{{ $review->action?->label() }}</span>
                                    <span class="text-gray-500">{{ $review->created_at?->format('d.m.Y H:i') }}</span>
                                </div>
                                <p class="mt-1 text-gray-600">{{ $review->user?->name ?? 'system' }}</p>
                                @if ($review->reject_reason)
                                    <p class="mt-1 text-gray-700">{{ $review->reject_reason->label() }}</p>
                                @endif
                                @if ($review->comment)
                                    <p class="mt-1 text-gray-600">{{ $review->comment }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-950">{{ __('filament.approval_cockpit.audit_diff') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($cockpit['audit_changes'] as $auditChange)
                            <div class="px-4 py-3 text-sm">
                                <p class="font-medium text-gray-950">
                                    {{ $auditChange['action'] }} · {{ $auditChange['user'] ?? 'system' }}
                                </p>
                                <p class="text-gray-500">{{ $auditChange['created_at']?->format('d.m.Y H:i') }}</p>
                                <dl class="mt-2 space-y-1">
                                    @foreach ($auditChange['changes'] as $field => $change)
                                        <div class="grid grid-cols-[90px_1fr] gap-2">
                                            <dt class="text-gray-500">{{ $field }}</dt>
                                            <dd class="text-gray-700">{{ json_encode($change['old']) }} -> {{ json_encode($change['new']) }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">{{ __('filament.approval_cockpit.empty') }}</p>
                        @endforelse
                    </div>
                </section>
            @else
                <section class="rounded-lg border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                    {{ __('filament.messages.approval_empty') }}
                </section>
            @endif
        </aside>
    </div>
</x-filament-panels::page>
