<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tickets.*', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ticketcreated', function ($user) {
    return true;
});
