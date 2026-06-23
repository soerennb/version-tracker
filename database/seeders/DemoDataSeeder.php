<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\ComplianceStatus;
use App\Enums\Language;
use App\Enums\SoftwareStatus;
use App\Enums\VersionStatus;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Populate the database with a single demo software and five versions.
     */
    public function run(): void
    {
        $owner = User::first() ?? User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);

        $software = Software::updateOrCreate(
            ['name' => 'Aurora Suite'],
            [
                'description' => 'A fictional platform showcasing version tracking with polished release notes.',
                'status' => SoftwareStatus::ACTIVE,
                'current_version' => '1.4.0',
                'last_release_date' => now()->subWeeks(2),
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
                'license_type' => 'Proprietary',
                'compliance_status' => ComplianceStatus::COMPLIANT,
                'github_repo_url' => 'https://github.com/example/aurora',
            ]
        );

        $releases = [
            [
                'version' => '1.0.0',
                'released' => Carbon::now()->subMonths(6),
                'summary' => [
                    Language::DE->value => 'Erstveröffentlichung mit dem Kern-Dashboard und Basis-APIs.',
                    Language::EN->value => 'Initial release with the core dashboard and foundational APIs.',
                ],
            ],
            [
                'version' => '1.1.0',
                'released' => Carbon::now()->subMonths(5),
                'summary' => [
                    Language::DE->value => 'Neue Automations-Engine und verbesserte Audit-Protokolle.',
                    Language::EN->value => 'Introduced the automation engine plus richer audit trails.',
                ],
            ],
            [
                'version' => '1.2.0',
                'released' => Carbon::now()->subMonths(3),
                'summary' => [
                    Language::DE->value => 'Support für mehrsprachige Inhalte der Release Notes.',
                    Language::EN->value => 'Added multilingual release notes and tighter dependency checks.',
                ],
            ],
            [
                'version' => '1.3.0',
                'released' => Carbon::now()->subMonth(),
                'summary' => [
                    Language::DE->value => 'Sicherheitsverbesserungen und CSV-Export.',
                    Language::EN->value => 'Security hardening plus CSV export for compliance teams.',
                ],
            ],
            [
                'version' => '1.4.0',
                'released' => Carbon::now()->subWeeks(2),
                'summary' => [
                    Language::DE->value => 'Visuelles Timeline-Widget sowie Genehmigungs-Workflow.',
                    Language::EN->value => 'Visual timeline widget and a streamlined approval workflow.',
                ],
            ],
        ];

        foreach ($releases as $release) {
            $version = $software->versions()->updateOrCreate(
                ['version_number' => $release['version']],
                [
                    'release_date' => $release['released'],
                    'status' => VersionStatus::PUBLISHED,
                    'approval_status' => ApprovalStatus::APPROVED,
                ]
            );

            foreach ($release['summary'] as $locale => $content) {
                TextContent::updateOrCreate(
                    [
                        'version_id' => $version->id,
                        'language' => $locale,
                    ],
                    [
                        'title' => "Aurora {$release['version']} Release Notes",
                        'content' => $content,
                    ]
                );
            }
        }
    }
}
