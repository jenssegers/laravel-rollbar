Laravel Rollbar
===============

[![Build Status](http://img.shields.io/travis/jenssegers/laravel-rollbar.svg)](https://travis-ci.org/jenssegers/laravel-rollbar) [![Coverage Status](http://img.shields.io/coveralls/jenssegers/laravel-rollbar.svg)](https://coveralls.io/r/jenssegers/laravel-rollbar)

Rollbar error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Rollbar, as well as some other helpful information such as 'environment', 'server', and 'session'.

![rollbar](https://d37gvrvc0wt4s1.cloudfront.net/static/img/features-dashboard1.png?ts=1361907905)

Installation
------------

Install using composer:

```
composer require jenssegers/rollbar
```

Add the service provider to the `'providers'` array in `config/app.php`:

```php
Jenssegers\Rollbar\RollbarServiceProvider::class,
```
    
If you only want to enable Rollbar reporting for certain environments you can conditionally load the service provider in your `AppServiceProvider`:

```php
    public function register()
    {
        if ($this->app->environment('production')) {
            $this->app->register(\Jenssegers\Rollbar\RollbarServiceProvider::class);
        }
    }
```

Configuration
-------------

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
    \Log::error($exception); //rollbar
    parent::report($exception);
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

Rollbar allows you to send optional, user-specific data along with each instance of an error. If you choose to send that data, Rollbar requires an ID, and also permits optional `username` and `email` values. All values must be passed as trings.

You can pass user information as context like this:

```php
use Auth;

public function report(Exception $exception)
{
    if (Auth::check()) { // Ensure your user is logged in.
        $user = Auth::User(); // Store user as a variable for improved legibility
        if ($this->shouldReport($exception)) { // Ensure exception should be passed
            \Log::error($exception->getMessage(), [ // Pass the specific exception message
                'person' => [ //Pass your user details
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email
                ]
            ]);
        }
    }
    parent::report($exception); // If no authenticated user, report the exception anonymously as usual.
}
```
The example above uses the `shouldReport()` function, which is defined in the parent `Illuminate\Foundation\Exceptions\Handler` class, and checks that the exception cannot be found within the `$dontReport` array at the top of your `App\Exceptions\Handler` class. Otherwise, Rollbar will log *every* exception your app throws, including validation errors, TokenMismatchExceptions, and other less useful errors.

If you're using Laravel's Auth facade, don't forget to import it by including `use Auth;` at the top of your `App\Exceptions\Handler` class.

Rollbar also accepts custom values:

```php
\Log::warning('Something went wrong', [
    'download_size' => 3432425235
]);
```
