<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\Share;
use App\Models\User;
use App\ShareAccessDenyReason;
use App\SharePermission;
use Illuminate\Auth\Access\Response;

class SharePolicy
{
    /**
     * Determine if the user/public token is allowed to view the login screen of a share.
     * This means that the user has enough access to the share, except for the share password.
     */
    public function viewShareLogin(?User $user, Share $share, ?string $publicToken = null): Response
    {
        if ($user) {
            $permission = $share->getUserPermission($user);

            if (
                $permission === SharePermission::Owner ||
                $permission === SharePermission::Read ||
                $permission === SharePermission::Write
            ) {
                return Response::allow();
            }
        }
        else if ($publicToken) {
            return hash_equals($share->public_token, $publicToken)
                ? Response::allow()
                : Response::deny('The public token is incorrect', ShareAccessDenyReason::PublicTokenIncorrect);
        }

        return Response::deny('You do not have access to this Share.', ShareAccessDenyReason::MissingCredentials);
    }

    /**
     * Determine if the user can view details of the share and its list of files.
     */
    public function view(?User $user, Share $share, ?string $publicToken = null): Response
    {
        $response = $this->viewShareLogin($user, $share, $publicToken);
        if ($response->denied()) {
            return $response;
        }

        $isOwner = $user && $share->owner_id === $user->id;

        if ($share->password && !$isOwner) {
            $sessionPasswordHash = session("share-password.$share->id");

            if (!$sessionPasswordHash) {
                return Response::deny('This share requires a password', ShareAccessDenyReason::InvalidSharePassword);
            }

            return hash_equals($share->password, $sessionPasswordHash)
                ? Response::allow()
                : Response::deny('The entered password for the share has expired', ShareAccessDenyReason::InvalidSharePassword);
        }

        return $response;
    }

    /**
     * Determine if the user can view the audit log of the share
     * Only the owner can view the audit log.
     */
    public function viewAudit(User $user, Share $share): Response
    {
        $permission = $share->getUserPermission($user);

        return $permission === SharePermission::Owner
            ? Response::allow()
            : Response::deny('You do not own this Share.', ShareAccessDenyReason::MissingPermission);
    }

    /**
     * Determine if a share may be created.
     * All registered users may create shares
     */
    public function create(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine if the user may create files within the given share
     */
    public function createFile(User $user, Share $share): Response
    {
        $permission = $share->getUserPermission($user);

        return $permission === SharePermission::Owner || $permission === SharePermission::Write
            ? Response::allow()
            : Response::deny('You do not have write access to the Share.', ShareAccessDenyReason::MissingPermission);
    }

    /**
     * Determine if the given Share can be updated by the user.
     * Only the owner can update the Share itself.
     * The creation/modification of file within the Share is governed by a different policy
     */
    public function update(User $user, Share $share): Response
    {
        $permission = $share->getUserPermission($user);

        return $permission === SharePermission::Owner
            ? Response::allow()
            : Response::deny('You do not own this Share.', ShareAccessDenyReason::MissingPermission);
    }

    /**
     * Determine if the user can grant permissions for this share to other users.
     * Only the owner can manager permissions of a Share.
     */
    public function updateAccess(User $user, Share $share): Response
    {
        $permission = $share->getUserPermission($user);

        return $permission === SharePermission::Owner
            ? Response::allow()
            : Response::deny('You do not own this Share.', ShareAccessDenyReason::MissingPermission);
    }

    /**
     * Determine if the given Share can be deleted by the user.
     * Only the owner can delete a share.
     */
    public function delete(User $user, Share $share): Response
    {
        $permission = $share->getUserPermission($user);

        return $permission === SharePermission::Owner
            ? Response::allow()
            : Response::deny('You do not own this Share.', ShareAccessDenyReason::MissingPermission);
    }
}
