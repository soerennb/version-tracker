<?php

namespace App\Filament\Pages;

use App\Services\ApiTokenService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class ManageApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 69;

    public bool $showAll = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessPanel(filament()->getCurrentPanel()) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('api_tokens.title');
    }

    public function getTitle(): string
    {
        return __('api_tokens.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Section::make(__('api_tokens.connection'))->description(__('api_tokens.connection_description'))->schema([TextEntry::make('endpoint')->label('MCP')->state(fn (): string => route('mcp.versiontracker'))->copyable()]), EmbeddedTable::make()]);
    }

    protected function tokenQuery(): Builder
    {
        $user = auth()->user();
        $query = PersonalAccessToken::query()->with('tokenable')->where('tokenable_type', $user->getMorphClass());
        if (! $this->showAll || ! $user->isAdmin()) {
            $query->where('tokenable_id', $user->id);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table->modelLabel(__('api_tokens.token'))->pluralModelLabel(__('api_tokens.tokens'))->query(fn (): Builder => $this->tokenQuery())->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('name')->label(__('api_tokens.name'))->searchable(),
            TextColumn::make('tokenable.name')->label(__('api_tokens.owner'))->visible(fn (): bool => auth()->user()->isAdmin() && $this->showAll),
            TextColumn::make('access')->label(__('api_tokens.access'))->state(fn (PersonalAccessToken $record): array => array_values(array_map(fn (string $ability): string => strtoupper(substr($ability, 7)), array_filter($record->abilities ?? [], fn (string $ability): bool => str_starts_with($ability, 'access_')))))->badge(),
            TextColumn::make('abilities')->label(__('api_tokens.abilities'))->formatStateUsing(fn (string $state): string => $this->abilityLabel($state))->listWithLineBreaks()->limitList(3)->expandableLimitedList(),
            TextColumn::make('created_at')->label(__('api_tokens.created'))->dateTime()->sortable(),
            TextColumn::make('expires_at')->label(__('api_tokens.expires'))->dateTime()->sortable(),
            TextColumn::make('last_used_at')->label(__('api_tokens.last_used'))->dateTime()->placeholder(__('api_tokens.never')),
        ])->recordActions([
            Action::make('revoke')->label(__('api_tokens.revoke'))->icon(Heroicon::OutlinedTrash)->color('danger')->requiresConfirmation()->modalDescription(__('api_tokens.revoke_confirmation'))->action(function (PersonalAccessToken $record): void {
                app(ApiTokenService::class)->revoke(auth()->user(), $record);
                Notification::make()->title(__('api_tokens.revoked'))->success()->send();
            }),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleOverview')->label(fn (): string => __($this->showAll ? 'api_tokens.mine' : 'api_tokens.all'))->visible(fn (): bool => auth()->user()->isAdmin())->action(function (): void {
                abort_unless(auth()->user()->isAdmin(), 403);
                $this->showAll = ! $this->showAll;
                $this->resetTable();
            }),
            Action::make('createToken')->label(__('api_tokens.create'))->icon(Heroicon::OutlinedPlus)->schema([
                TextInput::make('name')->label(__('api_tokens.name'))->required()->maxLength(255),
                CheckboxList::make('access')->label(__('api_tokens.access'))->options(['mcp' => 'MCP', 'rest' => 'REST'])->default(['mcp'])->required()->minItems(1),
                DateTimePicker::make('expires_at')->label(__('api_tokens.expires'))->default(fn (): string => now()->addDays(90)->toDateTimeString())->required()->after('now'),
                Select::make('preset')->label(__('api_tokens.preset'))->options(['read' => __('api_tokens.read'), 'edit' => __('api_tokens.edit')])->default('edit')->live()->afterStateUpdated(fn (?string $state, Set $set) => $set('abilities', app(ApiTokenService::class)->preset(auth()->user(), $state ?? 'read'))),
                CheckboxList::make('abilities')->label(__('api_tokens.abilities'))->options(fn (): array => collect(app(ApiTokenService::class)->availableAbilities(auth()->user()))->mapWithKeys(fn (string $ability): array => [$ability => $this->abilityLabel($ability)])->all())->default(fn (): array => app(ApiTokenService::class)->preset(auth()->user(), 'edit'))->required()->minItems(1),
            ])->action(function (array $data): void {
                abort_unless(static::canAccess(), 403);
                $token = app(ApiTokenService::class)->create(auth()->user(), $data);
                $this->replaceMountedAction('showToken', ['token' => $token->plainTextToken]);
                $this->forceRender();
            }),
        ];
    }

    public function showTokenAction(): Action
    {
        return Action::make('showToken')->label(__('api_tokens.secret'))->modalHeading(__('api_tokens.secret'))->modalDescription(__('api_tokens.once'))->schema(fn (array $arguments): array => [TextEntry::make('token')->label(__('api_tokens.secret'))->state($arguments['token'] ?? '')->copyable()])->modalSubmitAction(false)->modalCancelActionLabel(__('api_tokens.close'));
    }

    protected function abilityLabel(string $ability): string
    {
        if (str_starts_with($ability, 'access_')) {
            return strtoupper(substr($ability, 7));
        }
        $label = __('api_tokens.permissions.'.$ability);

        return $label === 'api_tokens.permissions.'.$ability ? $ability : $label;
    }
}
