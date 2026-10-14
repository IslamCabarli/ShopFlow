<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PaymentProviderInterface;
use App\Services\Payments\FakePaymentProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProviderInterface::class, FakePaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throws an exception whenever a relation is lazy-loaded across a
        // multi-model result set (the N+1 pattern). Disabled in production.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
