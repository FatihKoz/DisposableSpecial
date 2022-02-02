<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegisterMessageSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Discord New User Register
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.discord_registermsg'], ['group' => 'Discord', 'name' => 'User Registered Messages', 'field_type' => 'check', 'default' => 'false']);
        }
    }
}
