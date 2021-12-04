<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandleDisposableNotams extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('turksim_notams') && !Schema::hasTable('disposable_notams')) {
            Schema::create('turksim_notams', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 250)->nullable();
                $table->text('body')->nullable();
                $table->date('eff_start')->nullable();
                $table->string('eff_stime', 4)->nullable();
                $table->date('eff_end')->nullable();
                $table->string('eff_etime', 4)->nullable();
                $table->string('ref_airport', 5)->nullable();
                $table->integer('ref_airline')->nullable();
                $table->integer('ref_notamid')->nullable();
                $table->boolean('active')->nullable();
                $table->timestamps();
                $table->index('id');
                $table->unique('id');
            });
        }

        if (Schema::hasTable('turksim_notams') && !Schema::hasTable('disposable_notams')) {
            Schema::table('turksim_notams', function (Blueprint $table) {
                $table->dropIndex(['id']);
                $table->dropUnique(['id']);
            });

            Schema::rename('turksim_notams', 'disposable_notams');

            Schema::table('disposable_notams', function (Blueprint $table) {
                $table->index('id');
                $table->unique('id');
            });
        }
    }
}
