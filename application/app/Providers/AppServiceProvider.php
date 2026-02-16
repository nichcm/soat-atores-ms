<?php

namespace App\Providers;

use App\Domain\Entity\Usuario\RepositorioInterface as UsuarioRepository;
use App\Infrastructure\Service\JsonWebToken;
use App\Infrastructure\Repositories\UsuarioEloquentRepository;
use App\Infrastructure\Service\LaravelAuthService;
use App\Signature\AuthServiceInterface;
use App\Signature\TokenServiceInterface;
use Illuminate\Support\ServiceProvider;


use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepository;
use App\Infrastructure\Repositories\ClienteEloquentRepository;

use App\Domain\Entity\Veiculo\RepositorioInterface as VeiculoRepository;
use App\Infrastructure\Repositories\VeiculoEloquentRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // "usuario" repository binding
        $this->app->bind(
            UsuarioRepository::class,
            UsuarioEloquentRepository::class
        );

        // "cliente" repository binding
        $this->app->bind(
            ClienteRepository::class,
            ClienteEloquentRepository::class
        );

        // "veiculo" repository binding
        $this->app->bind(
            VeiculoRepository::class,
            VeiculoEloquentRepository::class
        );
        
        // "token" service binding
        $this->app->bind(
            TokenServiceInterface::class,
            JsonWebToken::class
        );

        // "auth" service binding
        $this->app->bind(
            AuthServiceInterface::class,
            LaravelAuthService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
