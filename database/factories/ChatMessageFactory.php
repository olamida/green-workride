<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'sender_id' => User::factory(),
            'message' => fake()->sentence(),
        ];
    }
}
