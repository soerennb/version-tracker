<?php

namespace Tests\Feature;

use App\Enums\SubscriptionEvent;
use App\Models\Software;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_and_delete_own_subscription(): void
    {
        $user = User::factory()->create();
        $software = Software::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/subscriptions', [
            'software_id' => $software->id,
            'event' => SubscriptionEvent::SECURITY->value,
        ])->assertCreated();

        $subscriptionId = $response->json('data.id');

        $this->getJson('/api/subscriptions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $subscriptionId);

        $this->deleteJson('/api/subscriptions/'.$subscriptionId)
            ->assertNoContent();

        $this->assertDatabaseMissing('subscriptions', ['id' => $subscriptionId]);
    }

    public function test_user_cannot_delete_another_users_subscription(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $subscription = Subscription::factory()->for($owner)->create();
        Sanctum::actingAs($other);

        $this->deleteJson('/api/subscriptions/'.$subscription->id)
            ->assertNotFound();
    }
}
