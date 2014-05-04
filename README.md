Laravel Rollbar
===============

[![Build Status](https://travis-ci.org/jenssegers/Laravel-Rollbar.svg)](https://travis-ci.org/jenssegers/Laravel-Rollbar) [![Coverage Status](https://coveralls.io/repos/jenssegers/Laravel-Rollbar/badge.png)](https://coveralls.io/r/jenssegers/Laravel-Rollbar)

Rollbar error monitoring integration for Laravel projects. This library will add a listener to Laravel's logging component. All Rollbar messages will be pushed onto Laravel's queue system, so that they can be processed in the background without slowing down the application. Laravel's session data will also be sent to Rollbar.

![rollbar](https://d37gvrvc0wt4s1.cloudfront.net/static/img/features-dashboard1.png?ts=1361907905)

Installation
------------

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "jenssegers/rollbar": "*"
        }
    }

Add the service provider in `app/config/app.php`:

    'Jenssegers\Rollbar\RollbarServiceProvider',

Configuration
-------------

Publish the included configuration file:

    php artisan config:publish jenssegers/rollbar

And change your rollbar access token:

    'access_token' => '',

Because this library uses the queue system, make sure your `config/queue.php` file is configured correctly. If you do not wish to process the jobs in the background, you can set the queue driver to 'sync':

    'default' => 'sync',

Usage
-----

This library adds a listener to Laravel's logging system. To monitor exceptions, simply use the `Log` facade:

    App::error(function(Exception $exception, $code)
    {
        Log::error($exception);
    });

Your other log messages will also be sent to Sentry:

    Log::info('Here is some debug information', array('context'));
