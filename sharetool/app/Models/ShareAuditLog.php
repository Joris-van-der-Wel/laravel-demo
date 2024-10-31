<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property Carbon timestamp
 * @property string $share_id
 * @property ?string $file_id
 * @property ?int $user_id
 * @property string $type
 * @property array $details
 */
class ShareAuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\ShareAuditLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return BelongsTo<Share, $this>
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class, 'share_id');
    }

    /**
     * @return BelongsTo<?File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * @return BelongsTo<?User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
