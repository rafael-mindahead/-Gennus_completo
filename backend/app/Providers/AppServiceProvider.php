<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
    public function boot(): void
    {
        Gate::define('produtos.visualizar', fn(User $user): bool => in_array($user->perfil,['leitor', 'operador', 'administrador'], true));
        Gate::define('produtos.cadastrar', fn(User $user): bool => in_array($user->perfil,['operador', 'administrador'], true));
        Gate::define('produtos.excluir', fn(User $user): bool => in_array($user->perfil === 'administrador'));
    }
}
