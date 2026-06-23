<x-filament-panels::page>
    @php
        $formatValue = static function (mixed $value): string {
            if ($value === null) return '∅';
            if (is_bool($value)) return $value ? 'true' : 'false';
            if (is_array($value)) return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return (string) $value;
        };
    @endphp

    <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="self-start overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <dl class="divide-y divide-gray-100 text-sm dark:divide-white/5">
                <div class="px-4 py-3"><dt class="text-xs text-gray-500">{{ __('filament.audit.event') }}</dt><dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->action_label }}</dd></div>
                <div class="px-4 py-3"><dt class="text-xs text-gray-500">{{ __('filament.audit.object') }}</dt><dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->subject_label }}</dd><dd class="mt-0.5 text-xs text-gray-500">{{ $record->model_label }} #{{ $record->model_id }}</dd></div>
                <div class="px-4 py-3"><dt class="text-xs text-gray-500">{{ __('filament.audit.actor') }}</dt><dd class="mt-1 text-gray-800 dark:text-gray-200">{{ $record->user?->name ?? __('filament.audit.system') }}</dd></div>
                <div class="px-4 py-3"><dt class="text-xs text-gray-500">{{ __('filament.audit.timestamp') }}</dt><dd class="mt-1 text-gray-800 dark:text-gray-200">{{ $record->created_at?->format('d.m.Y H:i:s') }}</dd></div>
                <div class="px-4 py-3"><dt class="text-xs text-gray-500">{{ __('filament.audit.ip_address') }}</dt><dd class="mt-1 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $record->ip_address ?? '—' }}</dd></div>
            </dl>
        </aside>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <header class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('filament.audit.field_diff') }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ trans_choice('filament.audit.change_count', count($record->getChangedFields()), ['count' => count($record->getChangedFields())]) }}</p>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5"><tr><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">{{ __('filament.audit.field') }}</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">{{ __('filament.audit.before') }}</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">{{ __('filament.audit.after') }}</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($record->getChangedFields() as $field => $values)
                            <tr class="align-top"><th class="whitespace-nowrap px-4 py-3 text-left font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">{{ str($field)->replace('_', ' ')->headline() }}</th><td class="max-w-md px-4 py-3"><pre class="whitespace-pre-wrap break-words font-mono text-xs text-red-700">{{ $formatValue($values['old']) }}</pre></td><td class="max-w-md px-4 py-3"><pre class="whitespace-pre-wrap break-words font-mono text-xs text-emerald-700">{{ $formatValue($values['new']) }}</pre></td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('filament.audit.no_changes') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
