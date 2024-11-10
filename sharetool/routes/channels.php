<?php

use App\Models\Share;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('shares.{id}', function (User $user, string $id) {
    return Share::where('id', $id)->whereUserHasAccess($user)->exists();
});
