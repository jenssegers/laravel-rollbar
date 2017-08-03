Laravel Rollbar
===============

[![Build Status](https://travis-ci.org/rollbar/rollbar-php-laravel.svg?branch=master)](https://travis-ci.org/rollbar/rollbar-laravel) 

Rollbar error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Rollbar, as well as some other helpful information such as 'environment', 'server', and 'session'.

![rollbar](https://d37gvrvc0wt4s1.cloudfront.net/static/img/features-dashboard1.png?ts=1361907905)

Installation
------------

Install using composer:

```
composer require rollbar/rollbar-laravel
```

Add Project Access Token `post_server_item` from Rollbar.com -> Settings -> Project Access Tokens to .env:

```
ROLLBAR_TOKEN=[your Rollbar project access token]
```

Add the service provider to the `'providers'` array in `config/app.php`:

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

Configuration
-------------

Setting up `ROLLBAR_TOKEN` in .env should be enough for basic configuration.

This package supports configuration through the services configuration file located in `config/services.php`. All configuration variables will be directly passed to Rollbar:

```php
'rollbar' => [
    'access_token' => env('ROLLBAR_TOKEN'),
    'level' => env('ROLLBAR_LEVEL'),
],
```

The level variable defines the minimum log level at which log messages are sent to Rollbar. For development you could set this either to `debug` to send all log messages, or to `none` to sent no messages at all. For production you could set this to `error` so that all info and debug messages are ignored.

Usage
-----

To automatically monitor exceptions, simply use the `Log` facade in your error handler in `app/Exceptions/Handler.php`:

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

Your other log messages will also be sent to Rollbar:

```php
\Log::debug('Here is some debug information');
```

*NOTE*: Fatal exceptions will always be sent to Rollbar.

### Context informaton

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
