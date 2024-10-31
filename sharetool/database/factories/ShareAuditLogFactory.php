<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\Share;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShareAuditLog>
 */
class ShareAuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'share_id' => Share::factory(),
            'file_id' => null,
            'user_id' => null,
            'type' => 'share_update',
            'details' => '{}',
        ];
    }
}
