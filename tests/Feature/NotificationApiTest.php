<?php

namespace Tests\Feature;

use App\Enums\SubscriptionEvent;
use App\Enums\VersionStatus;
use App\Models\NotificationDelivery;
use App\Models\Software;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Version;
use App\Notifications\VersionPublishedNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_only_see_and_can_manage_their_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '2.0.0',
        ]);

        $user->notify(new VersionPublishedNotification($version));
        $otherUser->notify(new VersionPublishedNotification($version));
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.version_id', $version->id)
            ->assertJsonPath('data.0.read_at', null);
        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $notificationId = $user->notifications()->firstOrFail()->id;
        $readResponse = $this->postJson('/api/notifications/'.$notificationId.'/read');
        $readResponse->assertOk();
        $this->assertNotNull($readResponse->json('data.read_at'));
        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->postJson('/api/notifications/read-all')->assertNoContent();
        Sanctum::actingAs($otherUser);
        $this->getJson('/api/notifications')->assertJsonCount(1, 'data');
    }

    public function test_release_notifications_are_deduplicated_and_record_delivery_status(): void
    {
        $user = User::factory()->create();
        $software = Software::factory()->create([
            'created_by' => $user->id,
        ]);
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '2.1.0',
        ]);
        Subscription::factory()->for($user)->for($software)->create([
            'event' => SubscriptionEvent::RELEASE,
        ]);

        app(NotificationService::class)->notifyVersionPublished($version);
        app(NotificationService::class)->notifyVersionPublished($version);

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $user->id,
            'event_key' => 'version-published:'.$version->id,
            'status' => 'sent',
        ]);
        $this->assertSame(1, NotificationDelivery::query()
            ->where('user_id', $user->id)
            ->where('event_key', 'version-published:'.$version->id)
            ->count());
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_unverified_users_cannot_manage_subscriptions(): void
    {
        $user = User::factory()->unverified()->create();
        $software = Software::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscriptions', [
            'software_id' => $software->id,
            'event' => SubscriptionEvent::RELEASE->value,
        ])->assertForbidden();
    }

    public function test_verified_users_can_create_list_and_delete_subscriptions(): void
    {
        $user = User::factory()->create();
        $software = Software::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/subscriptions', [
            'software_id' => $software->id,
            'event' => SubscriptionEvent::SECURITY->value,
        ])->assertCreated()
            ->assertJsonPath('data.software.id', $software->id)
            ->assertJsonPath('data.event', SubscriptionEvent::SECURITY->value);

        $this->getJson('/api/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $subscription = Subscription::query()->firstOrFail();
        $this->deleteJson('/api/subscriptions/'.$subscription->id)->assertNoContent();
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }
}
