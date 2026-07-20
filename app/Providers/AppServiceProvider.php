<?php

namespace App\Providers;

use App\Contracts\FiscalIssuerInterface;
use App\Services\Fiscal\RawNativeNfceIssuer;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FiscalIssuerInterface::class, function () {
            $driver = config('nfce.driver', 'raw_native');

            return match ($driver) {
                'raw_native' => new RawNativeNfceIssuer,
                default => throw new InvalidArgumentException("Driver NFC-e desconhecido: {$driver}"),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
