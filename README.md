# Extracts attributes of audio-files & Visualization

[![Latest Version on Packagist](https://img.shields.io/packagist/v/masterfermin02/audio.svg?style=flat-square)](https://packagist.org/packages/masterfermin02/audio)
[![Tests](https://github.com/masterfermin02/audio/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/masterfermin02/audio/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/masterfermin02/audio.svg?style=flat-square)](https://packagist.org/packages/masterfermin02/audio)

This package was designed to identify various attributes of audio files such as frequency, format, channels, resolution, compression, length, and id3-tags. Currently, it supports audio files in .wav, .aif, .mp3, and .ogg formats. Additionally, the latest version comes with a feature that allows users to visualize audio samples similar to popular audio software such as waveLab and CoolEdit, using the GD library.

inspire by [PHP-Extracts-attributes-of-audio-files-Visualization.html](https://www.phpclasses.org/package/482-PHP-Extracts-attributes-of-audio-files-Visualization.html)

## Installation

You can install the package via composer:

```bash
composer require masterfermin02/audio
```

## Usage

```php
use Masterfermin02\Audio\Audio;

$audio = Audio::create();

$audio->loadFile(getenv('filename'));
$audio->printSampleInfo();

if ($audio->waveId == "RIFF")
{
    $imageSrc = $audio->getVisualization($filename);
    print "<img src='$imageSrc' />";
}
```

## You can add the image dir

```php
use Masterfermin02\Audio\Audio;

$audio = Audio::create();

$audio->loadFile(getenv('filename'));
$audio->printSampleInfo();

if ($audio->waveId == "RIFF")
{
    $imageSrc = $audio->setImageBaseDir('./images/')
    ->getVisualization($filename);
    print "<img src='$imageSrc' />";
}
```

## Screenshots
![result](./screen/Screenshot%202023-12-04%20at%203.40.12%20PM.png?raw=true "Result")

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
