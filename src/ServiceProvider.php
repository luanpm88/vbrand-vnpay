<?php

namespace Acelle\Vnpay;

use Illuminate\Support\ServiceProvider as Base;
use Acelle\Library\Facades\Hook;
use Acelle\Library\Facades\Billing;
use Acelle\Vnpay\Vnpay;

class ServiceProvider extends Base
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Register views path
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vnpay');

        // Register routes file
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // Register translation file
        $this->loadTranslationsFrom(storage_path('app/data/plugins/acelle/vnpay/lang/'), 'vnpay');

        // Register the translation file against Acelle translation management
        Hook::register('add_translation_file', function() {
            return [
                "id" => '#acelle/vnpay_translation_file',
                "plugin_name" => "acelle/vnpay",
                "file_title" => "Translation for acelle/vnpay plugin",
                "translation_folder" => storage_path('app/data/plugins/acelle/vnpay/lang/'),
                "file_name" => "messages.php",
                "master_translation_file" => realpath(__DIR__.'/../resources/lang/en/messages.php'),
            ];
        });

        // register payment
        $vnpay = Vnpay::initialize();
        Billing::register($vnpay->gateway->getType(), function() use ($vnpay) {
            return $vnpay->gateway;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
