<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandleDisposableTours extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('turksim_tours') && !Schema::hasTable('disposable_tours')) {
            Schema::create('turksim_tours', function (Blueprint $table) {
                $table->increments('id');
                $table->string('tour_name', 150);
                $table->string('tour_code', 5);
                $table->text('tour_desc')->nullable();
                $table->text('tour_rules')->nullable();
                $table->integer('tour_airline')->nullable()->default(0);
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('active');
                $table->timestamps();
                $table->index('id');
                $table->unique('id');
            });
        }

        if (Schema::hasTable('turksim_tours') && !Schema::hasTable('disposable_tours') && !Schema::hasColumn('turksim_tours', 'tour_airline')) {
            Schema::table('turksim_tours', function (Blueprint $table) {
                $table->integer('tour_airline')->nullable()->default(0)->after('tour_desc');
            });
        }

        if (Schema::hasTable('turksim_tours') && !Schema::hasTable('disposable_tours')) {
            Schema::table('turksim_tours', function (Blueprint $table) {
                $table->text('tour_desc')->change();
            });
        }

        if (Schema::hasTable('turksim_tours') && !Schema::hasTable('disposable_tours') && !Schema::hasColumn('turksim_tours', 'tour_rules')) {
            Schema::table('turksim_tours', function (Blueprint $table) {
                $table->text('tour_rules')->nullable()->after('tour_desc');
            });
        }

        if (Schema::hasTable('turksim_tours') && !Schema::hasTable('disposable_tours')) {
            Schema::table('turksim_tours', function (Blueprint $table) {
                $table->dropIndex(['id']);
                $table->dropUnique(['id']);
            });

            Schema::rename('turksim_tours', 'disposable_tours');

            Schema::table('disposable_tours', function (Blueprint $table) {
                $table->index('id');
                $table->unique('id');
            });
        }
    }
}
