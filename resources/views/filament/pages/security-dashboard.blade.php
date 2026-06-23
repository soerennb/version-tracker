<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-600">{{ __('vulnerabilities.dashboard.open_critical_high') }}</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($openCriticalOrHigh) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-600">{{ __('vulnerabilities.dashboard.fix_available') }}</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($fixAvailable) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-600">{{ __('vulnerabilities.dashboard.eol_risk') }}</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($eolRiskCount) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-600">{{ __('vulnerabilities.dashboard.affected_software') }}</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950">{{ number_format($affectedSoftwareCount) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[280px_1fr]">
        <section class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-950">{{ __('vulnerabilities.dashboard.open_by_severity') }}</h2>
            </div>
            <dl class="divide-y divide-gray-100">
                @foreach (\App\Enums\VulnerabilitySeverity::cases() as $severity)
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-sm text-gray-700">{{ $severity->label() }}</dt>
                        <dd class="text-sm font-semibold text-gray-950">{{ number_format($severityBreakdown[$severity->value] ?? 0) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-950">{{ __('vulnerabilities.dashboard.priority_findings') }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.cve') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.software') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.severity') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.cvss') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.fix') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.exploitability') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($priorityFindings as $finding)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-950">{{ $finding->cve_id }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $finding->affectedVersion?->software?->name ?? 'n/a' }}
                                    <span class="text-gray-500">{{ $finding->affectedVersion?->version_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $finding->severity?->label() }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $finding->cvss_score ?? 'n/a' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $finding->fixedVersion?->version_number ?? __('vulnerabilities.dashboard.no_fix') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $finding->exploitability?->label() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                    {{ __('vulnerabilities.dashboard.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
