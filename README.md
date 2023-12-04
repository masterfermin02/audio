# Extracts attributes of audio-files & Visualization

[![Latest Version on Packagist](https://img.shields.io/packagist/v/masterfermin02/audio.svg?style=flat-square)](https://packagist.org/packages/masterfermin02/audio)
[![Tests](https://github.com/masterfermin02/audio/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/masterfermin02/audio/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/masterfermin02/audio.svg?style=flat-square)](https://packagist.org/packages/masterfermin02/audio)

This class was basically built to recognize attributes (like frequency, format, channels, resolution, compression, length, id3-tags) of audio-files (.wav,.aif,.mp3,.ogg at the moment). Furthermore the latest version includes a method to visualize audio-samples as known in common audio-software (waveLab, CoolEdit...) using the GD-library.

inspire by [PHP-Extracts-attributes-of-audio-files-Visualization.html](https://www.phpclasses.org/package/482-PHP-Extracts-attributes-of-audio-files-Visualization.html)

## Installation

You can install the package via composer:

```bash
composer require masterfermin02/audio
```

## Usage

```php
$skeleton = new Masterfermin02\Audio();
echo $skeleton->echoPhrase('Hello, Masterfermin02!');
```

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

- [Fermin](https://github.com/masterfermin02)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
