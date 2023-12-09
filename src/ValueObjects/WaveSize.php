<?php

namespace Masterfermin02\Audio\ValueObjects;

class WaveSize
{
    public function __construct(
        public readonly int $size,
    ){
        if ($this->size < 16) {
            throw new \InvalidArgumentException('The size of the file must be greater than 16');
        }
    }

    public function value(): int
    {
        return $this->size;
    }
}
