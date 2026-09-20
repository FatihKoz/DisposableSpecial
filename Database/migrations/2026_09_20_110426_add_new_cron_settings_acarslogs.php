<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Acars Log cleanup
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key'        => 'dspecial.old_acars_logs',
                ],
                [
                    'group'      => 'Cron',
                    'name'       => 'Delete old Acars Log Entries (days)',
                    'field_type' => 'numeric',
                    'default'    => '0',
                    'order'      => '2004',
                ]
            );
        }
    }
};
