<?php

namespace App\Notifications\Concerns;

trait HasNotificationDelivery
{
    protected ?string $deliveryEventKey = null;

    /**
     * @var array<int, string>
     */
    protected array $deliveryChannels = ['mail', 'database'];

    public function setDeliveryEventKey(string $eventKey): static
    {
        $this->deliveryEventKey = $eventKey;

        return $this;
    }

    public function deliveryEventKey(): ?string
    {
        return $this->deliveryEventKey;
    }

    /**
     * @param  array<int, string>  $channels
     */
    public function setDeliveryChannels(array $channels): static
    {
        $this->deliveryChannels = array_values(array_intersect(['mail', 'database'], $channels));

        return $this;
    }
}
