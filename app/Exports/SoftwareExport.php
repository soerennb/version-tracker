<?php

namespace App\Exports;

use App\Models\Software;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SoftwareExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?Collection $software = null,
    ) {
        //
    }

    public function collection(): Collection
    {
        return $this->software ?? Software::with(['versions', 'creator'])->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'Status',
            'Current Version',
            'Last Release',
            'Version Count',
            'Created By',
            'Created At',
        ];
    }

    /**
     * @param  Software  $software
     * @return array<int, string>
     */
    public function map($software): array
    {
        return [
            $software->name,
            $software->description ?? '',
            $software->status?->value ?? '',
            $software->current_version ?? '',
            $software->last_release_date?->format('d.m.Y') ?? '',
            (string) $software->versions->count(),
            $software->creator?->name ?? 'System',
            $software->created_at?->format('d.m.Y H:i') ?? '',
        ];
    }
}
