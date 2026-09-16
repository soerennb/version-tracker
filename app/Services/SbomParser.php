<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

class SbomParser
{
    /**
     * Parse a CycloneDX or SPDX JSON document into a stable internal shape.
     *
     * @return array{format:string,spec_version:?string,serial_number:?string,components:array<int,array<string,mixed>>,findings:array<int,array<string,mixed>>}
     */
    public function parse(string $payload, ?string $format = null): array
    {
        if (trim($payload) === '') {
            throw new InvalidArgumentException('The SBOM document is empty.');
        }

        try {
            $document = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The SBOM document is not valid JSON.', previous: $exception);
        }

        if (! is_array($document)) {
            throw new InvalidArgumentException('The SBOM document must contain a JSON object.');
        }

        $format = $this->normalizeFormat($format, $document);

        return match ($format) {
            'cyclonedx' => $this->parseCycloneDx($document),
            'spdx' => $this->parseSpdx($document),
            default => throw new InvalidArgumentException('Unsupported SBOM format. Use CycloneDX or SPDX JSON.'),
        };
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function normalizeFormat(?string $format, array $document): string
    {
        $format = Str::lower(trim((string) $format));

        if ($format === '') {
            $format = Str::lower((string) ($document['bomFormat'] ?? ''));

            if ($format === '') {
                $format = array_key_exists('spdxVersion', $document) ? 'spdx' : '';
            }
        }

        return match ($format) {
            'cyclonedx', 'cyclone-dx', 'cyclone_dx' => 'cyclonedx',
            'spdx' => 'spdx',
            default => throw new InvalidArgumentException('Unsupported SBOM format. Use CycloneDX or SPDX JSON.'),
        };
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array{format:string,spec_version:?string,serial_number:?string,components:array<int,array<string,mixed>>,findings:array<int,array<string,mixed>>}
     */
    private function parseCycloneDx(array $document): array
    {
        $components = [];

        foreach ((array) ($document['components'] ?? []) as $index => $component) {
            if (! is_array($component)) {
                continue;
            }

            $name = $this->stringValue($component['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $components[] = [
                'bom_ref' => $this->stringValue($component['bom-ref'] ?? null) ?? 'component-'.($index + 1),
                'package_type' => $this->stringValue($component['type'] ?? null),
                'group_name' => $this->stringValue($component['group'] ?? null),
                'name' => $name,
                'version' => $this->stringValue($component['version'] ?? null),
                'purl' => $this->stringValue($component['purl'] ?? null),
                'cpe' => $this->stringValue($component['cpe'] ?? null),
                'supplier' => $this->stringValue(Arr::get($component, 'supplier.name')),
                'licenses' => $this->licenses($component['licenses'] ?? []),
                'hashes' => $this->hashes($component['hashes'] ?? []),
                'properties' => $this->properties($component['properties'] ?? []),
            ];
        }

        $findings = [];

        foreach ((array) ($document['vulnerabilities'] ?? []) as $vulnerability) {
            if (! is_array($vulnerability)) {
                continue;
            }

            $externalId = $this->stringValue($vulnerability['id'] ?? null)
                ?? $this->stringValue(Arr::get($vulnerability, 'source.id'));

            if ($externalId === null) {
                continue;
            }

            $rating = collect((array) ($vulnerability['ratings'] ?? []))
                ->filter(static fn (mixed $rating): bool => is_array($rating))
                ->first() ?? [];
            $affectedRefs = collect((array) ($vulnerability['affects'] ?? []))
                ->map(fn (mixed $affect): ?string => is_array($affect)
                    ? ($this->stringValue($affect['ref'] ?? null) ?? $this->stringValue(Arr::get($affect, 'target.ref')))
                    : null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($affectedRefs === []) {
                $affectedRefs = [null];
            }

            foreach ($affectedRefs as $bomRef) {
                $findings[] = [
                    'bom_ref' => $bomRef,
                    'external_id' => $externalId,
                    'source' => $this->stringValue($rating['source'] ?? null)
                        ?? $this->stringValue($vulnerability['source'] ?? null)
                        ?? 'CycloneDX',
                    'severity' => $this->normalizeSeverity($rating['severity'] ?? null),
                    'cvss_score' => $this->score($rating),
                    'exploitability' => $this->normalizeExploitability(Arr::get($vulnerability, 'analysis.state')),
                    'status' => $this->normalizeStatus(Arr::get($vulnerability, 'analysis.state')),
                    'description' => $this->stringValue($vulnerability['description'] ?? null),
                    'affected_range' => $this->stringValue(Arr::get($vulnerability, 'affects.0.range')),
                    'fixed_version' => $this->stringValue(Arr::get($vulnerability, 'recommendation')),
                    'source_url' => $this->stringValue(Arr::get($vulnerability, 'source.url')),
                    'details' => [
                        'aliases' => array_values(array_filter((array) ($vulnerability['advisories'] ?? []), 'is_scalar')),
                    ],
                ];
            }
        }

        return [
            'format' => 'cyclonedx',
            'spec_version' => $this->stringValue($document['specVersion'] ?? null),
            'serial_number' => $this->stringValue($document['serialNumber'] ?? null),
            'components' => $components,
            'findings' => $findings,
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array{format:string,spec_version:?string,serial_number:?string,components:array<int,array<string,mixed>>,findings:array<int,array<string,mixed>>}
     */
    private function parseSpdx(array $document): array
    {
        $components = [];

        foreach ((array) ($document['packages'] ?? []) as $index => $package) {
            if (! is_array($package)) {
                continue;
            }

            $name = $this->stringValue($package['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $purl = collect((array) ($package['externalRefs'] ?? []))
                ->filter(static fn (mixed $reference): bool => is_array($reference))
                ->map(fn (array $reference): ?string => str_contains(Str::lower((string) ($reference['referenceType'] ?? '')), 'purl')
                    ? $this->stringValue($reference['referenceLocator'] ?? null)
                    : null)
                ->filter()
                ->first();

            $components[] = [
                'bom_ref' => $this->stringValue($package['SPDXID'] ?? null) ?? 'package-'.($index + 1),
                'package_type' => 'library',
                'group_name' => null,
                'name' => $name,
                'version' => $this->stringValue($package['versionInfo'] ?? null),
                'purl' => $purl,
                'cpe' => null,
                'supplier' => $this->stringValue($package['supplier'] ?? null),
                'licenses' => array_values(array_filter([
                    $this->stringValue($package['licenseDeclared'] ?? null),
                    $this->stringValue($package['licenseConcluded'] ?? null),
                ])),
                'hashes' => $this->hashes((array) ($package['checksums'] ?? []), 'algorithm', 'checksumValue'),
                'properties' => [],
            ];
        }

        $findings = [];
        foreach ((array) ($document['vulnerabilities'] ?? []) as $vulnerability) {
            if (! is_array($vulnerability)) {
                continue;
            }

            $externalId = $this->stringValue($vulnerability['id'] ?? null)
                ?? $this->stringValue($vulnerability['externalId'] ?? null);
            if ($externalId === null) {
                continue;
            }

            $refs = collect((array) ($vulnerability['affects'] ?? []))
                ->map(fn (mixed $affect): ?string => is_array($affect) ? $this->stringValue($affect['ref'] ?? $affect['SPDXID'] ?? null) : null)
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($refs === []) {
                $refs = [null];
            }

            foreach ($refs as $bomRef) {
                $findings[] = [
                    'bom_ref' => $bomRef,
                    'external_id' => $externalId,
                    'source' => 'SPDX',
                    'severity' => $this->normalizeSeverity($vulnerability['severity'] ?? null),
                    'cvss_score' => $this->score($vulnerability),
                    'exploitability' => $this->normalizeExploitability($vulnerability['status'] ?? null),
                    'status' => $this->normalizeStatus($vulnerability['status'] ?? null),
                    'description' => $this->stringValue($vulnerability['description'] ?? null),
                    'affected_range' => $this->stringValue($vulnerability['affectedRange'] ?? null),
                    'fixed_version' => $this->stringValue($vulnerability['fixedVersion'] ?? null),
                    'source_url' => $this->stringValue($vulnerability['sourceUrl'] ?? null),
                    'details' => [],
                ];
            }
        }

        return [
            'format' => 'spdx',
            'spec_version' => $this->stringValue($document['spdxVersion'] ?? null),
            'serial_number' => $this->stringValue($document['documentNamespace'] ?? null),
            'components' => $components,
            'findings' => $findings,
        ];
    }

    /**
     * @param  array<string, mixed>  $rating
     */
    private function score(array $rating): ?float
    {
        foreach (['score', 'baseScore', 'cvss_score'] as $key) {
            if (isset($rating[$key]) && is_numeric($rating[$key])) {
                return (float) $rating[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $licenses
     * @return array<int, string>
     */
    private function licenses(array $licenses): array
    {
        return collect($licenses)
            ->map(function (mixed $license): ?string {
                if (is_string($license)) {
                    return $license;
                }

                if (! is_array($license)) {
                    return null;
                }

                return $this->stringValue(Arr::get($license, 'license.id'))
                    ?? $this->stringValue(Arr::get($license, 'license.name'))
                    ?? $this->stringValue($license['expression'] ?? null);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $hashes
     * @return array<string, string>
     */
    private function hashes(array $hashes, string $algorithmKey = 'alg', string $contentKey = 'content'): array
    {
        $normalized = [];

        foreach ($hashes as $hash) {
            if (! is_array($hash)) {
                continue;
            }

            $algorithm = $this->stringValue($hash[$algorithmKey] ?? null);
            $content = $this->stringValue($hash[$contentKey] ?? null);
            if ($algorithm !== null && $content !== null) {
                $normalized[$algorithm] = $content;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $properties
     * @return array<string, string>
     */
    private function properties(array $properties): array
    {
        $normalized = [];

        foreach ($properties as $property) {
            if (! is_array($property)) {
                continue;
            }

            $name = $this->stringValue($property['name'] ?? null);
            $value = $this->stringValue($property['value'] ?? null);
            if ($name !== null && $value !== null) {
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    private function normalizeSeverity(mixed $severity): ?string
    {
        return match (Str::lower(trim((string) $severity))) {
            'critical', 'urgent' => 'critical',
            'high', 'important' => 'high',
            'medium', 'moderate', 'moderate-high' => 'medium',
            'low', 'negligible', 'informational', 'info' => 'low',
            default => null,
        };
    }

    private function normalizeExploitability(mixed $state): string
    {
        return match (Str::lower(trim((string) $state))) {
            'exploited', 'exploitable', 'active' => 'active',
            'in_triage', 'proof_of_concept', 'proof-of-concept' => 'proof_of_concept',
            'resolved', 'fixed', 'not_affected' => 'no_known_exploit',
            default => 'unknown',
        };
    }

    private function normalizeStatus(mixed $state): string
    {
        return match (Str::lower(trim((string) $state))) {
            'resolved', 'fixed' => 'fixed',
            'false_positive', 'false-positive', 'not_affected' => 'false_positive',
            'accepted', 'under_review' => 'accepted',
            default => 'open',
        };
    }

    private function stringValue(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }
}
