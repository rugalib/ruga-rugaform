<?php

declare(strict_types=1);

namespace Ruga\Rugaform;

/**
 * Provides functions for a configuration template.
 *
 * @see      ConfigurationInterface
 * @author   Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
trait ConfigurationTrait
{
    /** @var array Configuration. */
    private $config = [];
    
    
    
    /**
     * Store the config.
     *
     * @param array $config
     *
     * @return mixed
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    
    
    
    /**
     * Returns a value from $config or the $default if the key is not found.
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function getConfig(string $name, $default = null)
    {
        if (array_key_exists($name, $this->config)) {
            return $this->config[$name];
        }
        return $default;
    }
    
    
    
    /**
     * Returns a value from $config as a JS boolean string.
     *
     * @param string $name
     * @param bool   $default
     *
     * @return string
     */
    public function getConfigAsJsBoolean(string $name, bool $default)
    {
        $val = $this->getConfig($name) ?? $default;
        if ($val === true) {
            return 'true';
        }
        if ($val === false) {
            return 'false';
        }
    }
    
    
    
    /**
     * Parse boolean value.
     *
     * @param $in
     *
     * @return bool
     */
    public function parseBool($in): bool
    {
        if (is_bool($in)) {
            return $in;
        }
        if (is_string($in)) {
            switch ($in) {
                case 'true':
                case 'yes':
                case '1':
                    return true;
                    break;
                
                case 'false':
                case 'no':
                case '0':
                    return false;
                    break;
            }
        }
    }
    
    
}
