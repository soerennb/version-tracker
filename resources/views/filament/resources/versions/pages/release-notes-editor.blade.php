<x-filament-panels::page>
    <div x-data="{ language: 'de' }" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <div>
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $record->software?->name }} · {{ $record->version_number }}</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ __('filament.release_notes.editor_description') }}</p>
                </div>
                <span class="text-xs font-semibold {{ $this->isComplete() ? 'text-success-700' : 'text-warning-700' }}">
                    {{ $this->isComplete() ? __('filament.release_notes.complete') : __('filament.release_notes.incomplete') }}
                </span>
            </header>

            <div class="border-b border-gray-200 px-4 pt-3 dark:border-white/10" role="tablist" aria-label="{{ __('filament.release_notes.languages') }}">
                @foreach (\App\Enums\Language::cases() as $language)
                    <button type="button" role="tab" @click="language = '{{ $language->value }}'" :aria-selected="language === '{{ $language->value }}'" :class="language === '{{ $language->value }}' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500'" class="mr-4 border-b-2 px-1 pb-3 text-sm font-semibold">
                        {{ $language->nativeLabel() }}
                    </button>
                @endforeach
            </div>

            @foreach (\App\Enums\Language::cases() as $language)
                <div x-show="language === '{{ $language->value }}'" x-cloak class="grid gap-0 lg:grid-cols-2">
                    <div class="border-b border-gray-200 p-4 lg:border-r lg:border-b-0 dark:border-white/10">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('filament.release_notes.headline') }}</label>
                        <input type="text" wire:model.live.debounce.400ms="notes.{{ $language->value }}.title" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-950">
                        @error("notes.{$language->value}.title") <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror

                        <label class="mt-4 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('filament.release_notes.content') }}</label>
                        <textarea wire:model.live.debounce.400ms="notes.{{ $language->value }}.content" rows="18" class="mt-1 block w-full resize-y rounded-md border-gray-300 font-mono text-sm leading-6 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-950"></textarea>
                        @error("notes.{$language->value}.content") <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="min-w-0 bg-gray-50 p-4 dark:bg-white/5">
                        <p class="text-xs font-semibold uppercase text-gray-500">{{ __('filament.release_notes.preview') }}</p>
                        <article class="prose prose-sm mt-4 max-w-none dark:prose-invert">
                            <h2>{{ $notes[$language->value]['title'] ?: __('filament.release_notes.untitled') }}</h2>
                            {!! \Illuminate\Support\Str::markdown($notes[$language->value]['content'] ?: __('filament.release_notes.empty_preview'), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </article>
                    </div>
                </div>
            @endforeach

            <footer class="flex justify-end border-t border-gray-200 px-4 py-3 dark:border-white/10">
                <x-filament::button wire:click="save" wire:loading.attr="disabled" icon="heroicon-o-check">
                    {{ __('filament.release_notes.save') }}
                </x-filament::button>
            </footer>
        </section>

        <aside class="self-start overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <header class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('filament.release_notes.changes') }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ __('filament.release_notes.changes_description') }}</p>
            </header>
            <ol class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($this->recentChanges() as $change)
                    <li class="px-4 py-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $change->user?->name ?? __('filament.release_notes.system') }}</span>
                            <time class="text-gray-500">{{ $change->created_at?->diffForHumans() }}</time>
                        </div>
                        <dl class="mt-2 space-y-2">
                            @foreach ($change->getChangedFields() as $field => $values)
                                @continue(! in_array($field, ['title', 'content'], true))
                                <div class="text-xs">
                                    <dt class="font-semibold text-gray-700 dark:text-gray-300">{{ __('filament.release_notes.'.$field) }}</dt>
                                    <dd class="mt-0.5 line-clamp-2 text-red-700">− {{ \Illuminate\Support\Str::limit((string) $values['old'], 100) }}</dd>
                                    <dd class="line-clamp-2 text-emerald-700">+ {{ \Illuminate\Support\Str::limit((string) $values['new'], 100) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-gray-500">{{ __('filament.release_notes.no_changes') }}</li>
                @endforelse
            </ol>
        </aside>
    </div>
</x-filament-panels::page>
