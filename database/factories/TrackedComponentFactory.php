<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\TrackedComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedComponent>
 */
class TrackedComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'kind' => 'interface',
            'customer_id' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(['kind' => 'customization', 'customer_id' => $customer->id]);
    }
}
