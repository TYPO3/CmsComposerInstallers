<?php
namespace TYPO3\CMS\Composer\Plugin;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Composer\Composer;

/**
 * Configuration wrapper to easily access extra configuration for installer
 */
class Config
{
    const RELATIVE_PATHS = 1;

    /**
     * @var array
     */
    public static $defaultConfig = [
        'web-dir' => 'public',
        'root-dir' => '{$web-dir}',
        'app-dir' => '{$base-dir}',
        // The following values are for internal use only and do not represent public API
        // Names and behaviour of these values might change without notice
        'composer-mode' => true,
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @param string $baseDir
     */
    public function __construct($baseDir = null)
    {
        $this->baseDir = $baseDir;
        // load defaults
        $this->config = static::$defaultConfig;
    }

    /**
     * Merges new config values with the existing ones (overriding)
     *
     * @param array $config
     */
    public function merge(array $config)
    {
        // Override defaults with given config
        if (!empty($config['typo3/cms']) && is_array($config['typo3/cms'])) {
            foreach ($config['typo3/cms'] as $key => $val) {
                $this->config[$key] = $val;
            }
        }
    }

    /**
     * Returns a setting
     *
     * @param  string $key
     * @param  int $flags Options (see class constants)
     * @return mixed
     */
    public function get($key, $flags = 0)
    {
        switch ($key) {
            case 'web-dir':
            case 'root-dir':
            case 'app-dir':
                $val = rtrim($this->process($this->config[$key], $flags), '/\\');
                return ($flags & self::RELATIVE_PATHS === 1) ? $val : $this->realpath($val);
            case 'base-dir':
                return ($flags & self::RELATIVE_PATHS === 1) ? '' : $this->realpath($this->baseDir);
            default:
                if (!isset($this->config[$key])) {
                    return null;
                }
                return $this->process($this->config[$key], $flags);
        }
    }

    /**
     * @param int $flags Options (see class constants)
     * @return array
     */
    public function all($flags = 0)
    {
        $all = [];
        foreach (array_keys($this->config) as $key) {
            $all['config'][$key] = $this->get($key, $flags);
        }

        return $all;
    }

    /**
     * @return array
     */
    public function raw()
    {
        return [
            'config' => $this->config,
        ];
    }

    /**
     * Checks whether a setting exists
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Replaces {$refs} inside a config string
     *
     * @param  string $value a config string that can contain {$refs-to-other-config}
     * @param  int $flags Options (see class constants)
     * @return string
     */
    protected function process($value, $flags)
    {
        $config = $this;

        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback(
            '#\{\$(.+)\}#',
            function ($match) use ($config, $flags) {
                return $config->get($match[1], $flags);
            },
            $value
        );
    }

    /**
     * Turns relative paths in absolute paths without realpath()
     *
     * Since the dirs might not exist yet we can not call realpath or it will fail.
     *
     * @param  string $path
     * @return string
     */
    protected function realpath($path)
    {
        if ($path === '') {
            return $this->baseDir;
        }
        if ($path[0] === '/' || (!empty($path[1]) && $path[1] === ':')) {
            return $path;
        }

        return $this->baseDir . '/' . $path;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * @param Composer $composer
     * @return Config
     */
    public static function load(Composer $composer)
    {
        static $config;
        if ($config === null) {
            $baseDir = static::extractBaseDir($composer->getConfig());
            if ($composer->getPackage()->getName() === 'typo3/cms') {
                // Configuration for the web dir is different, in case
                // typo3/cms is the root package
                self::$defaultConfig['web-dir'] = '.';
            }
            $config = new static($baseDir);
            $rootPackageExtraConfig = $composer->getPackage()->getExtra();
            if (is_array($rootPackageExtraConfig)) {
                $config->merge($rootPackageExtraConfig);
            }
        }
        return $config;
    }

    /**
     * @param \Composer\Config $config
     * @return mixed
     */
    protected static function extractBaseDir(\Composer\Config $config)
    {
        $reflectionClass = new \ReflectionClass($config);
        $reflectionProperty = $reflectionClass->getProperty('baseDir');
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($config);
    }
}
