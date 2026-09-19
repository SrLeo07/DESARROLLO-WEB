<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();

        Passport::tokensExpireIn(CarbonInterval::hours(1));
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::months(6));

        Passport::tokensCan([
            'productos.read' => 'Consultar productos',
            'productos.write' => 'Crear y editar productos',
            'productos.delete' => 'Eliminar productos',
            'usuarios.read' => 'Consultar usuarios',
            'admin' => 'Acceso administrativo',
            'reportes' => 'Consultar reportes',
        ]);

        Passport::defaultScopes([
            'productos.read',
        ]);
    }
}
