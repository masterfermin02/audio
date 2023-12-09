<?php

namespace Masterfermin02\Audio;

use Masterfermin02\Audio\ValueObjects\WaveSize;

class Wave
{
    public function __construct(
        public readonly string $fileName,
        public readonly WaveSize $size,
        public readonly string $id,
        public readonly string $type,
        public readonly string $compression,
        public readonly string $channels,
        public readonly string $fameRate,
        public readonly string $byteRate,
        public readonly string $bits,
        public readonly string $length,
    ){}
}
