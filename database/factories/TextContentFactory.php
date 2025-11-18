<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Models\TextContent;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TextContent>
 */
class TextContentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_id' => Version::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'language' => fake()->randomElement(array_map(static fn (Language $language): string => $language->value, Language::cases())),
        ];
    }
}
