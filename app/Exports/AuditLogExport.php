<?php

namespace App\Exports;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?Carbon $dateFrom = null,
        protected ?Carbon $dateTo = null,
    ) {
        $this->dateFrom ??= now()->subMonth();
        $this->dateTo ??= now();
    }

    public function collection(): Collection
    {
        return AuditLog::with('user')
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'User',
            'Action',
            'Model Type',
            'Model ID',
            'IP Address',
            'User Agent',
            'Created At',
        ];
    }

    /**
     * @param  AuditLog  $auditLog
     * @return array<int, string>
     */
    public function map($auditLog): array
    {
        return [
            $auditLog->user?->name ?? 'System',
            $auditLog->action,
            $auditLog->model_type,
            (string) $auditLog->model_id,
            $auditLog->ip_address ?? '',
            $this->truncateUserAgent($auditLog->user_agent),
            $auditLog->created_at?->format('d.m.Y H:i:s') ?? '',
        ];
    }

    protected function truncateUserAgent(?string $userAgent): string
    {
        if (! $userAgent) {
            return '';
        }

        return mb_strlen($userAgent) > 120
            ? mb_substr($userAgent, 0, 117).'...'
            : $userAgent;
    }
}
