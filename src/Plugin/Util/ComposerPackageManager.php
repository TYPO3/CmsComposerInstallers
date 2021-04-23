<?php
declare(strict_types=1);

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

namespace TYPO3\CMS\Composer\Plugin\Util;

use Composer\Composer;
use Composer\Factory;
use Composer\InstalledVersions;
use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Config;

class ComposerPackageManager
{
    /**
     * Raw data of installed packages, see InstalledVersions.php
     */
    protected $raw;

    /**
     * Array of extensions and their requirements
     */
    protected $required = [];

    /**
     * Array of typo3 extensions with their package name
     */
    protected $extensionPackageNames = [];

    /**
     * Mapping for from extension key to composer package name
     */
    protected $extensionPackageNameMap = [];

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var Composer
     */
    protected $composer;

    public function __construct(Event $event)
    {
        $this->raw = InstalledVersions::getRawData();
        $this->event = $event;
        $this->composer = $event->getComposer();
    }

    public function initialize()
    {
        $this->filterByType('/^typo3-cms-framework/');
        $orderFrameworkPackages = $this->orderDependencies();

        $this->filterByType('/^typo3-cms-extension/');
        $orderExtensionPackages = $this->orderDependencies();

        $this->writePackageStates(array_merge($orderFrameworkPackages, $orderExtensionPackages));
        $this->writeActiveExtensions(array_merge($orderFrameworkPackages, $orderExtensionPackages));
    }

    /**
     * Get all packages by type using a regex
     *
     * @param string $regex
     */
    public function filterByType(string $regex): void
    {
        $this->extensionPackageNames = [];
        $this->required = [];

        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if (preg_match($regex, $package->getType(), $output_array)) {
                $thisRequire = [];

                foreach ($package->getRequires() as $require) {
                    $thisRequire[] = $require->getTarget();
                }

                $extensions[] = $package->getName();
                $this->extensionPackageNameMap[$package->getName()] = $package->getExtra()['typo3/cms']['extension-key'] ?? basename($this->raw['versions'][$package->getName()]['install_path']);
                $this->required[$package->getName()] = $thisRequire;
            }
        }

        // TODO: Why are package names duplicated ??
        $this->extensionPackageNames = array_values(array_unique($extensions ?? []));
    }

    /**
     * Order extensions based on the required section of composer.json
     *
     * @return array
     */
    private function orderDependencies(): array
    {
        foreach ($this->required as $key => $extension) {
            $rePosition = $originalKey = array_search($key, $this->extensionPackageNames);

            foreach ($extension as $required) {
                if ($positionOfRequire = array_search($required, $this->extensionPackageNames)) {
                    if ($rePosition < $positionOfRequire) {
                        $rePosition = $positionOfRequire;
                    }
                }
            }

            unset($this->extensionPackageNames[$originalKey]);
            array_splice($this->extensionPackageNames, $rePosition, 0, $key);
        }
        return $this->extensionPackageNames;
    }

    /**
     * Add packagePath to PackageStates array
     *
     * @param array $extensions
     * @return array
     */
    private function appendPackagePath(array $extensions): array
    {
        $packageStates = [];
        $root = dirname(realpath(Factory::getComposerFile()));
        foreach ($extensions as $extension) {
            $path = $this->raw['versions'][$extension]['install_path'] ?? '';
            $relativePackagePath = str_replace($root, '..', $path);
            preg_match('/typo3conf\/ext.*|typo3\/sysext.*/', $relativePackagePath, $extensionPath);

            try {
                $packageStates[$this->extensionPackageNameMap[$extension]]['packagePath'] = $extensionPath[0];
            } catch (\ErrorException $e) {
                throw new \ErrorException('No "install_path" set in InstalledVersions.php, can\'t generate PackageStates.php/ActiveExtensions.php. Make sure you are running composer >= 2.1');
            }
        }

        return $packageStates;
    }

    /**
     * Write php File named PackageStates.php
     * Takes a array of packages names to be written
     *
     * @param array $extensions
     */
    private function writePackageStates(array $extensions): void
    {
        $packageStatesContent = [
            'packages' => $this->appendPackagePath($extensions),
            'version' => 5,
        ];

        $comment = "
/**
 * Sorted array of TYPO3 extensions 'split' into two main groups
 *   1. typo3-cms-framework
 *   2. typo3-cms-extension
 */
        ";

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $pluginConfig = Config::load($this->composer);
        $packageStatesFile = $pluginConfig->get('web-dir') . DIRECTORY_SEPARATOR . 'typo3conf/PackageStates.php';

        file_put_contents(
            $packageStatesFile,
            sprintf("<?php\n%s\nreturn %s;", $comment, var_export($packageStatesContent, true))
        );
    }

    private function writeActiveExtensions(array $extensions): void
    {
        $pluginConfig = Config::load($this->composer);
        $packageBasePath = $pluginConfig->get('web-dir') . DIRECTORY_SEPARATOR;
        $extensions = $this->appendPackagePath($extensions);
        $activeExtensions = [];

        foreach ($extensions as $key => $extension) {
            $packageName = array_search($key, $this->extensionPackageNameMap);
            $activeExtensions[$key] = [
                'composerPackage' => $packageName,
                'version' => InstalledVersions::getVersion($packageName),
                'path' => $packageBasePath . $extension['packagePath'],
            ];
        }

        $activeExtensionsFile = $this->composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'ActiveExtensions.php';
        $comment = <<<EOF
// This file was autogenerated using typo3/cms-composer-installers
// 'composer dump-autoload' will recreate this file
EOF;

        $extensions = 'private static array $extensions = ' . var_export($activeExtensions, true);
        $activeExtensionsContent = <<<EOF
<?php

namespace TYPO3\CMS\Core\Package;

$comment

class ActiveExtensions {
    $extensions;

    public static function getRawData() {
        return self::\$extensions;
    }

    public static function getInfo(\$extensionKey) {
        return self::\$extensions[\$extensionKey] ?: null;
    }

    public static function getExtensionKey(\$packageName) {
        \$key = array_search(\$packageName, array_column(self::\$extensions, 'composerPackage'));
        return \$key === false ? null : array_keys(self::\$extensions)[\$key];
    }

    public static function getName(\$extensionKey) {
        return self::\$extensions[\$extensionKey]['composerPackage'] ?: null;
    }

    public static function getVersion(\$extensionKey) {
        return self::\$extensions[\$extensionKey]['version'] ?: null;
    }

    public static function getPath(\$extensionKey) {
        return self::\$extensions[\$extensionKey]['path'] ?: null;
    }
}
EOF;

        file_put_contents(
            $activeExtensionsFile,
            $activeExtensionsContent
        );
    }
}
