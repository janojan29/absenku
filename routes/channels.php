<?php

use Illuminate\Support\Facades\Broadcast;

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('classroom.{classRoomId}', function ($user, $classRoomId) {
    return method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['guru', 'guru_walikelas', 'petugas_piket', 'admin']);
});

Broadcast::channel('leave-requests', function ($user) {
    return method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['petugas_piket', 'admin']);
});
