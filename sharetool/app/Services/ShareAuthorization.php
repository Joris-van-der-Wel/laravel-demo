<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ShareInvalidPasswordException;
use App\Exceptions\ShareInvalidPublicTokenException;
use App\Exceptions\ShareLoginRateLimited;
use App\Exceptions\SharePermissionException;
use App\Models\Share;
use App\Models\User;
use App\NotSet;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Illuminate\Support\Facades\RateLimiter;

class ShareAuthorization
{
    public function authorizeShare(
        string $shareId,
        ?string $publicToken = null,
        bool $skipPasswordCheck = false,
        string $requiredPermission = 'read',
        User | NotSet | null $user = new NotSet,
    ): Share {
        if ($user instanceof NotSet) {
            $user = auth()->user();
        }

        $authenticatedUsingToken = false;

        if ($publicToken) {
            $share = Share::
                where('id', $shareId)
                ->whereNotNull('public_token')
                ->firstOrFail();

            if (!hash_equals($share->public_token, $publicToken)) {
                throw new ShareInvalidPublicTokenException('Invalid public token for share');
            }

            $authenticatedUsingToken = true;
        } else {
            $share = Share::whereUserHasAccess($user)
                ->where('id', $shareId)
                ->firstOrFail();
        }

        $user_is_owner = $user &&  $user->id === $share->owner_id;

        if (
            !$skipPasswordCheck &&
            !$user_is_owner &&
            $share->password &&
            $share->password !== session()->get("share-password.$share->id")
        ) {
            throw new ShareInvalidPasswordException('Password for share in session has expired');
        }

        // authenticating with a public token implies read permission
        if (
            !($authenticatedUsingToken && $requiredPermission === 'read') &&
            !$this->hasSharePermission($share, $requiredPermission, user: $user)
        ) {
            throw new SharePermissionException("Missing $requiredPermission permission on share");
        }

        return $share;
    }

    /**
     * Check if the session user has the given permission to the Share.
     * The $requiredPermission must be one of 'owner' | 'write' | 'read'.
     * This is a simplified permission system, where a higher permission level implies also having the lower levels:
     * 'owner' implies also having 'write' and 'read'
     * 'write' implies also having 'read'
     */
    public function hasSharePermission(
        Share $share,
        string $requiredPermission,
        User | NotSet | null $user = new NotSet
    ): bool {
        if (!in_array($requiredPermission, ['owner', 'write', 'read'])) {
            throw new InvalidArgumentException('Invalid value for $requiredPermission');
        }

        if ($user instanceof NotSet) {
            $user = auth()->user();
        }

        if (!$user) {
            return false;
        }

        if ($share->owner_id === $user->id) {
            // owner always has full permissions
            return true;
        }

        // $access is 'write' | 'read'
        $permission = $share
            ->userAccess()
            ->where('user_id', $user->id)
            ->first()
            ?->user_access
            ->permission;

        if (!$permission) {
            return false;
        }
        else if ($requiredPermission === 'read') {
            // write access implies read access
            return $permission === 'write' || $permission === 'read';
        }
        else if ($requiredPermission === 'write') {
            return $permission === 'write';
        }
        else {
            return false;
        }
    }

    public function shareLogin(Share $share, string $password) {
        $throttleKey = $share->id . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw new ShareLoginRateLimited($seconds, 'Too many login attempts');
        }

        if (!$share->password || !Hash::check($password, $share->password)) {
            RateLimiter::hit($throttleKey);

            throw new ShareInvalidPasswordException('Invalid password for share');
        }

        // store the password hash so that if the share owner changes the password,
        // the existing sessions are no longer valid
        session(["share-password.$share->id" => $share->password]);
    }
}
