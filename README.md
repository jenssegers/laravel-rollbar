# Laravel Rollbar
=================

[![Build Status](https://travis-ci.org/rollbar/rollbar-php-laravel.svg?branch=master)](https://travis-ci.org/rollbar/rollbar-laravel) 

Rollbar error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Rollbar, as well as some other helpful information such as 'environment', 'server', and 'session'.

![rollbar](https://d37gvrvc0wt4s1.cloudfront.net/static/img/features-dashboard1.png?ts=1361907905)

## Installation
---------------

Install using composer:

```
composer require rollbar/rollbar-laravel
```

Add Project Access Token `post_server_item` from Rollbar.com -> Settings -> Project Access Tokens to `.env`:

```
ROLLBAR_TOKEN=[your Rollbar project access token]
```

Add the service provider to the `'providers'` array in `config/app.php` (this package also supports Laravel 5.5's auto-discovery, which allows you to skip this step):

```php
Rollbar\Laravel\RollbarServiceProvider::class,
```

If you only want to enable Rollbar reporting for certain environments you can conditionally load the service provider in your `AppServiceProvider`:

```php
public function register()
{
    if ($this->app->environment('production')) {
        $this->app->register(\Rollbar\Laravel\RollbarServiceProvider::class);
    }
}
```

## Configuration
----------------

Setting up `ROLLBAR_TOKEN` in .env should be enough for basic configuration.

This package supports configuration through the services configuration file located in `config/services.php`. All rollbar configuration variables will be directly passed to Rollbar:

```php
'rollbar' => [
    'access_token' => env('ROLLBAR_TOKEN'),
    'level' => env('ROLLBAR_LEVEL'),
],
```

The level variable defines the minimum log level at which log messages are sent to Rollbar. If not specified, the default is `debug`. For development you could set this either to `debug` to send `all` log messages, or to `none` to send no messages at all. For production you could set this to `error` so that all `info` and `debug` messages are ignored.

## Usage
--------

This package will automatically send to Rollbar every logged message whose level is higher than the ROLLBAR_LEVEL you have configured.

### Logging a Specific Message

You can log your own messages anywhere in your app. For example, to log a `debug` message:

```php
\Log::debug('Here is some debug information');
```


### Adding Context Informaton

You can pass user information as context like this:

```php
\Log::error('Something went wrong', [
    'person' => ['id' =>(string) 123, 'username' => 'John Doe', 'email' => 'john@doe.com']
]);
```

Or pass some extra information:

```php
\Log::warning('Something went wrong', [
    'download_size' => 3432425235
]);
```

### Exception Logging
---------------------
*NOTE*: Fatal exceptions will always be sent to Rollbar.

Any exceptions that are not listed as `$dontReport` in your `app/Exceptions/Handler.php` or its parent will be sent to Rollbar automatically.

If you wish to override this to do more Rollbar reporting, you may do so using the `Log` facade in your error handler in `app/Exceptions/Handler.php`. For example, to log *every* exception add the following:

```php
public function report(Exception $exception)
{
    \Log::error($exception);
    return parent::report($exception);
}
```

For Laravel 4 installations, this is located in `app/start/global.php`:

```php
App::error(function(Exception $exception, $code)
{
    Log::error($exception);
});
```
