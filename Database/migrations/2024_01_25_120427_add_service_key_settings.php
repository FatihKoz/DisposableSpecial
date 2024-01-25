<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Settings for API Service Key (Common with Disposable Basic Module)
            DB::table('disposable_settings')->updateOrInsert(
                ['key' => 'dbasic.srvkey'],
                ['group' => 'API Service', 'name' => 'Service Key (API)', 'field_type' => 'text', 'default' => null, 'order' => '9901']
            );
        }
    }
};
