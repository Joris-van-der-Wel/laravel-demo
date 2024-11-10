<?php
declare(strict_types=1);

namespace App\Builders;

use App\Models\User;
use App\NotSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShareBuilder extends Builder
{
    /**
     * Select all shares where the given user has at least permission to read it.
     */
    public function whereUserHasAccess(User | NotSet | null $user = new NotSet): Builder {
        if ($user instanceof NotSet) {
            $user = auth()->user();
        }

        if (!$user) {
            return $this->where('id', null);
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
