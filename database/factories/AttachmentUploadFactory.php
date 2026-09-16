<?php

namespace Database\Factories;

use App\Models\AttachmentUpload;
use App\Models\User;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttachmentUpload> */
class AttachmentUploadFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'personal_access_token_id' => fn (array $attributes): int => User::query()->findOrFail($attributes['user_id'])->createToken('Upload', ['access_mcp', 'upload_files'])->accessToken->id, 'version_id' => Version::factory(), 'filename' => 'release.txt', 'metadata' => [], 'expires_at' => now()->addMinutes(15)];
    }
}
