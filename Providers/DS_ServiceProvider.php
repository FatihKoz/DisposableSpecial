<?php

namespace Modules\DisposableSpecial\Providers;

use App\Services\ModuleService;
use Illuminate\Support\ServiceProvider;
use Route;

class DS_ServiceProvider extends ServiceProvider
{
    protected $moduleSvc;

    // Boot application events
    public function boot()
    {
        $this->moduleSvc = app(ModuleService::class);

        $this->registerRoutes();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerLinks();

        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        app('arrilot.widget-namespaces')->registerNamespace('DSpecial', 'Modules\DisposableSpecial\Widgets');
    }

    // Register service provider
    public function register()
    {
    }

    // Register module links
    public function registerLinks()
    {
        $this->moduleSvc->addAdminLink('Disposable Special', '/admin/dspecial', 'pe-7s-tools');
    }

    // Register routes
    protected function registerRoutes()
    {
        // Frontend Auth
        Route::group([
            'as'         => 'DSpecial.',
            'prefix'     => '',
            'middleware' => ['web', 'auth'],
            'namespace'  => 'Modules\DisposableSpecial\Http\Controllers',
        ], function () {
            // Assignment Controller Routes
            Route::get('dassignments', 'DS_AssignmentController@index')->name('assignments');
            Route::post('dassignments_manual', 'DS_AssignmentController@assignments_manual')->name('assignments_manual');
            // Free Flight Controller Routes
            Route::get('dfreeflight', 'DS_FreeFlightController@index')->name('freeflight');
            Route::match(['get', 'post'], 'dfreeflight_store', 'DS_FreeFlightController@store')->name('freeflight_store');
            // Maintenance Controller Routes
            Route::get('dmaintenance', 'DS_MaintenanceController@index')->name('maintenance');
            // Notam Controller Routes
            Route::get('dnotams', 'DS_NotamController@index')->name('notams');
            // Page Controller Routes
            Route::get('dopsmanual', 'DS_PageController@ops_manual')->name('ops_manual');
            Route::get('dlandingrates', 'DS_PageController@landing_rates')->name('landing_rates');
            // Tour Controller Routes
            Route::get('dtours', 'DS_TourController@index')->name('tours');
            Route::get('dtours/{code}', 'DS_TourController@show')->name('tour');
            // Event Controller Routes
            Route::get('devents', 'DS_EventController@index')->name('events');
            Route::get('devents/{code}', 'DS_EventController@show')->name('event');
        });

        // Frontend Public
        Route::group([
            'as'         => 'DSpecial.',
            'prefix'     => '',
            'middleware' => ['web'],
            'namespace'  => 'Modules\DisposableSpecial\Http\Controllers',
        ], function () {
            Route::get('daboutus', 'DS_PageController@about_us')->name('about_us');
            Route::get('drulesandregs', 'DS_PageController@rules_regs')->name('rules_regs');
        });

        // Admin
        Route::group([
            'as'         => 'DSpecial.',
            'prefix'     => 'admin',
            'middleware' => ['web', 'auth', 'role:admin'],
            'namespace'  => 'Modules\DisposableSpecial\Http\Controllers',
        ], function () {
            Route::get('dspecial', 'DS_AdminController@index')->name('admin');
            Route::post('dsettings_store', 'DS_AdminController@update')->name('save_settings');
            // Maintenance Admin Routes
            Route::get('dmaint_admin', 'DS_MaintenanceController@index_admin')->name('maint_admin');
            Route::post('dmaint_finish', 'DS_MaintenanceController@finish_maint')->name('maint_finish');
            // Notam Admin Routes
            Route::get('dnotam_admin', 'DS_NotamController@index_admin')->name('notam_admin');
            Route::post('dnotam_store', 'DS_NotamController@store')->name('notam_store');
            // Tour Admin Routes
            Route::get('dtour_admin', 'DS_TourController@index_admin')->name('tour_admin');
            Route::post('dtour_store', 'DS_TourController@store')->name('tour_store');
            // Event Admin Routes
            Route::get('devent_admin', 'DS_EventController@admin')->name('event_admin');
            Route::post('devent_store', 'DS_EventController@store')->name('event_store');
            Route::match([
                'get',
                'post',
                'put',
                'delete',
            ], 'devent_admin/{id}/users', 'DS_EventController@users');
        });
    }

    protected function registerConfig()
    {
        $this->publishes([__DIR__ . '/../Config/config.php' => config_path('DSpecial.php'),], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'DSpecial');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/DisposableSpecial');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'DSpecial');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'DSpecial');
        }
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/DisposableSpecial');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([$sourcePath => $viewPath,], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/DisposableSpecial';
        }, \Config::get('view.paths')), [$sourcePath]), 'DSpecial');
    }

    public function provides(): array
    {
        return [];
    }
}
