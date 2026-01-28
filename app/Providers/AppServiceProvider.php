<?php

namespace App\Providers;

use App\Model\BusinessSetting;
use App\Traits\SystemAddonTrait;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    use SystemAddonTrait;
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ✅ Ensure global constants (e.g. MANAGEMENT_SECTION, TELEPHONE_CODES) are loaded
        // even when Laravel config is cached (bootstrap/cache/config.php).
        // Without this, views/middlewares that reference these constants will crash.
        if (!defined('MANAGEMENT_SECTION') && file_exists(config_path('constant.php'))) {
            require_once config_path('constant.php');
        }

        //for system addon
        Config::set('addon_admin_routes',$this->get_addon_admin_routes());
        Config::set('get_payment_publish_status',$this->get_payment_publish_status());

        try {
            $timezone = BusinessSetting::where(['key' => 'time_zone'])->first();
            if (isset($timezone)) {
                config(['app.timezone' => $timezone->value]);
                date_default_timezone_set($timezone->value);
            }
        }catch(\Exception $exception){}
        Paginator::useBootstrap();

    }
}
