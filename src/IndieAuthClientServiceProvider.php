<?php

namespace Inklings\IndieAuth;

use Illuminate\Support\ServiceProvider;
use App;

class IndieauthClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'indieauthclient');

        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/indieauthclient')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
 
