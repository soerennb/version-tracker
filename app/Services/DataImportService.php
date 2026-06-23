<?php

namespace App\Services;

use App\Enums\VulnerabilitySeverity;
use App\Helpers\VersionHelper;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Support\Facades\DB;

class DataImportService
{
    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int, string>}
     */
    public function importJson(string $json, bool $dryRun = false): array
    {
        $payload = json_decode($json, true);

        if (! is_array($payload)) {
            return $this->error('Invalid JSON payload.');
        }

        return $this->importPayload($payload, $dryRun);
    }

    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int, string>}
     */
    public function importCsv(string $entity, string $csv, bool $dryRun = false): array
    {
        $rows = $this->parseCsv($csv);

        if ($rows === null) {
            return $this->error('Invalid CSV payload.');
        }

        return $this->importPayload([$entity => $rows], $dryRun);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{created:int,updated:int,skipped:int,errors:array<int, string>}
     */
    public function importPayload(array $payload, bool $dryRun = false): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => $this->validatePayload($payload),
        ];

        if ($result['errors'] !== []) {
            return $result;
        }

        $operations = function () use ($payload, &$result): void {
            foreach ($payload['software'] ?? [] as $row) {
                $software = Software::query()->firstOrNew(['name' => $row['name']]);
                $software->fill([
                    'description' => $row['description'] ?? $software->description ?? '',
                    'status' => $row['status'] ?? $software->status?->value ?? 'active',
                    'license_type' => $row['license_type'] ?? $software->license_type,
                    'compliance_status' => $row['compliance_status'] ?? $software->compliance_status?->value ?? 'unknown',
                    'github_repo_url' => $row['github_repo_url'] ?? $software->github_repo_url,
                ]);
                $software->exists ? $result['updated']++ : $result['created']++;
                $software->save();
            }

            foreach ($payload['versions'] ?? [] as $row) {
                $software = Software::query()->where('name', $row['software_name'])->firstOrFail();
                $version = Version::query()->firstOrNew([
                    'software_id' => $software->id,
                    'version_number' => $row['version_number'],
                ]);
                $version->fill([
                    'release_date' => $row['release_date'],
                    'support_status' => $row['support_status'] ?? $version->support_status?->value,
                    'eol_date' => $row['eol_date'] ?? $version->eol_date,
                    'lts_date' => $row['lts_date'] ?? $version->lts_date,
                ]);
                $version->exists ? $result['updated']++ : $result['created']++;
                $version->save();
            }

            foreach ($payload['dependencies'] ?? [] as $row) {
                $software = Software::query()->where('name', $row['software_name'])->firstOrFail();
                $dependsOnSoftware = Software::query()->where('name', $row['depends_on_software_name'])->firstOrFail();
                $dependency = SoftwareDependency::query()->firstOrNew([
                    'software_id' => $software->id,
                    'depends_on_software_id' => $dependsOnSoftware->id,
                ]);
                $dependency->fill([
                    'dependency_type' => $row['dependency_type'] ?? 'runtime',
                    'min_version_id' => $this->versionId($dependsOnSoftware, $row['min_version'] ?? null),
                    'max_version_id' => $this->versionId($dependsOnSoftware, $row['max_version'] ?? null),
                ]);
                $dependency->exists ? $result['updated']++ : $result['created']++;
                $dependency->save();
            }

            foreach ($payload['vulnerabilities'] ?? [] as $row) {
                $software = Software::query()->where('name', $row['software_name'])->firstOrFail();
                $version = $software->versions()->where('version_number', $row['version_number'])->firstOrFail();
                $vulnerability = Vulnerability::query()->firstOrNew([
                    'cve_id' => $row['cve_id'],
                    'affected_version_id' => $version->id,
                ]);
                $vulnerability->fill([
                    'severity' => $row['severity'] ?? VulnerabilitySeverity::MEDIUM->value,
                    'description' => $row['description'] ?? $vulnerability->description ?? '',
                    'published_date' => $row['published_date'] ?? $vulnerability->published_date ?? now()->toDateString(),
                    'source' => $row['source'] ?? $vulnerability->source ?? 'manual',
                    'source_url' => $row['source_url'] ?? $vulnerability->source_url,
                    'affected_range' => $row['affected_range'] ?? $vulnerability->affected_range,
                    'status' => $row['status'] ?? $vulnerability->status?->value ?? 'open',
                    'exploitability' => $row['exploitability'] ?? $vulnerability->exploitability?->value ?? 'unknown',
                ]);
                $vulnerability->exists ? $result['updated']++ : $result['created']++;
                $vulnerability->save();
            }
        };

        if ($dryRun) {
            foreach (['software', 'versions', 'dependencies', 'vulnerabilities'] as $entity) {
                $result['created'] += count($payload[$entity] ?? []);
            }

            return $result;
        }

        DB::transaction($operations);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    protected function validatePayload(array $payload): array
    {
        $errors = [];

        foreach ($payload['software'] ?? [] as $index => $row) {
            if (blank($row['name'] ?? null)) {
                $errors[] = "software.$index.name is required.";
            }
        }

        foreach ($payload['versions'] ?? [] as $index => $row) {
            if (blank($row['software_name'] ?? null) || ! Software::query()->where('name', $row['software_name'])->exists()) {
                $errors[] = "versions.$index.software_name does not exist.";
            }

            if (blank($row['version_number'] ?? null) || ! VersionHelper::isValidSemver((string) ($row['version_number'] ?? ''))) {
                $errors[] = "versions.$index.version_number must be valid SemVer.";
            }

            if (blank($row['release_date'] ?? null)) {
                $errors[] = "versions.$index.release_date is required.";
            }
        }

        foreach ($payload['dependencies'] ?? [] as $index => $row) {
            $software = Software::query()->where('name', $row['software_name'] ?? null)->first();
            $dependsOnSoftware = Software::query()->where('name', $row['depends_on_software_name'] ?? null)->first();

            if (! $software) {
                $errors[] = "dependencies.$index.software_name does not exist.";
            }

            if (! $dependsOnSoftware) {
                $errors[] = "dependencies.$index.depends_on_software_name does not exist.";
            }

            foreach (['min_version', 'max_version'] as $field) {
                if ($dependsOnSoftware && filled($row[$field] ?? null) && $this->versionId($dependsOnSoftware, $row[$field]) === null) {
                    $errors[] = "dependencies.$index.$field does not exist for dependency target.";
                }
            }
        }

        foreach ($payload['vulnerabilities'] ?? [] as $index => $row) {
            $software = Software::query()->where('name', $row['software_name'] ?? null)->first();

            if (! $software) {
                $errors[] = "vulnerabilities.$index.software_name does not exist.";
            } elseif (! $software->versions()->where('version_number', $row['version_number'] ?? null)->exists()) {
                $errors[] = "vulnerabilities.$index.version_number does not exist for software.";
            }

            if (blank($row['cve_id'] ?? null)) {
                $errors[] = "vulnerabilities.$index.cve_id is required.";
            }
        }

        return $errors;
    }

    /**
     * @return array<int, array<string, string>>|null
     */
    protected function parseCsv(string $csv): ?array
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return null;
        }

        fwrite($handle, $csv);
        rewind($handle);

        $headers = fgetcsv($handle);

        if (! is_array($headers)) {
            fclose($handle);

            return null;
        }

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                fclose($handle);

                return null;
            }

            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    protected function versionId(Software $software, mixed $versionNumber): ?int
    {
        if (blank($versionNumber)) {
            return null;
        }

        return $software->versions()
            ->where('version_number', $versionNumber)
            ->value('id');
    }

    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int, string>}
     */
    protected function error(string $message): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [$message],
        ];
    }
}
