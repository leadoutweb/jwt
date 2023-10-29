# leadout-jwt

![run-tests workflow](https://github.com/leadoutweb/jwt/actions/workflows/run-tests.yml/badge.svg)

A lightweight JWT guard for Laravel.

## Installation

Install the package through composer:

``` bash
composer require leadout/jwt
```

The package will automatically register the authentication guard.

## Configuration

Configuring the package takes place solely through the guard configuration in Laravel's native `auth.php` configuration file.

The following example shows all the configuration that can be passed to the guard:

```php
<?php

return [
    // ...
    
    'guards' => [
        'my-guard' => [
            'driver' => 'jwt',
            'provider' => 'users',
            'key' => 'secret-key',
            'public_key' => 'path_to_public_key_file',
            'private_key' => 'path_to_private_key_file',
            'ttl' => 60,
            'claims' => [
                'key' => 'value'
            ]
        ]
    ]
    
    // ...
];

```

The `provider` should be defined in the `providers` array in the authentication configuration file.

The `key` is used for encoding and decoding the tokens and should be kept secret.

If not using a single `key` for both encoding and decoding, the `public_key` and `private_key` should point to the public and private keys that are used for encoding and decoding the tokens.

The `ttl` value is the time-to-live for the token in minutes. This field can be omitted from the config. If omitted, the default time-to-live for tokens is set to 60 minutes.

Additional claims that should be added to all tokens issued through the guard should be added to the `claims` configuration. This configuration key can be omitted.

## Usage

Since the guard is purely configured through Laravel's native auth configuration, it is recommended only instantiating it through the auth manager:

```
$guard = auth()->guard('my-guard');
```

### Issuing a token

```
$token = auth()->guard('my-guard')->issue(User::first());
```

When a token has been issued, it should be returned to the user. The user should then pass the token to the application as a bearer token in the Authorization header like so:

```
Authorization: Bearer ey...
```

### Refreshing the token in the request

```
$token = auth()->guard('my-guard')->refresh();
```

The refreshed token will have all the claims from the original token and a fresh time-to-live.

### Invalidating the token in the request

```
auth()->guard('my-guard')->invalidate();
```

When the token has been invalidated, all subsequent requests with the same token will fail.

### Adding claims from the user

In addition to adding custom claims through the guard configuration, it is possible to add claims to the token from the user itself.

Simply add a `getClaims()` method to the user model and return an array of claims:

```
<?php

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function getClaims() : array
    {
        return [
            'key' => 'value'
        ];
    }
}
```