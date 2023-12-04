<?php

namespace Masterfermin02\Audio\ValueObjects;

class Id3v2
{
    private array $dynamicProperties = [];

    public function __set($name, $value) {
        // Magic method __set is called when trying to set an inaccessible property
        $this->dynamicProperties[$name] = $value;
    }

    public function __get($name) {
        // Magic method __get is called when trying to access an inaccessible property
        if (array_key_exists($name, $this->dynamicProperties)) {
            return $this->dynamicProperties[$name];
        }

        // Handle cases where the property doesn't exist
        // You might throw an exception or return a default value
        return null;
    }
}
