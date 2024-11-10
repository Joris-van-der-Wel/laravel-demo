<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use App\ShareAccessDenyReason;
use App\SharePermission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    // the create policy is found in SharePolicy

    /**
     * Determine if the user can view details of the share and its list of files.
     */
    public function view(?User $user, File $file, ?string $publicToken = null): Response
    {
        return Gate::forUser($user)->inspect('view', [$file->share, $publicToken]);
    }

    /**
     * Determine if the user may delete the given file
     */
    public function delete(User $user, File $file): Response
    {
        $permission = $file->share->getUserPermission($user);

        return $permission === SharePermission::Owner || $permission === SharePermission::Write
            ? Response::allow()
            : Response::deny('You do not have write access to the Share.',ShareAccessDenyReason::MissingPermission);
    }
}
