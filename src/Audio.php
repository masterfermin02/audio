<?php

namespace Masterfermin02\Audio;

use InvalidArgumentException;
use Masterfermin02\Audio\ValueObjects\Id3v2;

class Audio
{
    public Wave|Mp3|Ogg $wave;
    /**
     * @var bool
     */
    public bool $valid;

    public $waveFilename;

    public array $info = [];

    public string $imageBaseDir = './';

    public function __construct(
        public readonly Mp3Info $mp3Info = new Mp3Info(),
        public readonly WaveFactory $waveFactory = new WaveFactory(),
    ) {
    }

    public static function create(): self
    {
        return new static();
    }

    public function setImageBaseDir(string $dir): self
    {
        $this->imageBaseDir = $dir;

        return $this;
    }

    // ************************************************************************
    // getSampleInfo extracts the attributes of the AudioFile-Object
    // ************************************************************************

    public function getSampleInfo(): bool
    {
        $this->wave = $this->waveFactory
            ->setFileName($this->waveFilename)
            ->build();

        return true;
    }

    // ************************************************************************
    // printSampleInfo prints the attributes of the AudioFile-Object
    // ************************************************************************

    public function printSampleInfo(): void
    {
        $this->wave->printInfo();
    }

    public function getVisualization(): void
    {
        $this->wave->visualize();
    }

    // ************************************************************************
    // loadFile initializes the AudioFile-Object
    // ************************************************************************

    public function loadFile($loadFilename): void
    {
        $this->waveFilename = $loadFilename;
        if (!$this->getSampleInfo()) {
            throw new InvalidArgumentException('Invalid file');
        }
    }
}
