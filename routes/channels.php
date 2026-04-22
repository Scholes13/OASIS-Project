<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.Core.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
