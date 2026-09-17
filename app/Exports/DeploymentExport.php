<?php

namespace App\Exports;

use App\Models\Deployment;
use App\Models\DeploymentEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DeploymentExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected ?Collection $deployments = null,
    ) {}

    public function collection(): Collection
    {
        return $this->deployments ?? Deployment::query()
            ->with(['software', 'version', 'environment', 'creator', 'approver', 'executor'])
            ->with('events.actor')
            ->withCount('events')
            ->latest('created_at')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Software',
            'Version',
            'Environment',
            'Status',
            'Scheduled At',
            'Approved At',
            'Started At',
            'Completed At',
            'Created By',
            'Approved By',
            'Executed By',
            'Change Reference',
            'External Reference',
            'Source',
            'Events',
            'Timeline',
            'Notes',
            'Result',
            'Created At',
        ];
    }

    /**
     * @param  Deployment  $deployment
     * @return array<int, string>
     */
    public function map($deployment): array
    {
        return [
            $deployment->software?->name ?? '',
            $deployment->version?->version_number ?? '',
            $deployment->environment?->name ?? '',
            $deployment->status?->value ?? '',
            $deployment->scheduled_at?->format('d.m.Y H:i') ?? '',
            $deployment->approved_at?->format('d.m.Y H:i') ?? '',
            $deployment->started_at?->format('d.m.Y H:i') ?? '',
            $deployment->completed_at?->format('d.m.Y H:i') ?? '',
            $deployment->creator?->name ?? 'System',
            $deployment->approver?->name ?? 'System',
            $deployment->executor?->name ?? 'System',
            $deployment->change_reference ?? '',
            $deployment->external_reference ?? '',
            $deployment->source ?? '',
            (string) ($deployment->events_count ?? $deployment->events->count()),
            $deployment->events
                ->sortBy('created_at')
                ->map(fn (DeploymentEvent $event): string => implode(' | ', array_filter([
                    $event->created_at?->format('d.m.Y H:i:s'),
                    $event->type?->value,
                    $event->actor?->name ?? 'System',
                    $event->comment,
                ])))
                ->implode("\n"),
            $deployment->notes ?? '',
            $deployment->result ?? '',
            $deployment->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }
}
