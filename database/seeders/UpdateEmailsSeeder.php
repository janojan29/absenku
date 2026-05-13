<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateEmailsSeeder extends Seeder
{
    public function run(): void
    {
        $affected = DB::update("UPDATE users SET email = REPLACE(email, '@sekolah.local', '@gmail.com') WHERE email LIKE '%@sekolah.local'");
        $this->command->info("Success! {$affected} user emails have been updated from @sekolah.local to @gmail.com");
    }
}
