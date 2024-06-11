<?php

namespace Ashraf\LaravelTapPayment;

use Illuminate\Support\ServiceProvider;

class TapServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/tap_payment.php' => config_path('tap_payment.php'),
        ], 'tap_payment-config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/tap_payment.php', 'tap_payment'
        );

        $this->registerBindings();
    }

    /**
     * Registers app bindings and aliases.
     */
    protected function registerBindings()
    {
        $this->app->singleton(Payment::class, function ($app) {
            return new Payment($app['config']['tap_payment']);
        });

        $this->app->alias(Payment::class, 'Payment');
    }
}
