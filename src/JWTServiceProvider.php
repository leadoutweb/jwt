<?php

namespace Leadout\JWT;

use Illuminate\Support\ServiceProvider;
use Leadout\JWT\Blacklists\Drivers\Cache;
use Leadout\JWT\TokenProviders\Drivers\Firebase;

class JWTServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
            $guard = new JWTGuard(
                $name,
                $app['auth']->createUserProvider($config['provider']),
                new TokenManager(
                    new Firebase($config),
                    new Cache($app['cache.store']),
                    $name,
                    $config
                ),
                $app['events'],
                $app['request'],
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
