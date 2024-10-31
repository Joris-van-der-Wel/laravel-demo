<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word() . '.jpg';
        return [
            'share_id' => Share::factory(),
            'uploader_id' => User::factory(),
            'name' => $name,
            'fs_path' => 'fake/' . $name,
            'description' => fake()->text(),
            'size' => fake()->numberBetween(10000, 100000000),
            'webp_thumbnail' => null,
        ];
    }
}
