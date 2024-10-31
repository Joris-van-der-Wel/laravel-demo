<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A specific file that has been created within a share.
 *
 * The actual file content is stored on a Laravel File Storage Disk, using a filename that is based on the primary key.
 *
 * @property string $id ULID
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 * @property string $share_id
 * @property int $uploader_id
 * @property string $fs_path
 * @property string $name
 * @property string $description
 * @property int $size
 * @property ?string $webp_thumbnail
 *
 */
class File extends Model
{
    /** @use HasFactory<\Database\Factories\FileFactory> */
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The share that this file belongs to
     *
     * @return BelongsTo<Share, $this>
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class, 'share_id');
    }

    /**
     * The user that uploaded this file
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * The audit log lines for this file
     *
     * @return HasMany<ShareAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ShareAuditLog::class, 'file_id');
    }
}
