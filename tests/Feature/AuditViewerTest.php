<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Models\Software;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_audit_list_uses_readable_event_and_model_labels(): void
    {
        $software = Software::factory()->create(['name' => 'Core Registry']);
        $audit = AuditLog::factory()->create([
            'action' => 'software.updated',
            'model_type' => Software::class,
            'model_id' => $software->id,
            'old_values' => ['name' => 'Old Registry'],
            'new_values' => ['name' => 'Core Registry'],
        ]);

        Livewire::test(ListAuditLogs::class)
            ->assertCanSeeTableRecords([$audit])
            ->assertTableColumnStateSet('action_label', __('filament.audit.actions.software.updated'), record: $audit)
            ->assertTableColumnStateSet('model_label', __('filament.audit.models.Software'), record: $audit)
            ->assertTableColumnStateSet('changes_count', 1, record: $audit);
    }

    public function test_audit_detail_renders_subject_and_field_diff(): void
    {
        $software = Software::factory()->create(['name' => 'Core Registry']);
        $audit = AuditLog::factory()->create([
            'action' => 'software.updated',
            'model_type' => Software::class,
            'model_id' => $software->id,
            'old_values' => ['name' => 'Old Registry', 'status' => 'inactive'],
            'new_values' => ['name' => 'Core Registry', 'status' => 'active'],
        ]);

        Livewire::test(ViewAuditLog::class, ['record' => $audit->id])
            ->assertOk()
            ->assertSee('Core Registry')
            ->assertSee('Old Registry')
            ->assertSee('inactive')
            ->assertSee('active');
    }

    public function test_audit_resource_is_read_only(): void
    {
        $this->assertFalse(AuditLogResource::canCreate());
        $this->assertArrayNotHasKey('create', AuditLogResource::getPages());
        $this->assertArrayNotHasKey('edit', AuditLogResource::getPages());
        $this->assertArrayHasKey('view', AuditLogResource::getPages());
    }
}
