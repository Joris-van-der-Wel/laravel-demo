<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A share is a collection of files.
 * The owner has full access to the share. The owner can share the share with other users or make it public.
 *
 * @property string $id ULID (PK)
 * @property ?Carbon created_at
 * @property ?Carbon updated_at
 * @property ?Carbon deleted_at
 * @property int $owner_id
 * @property string $name
 * @property string $description
 * @property ?string $public_token
 * @property ?string $password (hashed)
 */
class Share extends Model
{
    /** @use HasFactory<\Database\Factories\ShareFactory> */
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'password',
    ];

    /**
    * The User that owns the share.
     *
     * @return BelongsTo<User, $this>
    */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the Files that are part of this share
     *
     * @return HasMany<File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'share_id');
    }

    /**
     * Get the ShareUserAccess configured for this Share, which defines which users have access to this share.
     *
     * @return BelongsToMany<User, $this>
     */
    public function userAccess(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'share_user_access')
            ->withPivot('permission')
            ->as('user_access');
    }

    /**
     * The audit log lines related to this share
     *
     * @return HasMany<ShareAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ShareAuditLog::class, 'share_id');
    }

    public static function whereHasAccess(?User $user): Builder {
        if (!$user) {
            return Share::where('id', null);
        }

        $user_id = $user->id;

        // logical group the filter so that the builder can be used further
        return self::where(function($query) use ($user_id) {
            $query
                ->where('owner_id', $user_id)
                ->orWhereExists(
                    DB::table('share_user_access')
                        ->whereColumn('share_id', 'shares.id')
                        ->where('user_id', $user_id)
                );
        });
    }
}

