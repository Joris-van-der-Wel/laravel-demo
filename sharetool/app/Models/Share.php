<?php
declare(strict_types=1);

namespace App\Models;

use App\NotSet;
use App\SharePermission;
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
     * Returns the permission level the given user has to this share.
     */
    public function getUserPermission(?User $user): SharePermission {
        if (!$user) {
            return SharePermission::None;
        }

        if ($user->id === $this->owner_id) {
            return SharePermission::Owner;
        }

        $permission = $this->userAccess()
            ->where('user_id', $user->id)
            ->first()
            ?->user_access
            ->permission;

        if ($permission === 'read') {
            return SharePermission::Read;
        }
        else if ($permission === 'write') {
            return SharePermission::Write;
        }

        return SharePermission::None;
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

    public function addAuditLog(string $type, array $details = []): ShareAuditLog {
        $log = new ShareAuditLog;
        $log->share_id = $this->id;
        $log->timestamp = Carbon::now();
        $log->file_id = null;
        $log->user_id = auth()->user()?->id;
        $log->type = $type;
        $log->details = json_encode($details);
        $log->save();
        return $log;
    }

    // todo can we extend the laravel builder?

    /**
     * Select all shares where the given user has at least permission to read it.
     */
    public static function whereUserHasAccess(User | NotSet | null $user = new NotSet): Builder {
        if ($user instanceof NotSet) {
            $user = auth()->user();
        }

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

