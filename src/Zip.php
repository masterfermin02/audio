<?php

namespace Masterfermin02\Audio;

class Zip extends Wave
{
    private string $fileType = 'ZIP';
    public function __construct(
        public readonly string $fileName,
        public readonly ValueObjects\WaveSize $size,
        public readonly string $id,
        public readonly string $type,
        public readonly string $compression,
        public readonly string $channels,
        public readonly string $fameRate,
        public readonly string $byteRate,
        public readonly string $bits,
        public readonly string $length,
    ){
        parent::__construct(
            $fileName,
            $size,
            $this->fileType,
            $this->type,
            $compression,
            $channels,
            $fameRate,
            $byteRate,
            $bits,
            $length,
        );
    }
}
