<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Facades\App\Services\SecureRandom;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Share>
 */
class ShareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->jobTitle(),
            'description' => fake()->text(),
            'public_token' => fake()->boolean() ? SecureRandom::urlSafeToken(64) : null,
            'password' => fake()->boolean() ? Hash::make('password') : null,
        ];
    }
}
