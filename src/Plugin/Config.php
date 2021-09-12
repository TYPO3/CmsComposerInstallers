<?php

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

namespace TYPO3\CMS\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\RootPackageInterface;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Configuration wrapper to easily access extra configuration for installer
 */
class Config
{
    const RELATIVE_PATHS = 1;
    const NORMALIZE_PATHS = 2;

    /**
     * @var array
     */
    public static $defaultConfig = [
        'web-dir' => '{$app-dir}/public',
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
    public function get($key, $flags = null)
    {
        $flags = $flags ?? self::NORMALIZE_PATHS;
        switch ($key) {
            case 'web-dir':
            case 'root-dir':
            case 'app-dir':
                $val = rtrim($this->process($this->config[$key], $flags), '/\\');
                return (($flags & self::RELATIVE_PATHS) === self::RELATIVE_PATHS) ? $val : $this->realpath($val, $flags);
            case 'base-dir':
                return (($flags & self::RELATIVE_PATHS) === self::RELATIVE_PATHS) ? '' : $this->realpath($this->baseDir, $flags);
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
            static function ($match) use ($config, $flags) {
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
     * @param  int $flags Options (see class constants)
     * @return string
     */
    protected function realpath($path, int $flags = 0)
    {
        if ($path === '') {
            return $this->baseDir;
        }
        if ($path[0] === '/' || (!empty($path[1]) && $path[1] === ':')) {
            return $path;
        }

        return (($flags & self::NORMALIZE_PATHS) === self::NORMALIZE_PATHS) ? (new Filesystem())->normalizePath($this->baseDir . '/' . $path) : $this->baseDir . '/' . $path;
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
     * @param IOInterface|null $io
     * @return Config
     */
    public static function load(Composer $composer, IOInterface $io = null)
    {
        static $config;
        if ($config === null) {
            $io = $io ?? new NullIO();
            $baseDir = static::extractBaseDir($composer->getConfig());
            $rootPackageExtraConfig = self::handleRootPackageExtraConfig($io, $composer->getPackage());
            $config = new static($baseDir);
            $config->merge($rootPackageExtraConfig);
        }
        return $config;
    }

    /**
     * @internal This method isn't of much use, so just don't
     * @param IOInterface $io
     * @param RootPackageInterface $rootPackage
     * @return array
     */
    public static function handleRootPackageExtraConfig(IOInterface $io, RootPackageInterface $rootPackage): array
    {
        if ($rootPackage->getName() === 'typo3/cms') {
            // Configuration for the web dir is different, in case
            // typo3/cms is the root package
            self::$defaultConfig['web-dir'] = '.';

            return [];
        }
        $rootPackageExtraConfig = $rootPackage->getExtra() ?: [];
        $typo3Config = $rootPackageExtraConfig['typo3/cms'] ?? [];
        if (empty($typo3Config)) {
            return $rootPackageExtraConfig;
        }
        $config = new static('/composer/root');
        $config->merge($rootPackageExtraConfig);
        $rootDir = $config->get('root-dir');
        $webDir = $config->get('web-dir');
        $appDir = $config->get('app-dir');
        $baseDir = $config->get('base-dir');
        $defaults = false;
        if ($baseDir !== $appDir && !self::isSubdirectoryOf($baseDir, $appDir)) {
            $defaults = true;
            $io->warning('TYPO3 "app-dir" must be a sub directory or same as Composer root directory (base-dir).');
        }
        if (!self::isSubdirectoryOf($appDir, $webDir)) {
            $defaults = true;
            $io->warning(sprintf('TYPO3 "web-dir" must be a sub directory of %s.', empty($typo3Config['app-dir']) ? 'Composer root directory (base-dir)' : '"app-dir"'));
        }
        if (self::isSubdirectoryOf($webDir, $rootDir)) {
            $defaults = true;
            $io->warning('TYPO3 "root-dir" must not be a sub directory of "web-dir".');
        }
        if (!self::isSubdirectoryOf($appDir, $rootDir)) {
            $defaults = true;
            if (!empty($typo3Config['root-dir'])) {
                // Only show this warning of root-dir was explicitly set and not implicitly derived from web-dir
                $io->warning(sprintf('TYPO3 "root-dir" must be a sub directory of %s.', empty($typo3Config['app-dir']) ? 'Composer root directory (base-dir)' : '"app-dir"'));
            }
        }
        if ($baseDir !== $appDir && $rootPackage->getType() !== 'typo3-cms-extension') {
            $io->warning('Changing TYPO3 "app-dir" is deprecated and will not work any more in future TYPO3 versions.');
        }

        if ($defaults) {
            $io->writeError(sprintf('> typo3/cms-composer-installers: configured base-dir: %s', $baseDir), true, $io::VERY_VERBOSE);
            $io->writeError(sprintf('> typo3/cms-composer-installers: configured app-dir: %s', $appDir), true, $io::VERY_VERBOSE);
            $io->writeError(sprintf('> typo3/cms-composer-installers: configured root-dir: %s', $rootDir), true, $io::VERY_VERBOSE);
            $io->writeError(sprintf('> typo3/cms-composer-installers: configured web-dir: %s', $webDir), true, $io::VERY_VERBOSE);
            $io->warning('Due to errors in TYPO3 path configuration, default configuration is used');
        }
        $rootPackageExtraConfig = $defaults ? [] : $rootPackageExtraConfig;

        $config = new static('/composer/root');
        $config->merge($rootPackageExtraConfig);
        $rootDir = $config->get('root-dir');
        $webDir = $config->get('web-dir');
        $appDir = $config->get('app-dir');
        $baseDir = $config->get('base-dir');

        $io->writeError(sprintf('> typo3/cms-composer-installers: using base-dir: %s', $baseDir), true, $io::VERY_VERBOSE);
        $io->writeError(sprintf('> typo3/cms-composer-installers: using app-dir: %s', $appDir), true, $io::VERY_VERBOSE);
        $io->writeError(sprintf('> typo3/cms-composer-installers: using root-dir: %s', $rootDir), true, $io::VERY_VERBOSE);
        $io->writeError(sprintf('> typo3/cms-composer-installers: using web-dir: %s', $webDir), true, $io::VERY_VERBOSE);

        return $rootPackageExtraConfig;
    }

    private static function isSubdirectoryOf(string $path, string $directoryToTest): bool
    {
        return $path !== $directoryToTest && strpos($directoryToTest, $path) === 0;
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
