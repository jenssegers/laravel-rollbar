# Rollbar for Laravel [![Build Status](https://travis-ci.org/rollbar/rollbar-php-laravel.svg?branch=master)](https://travis-ci.org/rollbar/rollbar-php-laravel)

Rollbar error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Rollbar, as well as some other helpful information such as 'environment', 'server', and 'session'.

## Setup Instructions

1. [Sign up for a Rollbar account](https://rollbar.com/signup)
2. Follow the [Installation](https://docs.rollbar.com/docs/laravel#section-installation) instructions in our [Laravel SDK docs](https://docs.rollbar.com/docs/laravel) to install rollbar-gem and configure it for your platform.

## Usage and Reference

For complete usage instructions and configuration reference, see our [Laravel SDK docs](https://docs.rollbar.com/docs/laravel).
  
## Release History & Changelog

See our [Releases](https://github.com/rollbar/rollbar-php-laravel/releases) page for a list of all releases, including changes.


## Related projects

This project is a Laravel wrapper of Rollbar PHP: [Rollbar PHP](https://github.com/rollbar/rollbar-php)

A CakePHP-specific package is avaliable for integrating Rollbar PHP with CakePHP 2.x:
[CakeRollbar](https://github.com/tranfuga25s/CakeRollbar)

A Flow-specific package is available for integrating Rollbar PHP with Neos Flow: [m12/flow-rollbar](https://packagist.org/packages/m12/flow-rollbar)

Yii package: [baibaratsky/yii-rollbar](https://github.com/baibaratsky/yii-rollbar)

Yii2 package: [baibaratsky/yii2-rollbar](https://github.com/baibaratsky/yii2-rollbar)

## Help / Support

If you run into any issues, please email us at [support@rollbar.com](mailto:support@rollbar.com)

For bug reports, please [open an issue on GitHub](https://github.com/rollbar/rollbar-php/issues/new).


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request


## Testing
Tests are in `tests`.
* To run the tests: `composer test`
* To fix code style issues: `composer fix`

## Docker
Docker binaries are located in `./bin` and can be run by simply executing `bin/phpunit` for example.
* To run tests: `bin/phpunit`
* To run code sniffer: `bin/phpcs` or `bin/phpcbf`
* To run all supported versions: `bin/phpunit-versions`
* To run composer : `bin/composer install` OR `bin/composer update`