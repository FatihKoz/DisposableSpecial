<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandleDisposableSettings extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('disposable_settings')) {
            Schema::create('disposable_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 200)->nullable();
                $table->string('key', 100);
                $table->string('value', 500)->nullable();
                $table->string('group', 100)->nullable();
                $table->timestamps();
                $table->index('id');
                $table->unique('id');
                $table->unique('key');
            });
        }

        if (Schema::hasTable('disposable_settings') && !Schema::hasColumn('disposable_settings', 'field_type')) {
            Schema::table('disposable_settings', function (Blueprint $table) {
                $table->string('default', 250)->nullable()->after('value');
                $table->string('field_type', 50)->nullable()->after('group');
                $table->text('options')->nullable()->after('field_type');
                $table->string('desc', 250)->nullable()->after('options');
                $table->string('order', 6)->nullable()->after('desc');
            });
        }

        if (Schema::hasTable('disposable_settings')) {
            // Discord Webhook & Diversions
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.discord_divertmsg'], ['group' => 'Discord', 'name' => 'Send Divert Messages', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.discord_divert_msgposter'], ['group' => 'Discord', 'name' => 'Message Sender']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.discord_divert_webhook'], ['group' => 'Discord', 'name' => 'Webhook URL']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.pireps_handle_diversions'], ['group' => 'Discord', 'name' => 'Auto Handle Diversions (Move Assets)', 'field_type' => 'check', 'value' => 'true', 'default' => 'true']);
            // Random Flights Reward
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.randomflights_reward'], ['group' => 'Random Flights', 'name' => 'Reward Random Flights', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.randomflights_multiplier'], ['group' => 'Random Flights', 'name' => 'RF Multiplier', 'field_type' => 'numeric', 'default' => '10']);
            // Expenses - Enroute
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_atcmethod'], ['group' => 'Enroute', 'name' => 'Air Traffic Fee', 'field_type' => 'select', 'options' => 'disabled,tow,mtow', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_atcbase'], ['group' => 'Enroute', 'name' => 'Air Traffic Unit Rate', 'field_type' => 'decimal', 'default' => '2.586']);
            // Expenses - Landing Fee
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_lfmethod'], ['group' => 'Landing Fee', 'name' => 'Landing Fee', 'field_type' => 'select', 'options' => 'disabled,lw,mtow', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_lfbase'], ['group' => 'Landing Fee', 'name' => 'LF Unit Rate', 'field_type' => 'decimal', 'default' => '4.44']);
            // Expenses - Parking Fee
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_pfmethod'], ['group' => 'Parking Fee', 'name' => 'Parking Fee', 'field_type' => 'select', 'options' => 'disabled,lw,mtow', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_pfbase'], ['group' => 'Parking Fee', 'name' => 'PF Unit Rate', 'field_type' => 'decimal', 'default' => '0.55']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_pfmaxd'], ['group' => 'Parking Fee', 'name' => 'PF Max Charge Days', 'field_type' => 'numeric', 'default' => '14']);
            // Expenses - Terminal Services Fee
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_tfmethod'], ['group' => 'Terminal Services', 'name' => 'Terminal Services Fee', 'field_type' => 'select', 'options' => 'disabled,load,cap', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_tfpaxbase'], ['group' => 'Terminal Services', 'name' => 'TF Unit Rate (PAX)', 'field_type' => 'decimal', 'default' => '3.5']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_tfcgobase'], ['group' => 'Terminal Services', 'name' => 'TF Unit Rate (CGO)', 'field_type' => 'decimal', 'default' => '0.0125']);
            // Expenses - Airport Authority Fee
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_aamethod'], ['group' => 'Airport Authority', 'name' => 'Airport Authority Fee', 'field_type' => 'select', 'options' => 'disabled,load,cap', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_aapaxbase'], ['group' => 'Airport Authority', 'name' => 'AA Unit Rate (PAX)', 'field_type' => 'decimal', 'default' => '0.64']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_aacgobase'], ['group' => 'Airport Authority', 'name' => 'AA Unit Rate (CGO)', 'field_type' => 'decimal', 'default' => '1.2']);
            // Expenses - Ground Handling Fee
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_ghmethod'], ['group' => 'Ground Handling', 'name' => 'Ground Handling Fee', 'field_type' => 'select', 'options' => 'disabled,load,cap', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_ghpaxbase'], ['group' => 'Ground Handling', 'name' => 'GH Unit Rate (PAX)', 'field_type' => 'decimal', 'default' => '4.28']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_ghcgobase'], ['group' => 'Ground Handling', 'name' => 'GH Unit Rate (CGO)', 'field_type' => 'decimal', 'default' => '6.31']);
            // Expenses - Catering Services Fee
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_ctmethod'], ['group' => 'Catering', 'name' => 'Catering Services Fee', 'field_type' => 'select', 'options' => 'disabled,load,cap', 'default' => 'disabled']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_ctpaxbase'], ['group' => 'Catering', 'name' => 'CT Unit Rate (PAX)', 'field_type' => 'decimal', 'default' => '4.98']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_ctsrvbase'], ['group' => 'Catering', 'name' => 'CT Unit Rate (Service)', 'field_type' => 'decimal', 'default' => '0.06']);
            // Expenses - Fuel
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_srv'], ['group' => 'Fuel Services', 'name' => 'Fuel Service Fee', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_drn'], ['group' => 'Fuel Services', 'name' => 'Fuel Draining Fee', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_low'], ['group' => 'Fuel Services', 'name' => 'Low Uplift Fee', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_tax'], ['group' => 'Fuel Services', 'name' => 'Domestic Fuel Tax', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_margin'], ['group' => 'Fuel Services', 'name' => 'Fuel Expenses Margin', 'field_type' => 'numeric', 'default' => '220']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_lowcost'], ['group' => 'Fuel Services', 'name' => 'Low Uplift Charge', 'field_type' => 'numeric', 'default' => '250']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_lowlimit'], ['group' => 'Fuel Services', 'name' => 'Low Uplift Margin (lbs)', 'field_type' => 'numeric', 'default' => '1770']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_srvcost'], ['group' => 'Fuel Services', 'name' => 'Fuel Service Unit Rate', 'field_type' => 'decimal', 'default' => '0.01']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_drncost'], ['group' => 'Fuel Services', 'name' => 'Fuel Draining Unit Rate', 'field_type' => 'decimal', 'default' => '0.05']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_fuel_domtax'], ['group' => 'Fuel Services', 'name' => 'Domestic Fuel Tax (%)', 'field_type' => 'numeric', 'default' => '7']);
            // Expenses - Maintenance
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndhard'], ['group' => 'Maintenance', 'name' => 'Hard Landing Check', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndsoft'], ['group' => 'Maintenance', 'name' => 'Soft Landing Check', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lnduser'], ['group' => 'Maintenance', 'name' => 'Charge Pilot For Checks', 'field_type' => 'check', 'default' => 'false']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndhard_limit'], ['group' => 'Maintenance', 'name' => 'Hard Landing Limit', 'field_type' => 'numeric', 'default' => '500']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndhard_cost'], ['group' => 'Maintenance', 'name' => 'HL Maintenance Fee', 'field_type' => 'numeric', 'default' => '1000']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndsoft_limit'], ['group' => 'Maintenance', 'name' => 'Soft Landing Limit', 'field_type' => 'numeric', 'default' => '50']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndsoft_cost'], ['group' => 'Maintenance', 'name' => 'SL Maintenance Fee', 'field_type' => 'numeric', 'default' => '500']);
        }

        if (Schema::hasTable('disposable_settings')) {
            // TurkSim InFlight Sales
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.income_dfmethod'], ['group' => 'Income', 'name' => 'Duty Free Sales', 'field_type' => 'select', 'default' => 'disabled', 'options' => 'disabled,int,all', 'order' => '8010']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.income_dfprices'], ['group' => 'Income', 'name' => 'DF Item Prices', 'default' => '5,10,15,20,25,30,35,40,45,50,55,60,65,70', 'order' => '8011']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.income_dfprofit'], ['group' => 'Income', 'name' => 'DF Profit % per item', 'default' => '35', 'field_type' => 'numeric', 'order' => '8012']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.income_bsmethod'], ['group' => 'Income', 'name' => 'Cabin Bouffet Sales', 'field_type' => 'select', 'default' => 'disabled', 'options' => 'disabled,int,dom,all', 'order' => '8020']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.income_bsprices'], ['group' => 'Income', 'name' => 'CB Item Prices', 'default' => '1.5,2,2.5,3,4,5,6,7,8,9,10,11,14,18,20', 'order' => '8021']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.income_bsprofit'], ['group' => 'Income', 'name' => 'CB Profit % per item', 'default' => '45', 'field_type' => 'numeric', 'order' => '8022']);
        }
    }
}
