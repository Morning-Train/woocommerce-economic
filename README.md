# PHP wrapper for integrating with e-conomic for woocommerce

[![Latest Version on Packagist](https://img.shields.io/packagist/v/morning-train/woocommerce-economic.svg?style=flat-square)](https://packagist.org/packages/morning-train/woocommerce-economic)
[![Tests](https://img.shields.io/github/actions/workflow/status/morning-train/woocommerce-economic/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/morning-train/woocommerce-economic/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/morning-train/woocommerce-economic.svg?style=flat-square)](https://packagist.org/packages/morning-train/woocommerce-economic)

This is a wrapper for integrating with e-conomic for woocommerce using the [WP-Economic Package](https://github.com/Morning-Train/wp-e-conomic)

## Installation

You can install the package via composer:

```bash
composer require morningtrain/woocommerce-economic
```

## Usage

### Setup
First, you need to setup the Economic API credentials by using the `Economic::setup` method. This is typically done in your theme's `functions.php` file or a custom plugin.

Then you can initialize the Economic API by calling the `WoocommerceEconomic::init()` method. The `WoocommerceEconomic` class provides methods to interact with the e-conomic API specifically for WooCommerce.

An example of how to set up the Economic API credentials, where you can define the `ECONOMIC_APP_SECRET_TOKEN` and `ECONOMIC_GRANT_TOKEN` in your `wp-config.php` file:

```php
if (defined('ECONOMIC_APP_SECRET_TOKEN') && defined('ECONOMIC_GRANT_TOKEN')) {
    Economic::setup(
        ECONOMIC_APP_SECRET_TOKEN,
        ECONOMIC_GRANT_TOKEN
    );
    WoocommerceEconomic::init();
}
```

### Filters

```php
TODO: Add documentation for filters
```

### Woocommerce
TODO: add documentation for woocommerce usage

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Lars Rasmussen](https://github.com/larasmorningtrain)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
