<?php

namespace App\Events;

use App\Models\P2pTransfer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class P2pTransferCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public P2pTransfer $transfer) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->transfer->receiver_user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'P2pTransferCompleted';
    }

    public function broadcastWith(): array
    {
        return [
            'reference' => $this->transfer->reference,
            'amount' => (float) $this->transfer->amount,
            'type' => $this->transfer->type->value,
        ];
    }
}
