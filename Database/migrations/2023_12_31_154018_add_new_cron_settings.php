<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Paused Pirep Cleanup
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'dspecial.delete_paused_pireps'
                ],
                [
                    'group'      => 'Cron',
                    'name'       => 'Cancel Paused Pireps (hours)',
                    'field_type' => 'numeric',
                    'default'    => '0',
                    'order'      => '2006'
                ]
            );
        }
    }
};
