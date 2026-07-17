<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$password = Hash::make('guru1234');

$users = User::whereHas('roles', function ($query) {
    $query->whereIn('name', ['guru', 'guru_walikelas', 'petugas_piket']);
})->get();

$count = 0;
foreach ($users as $user) {
    $user->password = $password;
    $user->save();
    $count++;
}

echo "Successfully updated passwords for {$count} teachers." . PHP_EOL;
