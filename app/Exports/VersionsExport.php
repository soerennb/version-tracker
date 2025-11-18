<?php

namespace App\Exports;

use App\Models\Version;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VersionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected ?Collection $versions = null,
    ) {
        //
    }

    public function collection(): Collection
    {
        return $this->versions ?? Version::with(['software', 'textContents', 'fileAttachments', 'vulnerabilities'])->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Software',
            'Version',
            'Release Date',
            'Status',
            'Approval Status',
            'Description (DE)',
            'Description (EN)',
            'Attachments',
            'Vulnerabilities',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param  Version  $version
     * @return array<int, string>
     */
    public function map($version): array
    {
        $germanContent = $version->textContents->firstWhere('language', 'de');
        $englishContent = $version->textContents->firstWhere('language', 'en');

        return [
            $version->software?->name ?? 'n/a',
            $version->version_number,
            $version->release_date?->format('d.m.Y') ?? '',
            $version->status?->value ?? '',
            $version->approval_status?->value ?? '',
            $germanContent?->content ?? '',
            $englishContent?->content ?? '',
            (string) $version->fileAttachments->count(),
            (string) $version->vulnerabilities->count(),
            $version->created_at?->format('d.m.Y H:i') ?? '',
            $version->updated_at?->format('d.m.Y H:i') ?? '',
        ];
    }
}
