<?php
declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // Users are part of immutable audit logging (see ShareAuditLog model), so make sure users records are never really
    // deleted
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The shares owned by this user
     *
     * @return HasMany<Share, $this>
     */
    public function ownedShares(): HasMany
    {
        return $this->hasMany(Share::class, 'owner_id');
    }

    /**
     * The files uploaded by this user
     *
     * @return HasMany<File, $this>
     */
    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'uploader_id');
    }

    /**
     * The shares for which access has be given to this user
     *
     * @return BelongsToMany<Share, $this>
     */
    public function shareAccess(): BelongsToMany
    {
        return $this->belongsToMany(Share::class, 'share_user_access')
            ->withPivot('permission')
            ->as('share_access');
    }
}
