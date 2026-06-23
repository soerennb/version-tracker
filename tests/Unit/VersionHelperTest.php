<?php

namespace Tests\Unit;

use App\Helpers\VersionHelper;
use PHPUnit\Framework\TestCase;

class VersionHelperTest extends TestCase
{
    public function test_it_validates_semver_versions(): void
    {
        $this->assertTrue(VersionHelper::isValidSemver('1.2.3'));
        $this->assertTrue(VersionHelper::isValidSemver('1.2.3-rc.1'));
        $this->assertTrue(VersionHelper::isValidSemver('1.2.3+build.5'));
        $this->assertTrue(VersionHelper::isValidSemver('1.2.3-rc.1+build.5'));

        $this->assertFalse(VersionHelper::isValidSemver('1.2'));
        $this->assertFalse(VersionHelper::isValidSemver('01.2.3'));
        $this->assertFalse(VersionHelper::isValidSemver('1.2.3-'));
    }

    public function test_it_compares_pre_releases_before_stable_releases(): void
    {
        $this->assertSame(-1, VersionHelper::compareVersions('1.2.3-rc.1', '1.2.3'));
        $this->assertSame(1, VersionHelper::compareVersions('1.2.3', '1.2.3-rc.1'));
        $this->assertSame(0, VersionHelper::compareVersions('1.2.3+build.1', '1.2.3+build.2'));
    }

    public function test_it_compares_pre_release_identifiers(): void
    {
        $this->assertSame(-1, VersionHelper::compareVersions('1.0.0-alpha', '1.0.0-alpha.1'));
        $this->assertSame(-1, VersionHelper::compareVersions('1.0.0-alpha.1', '1.0.0-alpha.beta'));
        $this->assertSame(-1, VersionHelper::compareVersions('1.0.0-beta.2', '1.0.0-beta.11'));
        $this->assertSame(1, VersionHelper::compareVersions('1.0.0-rc.1', '1.0.0-beta.11'));
    }

    public function test_next_version_ignores_pre_release_and_build_metadata(): void
    {
        $this->assertSame('1.2.4', VersionHelper::getNextVersion('1.2.3-rc.1+build.5'));
        $this->assertSame('1.3.0', VersionHelper::getNextVersion('1.2.3-rc.1', 'minor'));
        $this->assertSame('2.0.0', VersionHelper::getNextVersion('1.2.3+build.5', 'major'));
    }
}
