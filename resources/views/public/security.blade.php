<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('public_security.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-950 antialiased">
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-6 border-b border-gray-200 pb-5">
            <h1 class="text-2xl font-semibold">{{ __('public_security.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-gray-600">{{ __('public_security.description') }}</p>
        </header>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.cve') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.software') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.severity') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('vulnerabilities.fields.fix') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('public_security.published') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">{{ __('public_security.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($advisories as $advisory)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-950">{{ $advisory->cve_id }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $advisory->affectedVersion?->software?->name ?? 'n/a' }}
                                    <span class="text-gray-500">{{ $advisory->affectedVersion?->version_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $advisory->severity?->label() }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $advisory->fixedVersion?->version_number ?? __('vulnerabilities.dashboard.no_fix') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $advisory->published_date?->format('Y-m-d') ?? 'n/a' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $advisory->status?->label() }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-4 pb-4 text-sm text-gray-600">
                                    {{ $advisory->description }}
                                    @if ($advisory->source_url)
                                        <a class="ml-2 font-medium text-gray-950 underline" href="{{ $advisory->source_url }}" rel="noopener noreferrer nofollow" target="_blank">
                                            {{ __('public_security.source') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">{{ __('public_security.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-5">
            {{ $advisories->links() }}
        </div>
    </main>
</body>
</html>
