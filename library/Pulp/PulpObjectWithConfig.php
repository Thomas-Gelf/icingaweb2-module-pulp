<?php

namespace Icinga\Module\Pulp;

abstract class PulpObjectWithConfig
{
    protected $config;

    protected $full;

    public function __construct($object)
    {
        $this->full = $object;
        $this->config = $object->config;
    }

    public function get($key, $default = null)
    {
        if (property_exists($this->full, $key)) {
            return $this->full->$key;
        } else {
            return $default;
        }
    }

    public function getConfigWithout($keys)
    {
        $config = [];
        foreach ($this->config as $key => $value) {
            if (! in_array($key, $keys)) {
                $config[$key] = $value;
            }
        }

        return (object) $config;
    }

    public function getConfig($key, $default = null)
    {
        if (property_exists($this->config, $key)) {
            return $this->config->$key;
        } else {
            return $default;
        }
    }
}
