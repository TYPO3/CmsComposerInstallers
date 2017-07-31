<?php
namespace TYPO3\CMS\Composer\Installer;

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
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use TYPO3\CMS\Composer\Plugin\Config;

/**
 * TYPO3 Core installer
 */
class CoreInstaller extends LibraryInstaller
{
    /**
     * @var Config
     */
    private $pluginConfig;

    /**
     * @var bool
     */
    private static $deprecationShown = false;

    public function __construct(
        IOInterface $io,
        Composer $composer,
        $type = 'typo3-cms-core',
        \Composer\Util\Filesystem $filesystem = null,
        BinaryInstaller $binaryInstaller = null
    ) {
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
        $this->pluginConfig = Config::load($composer);
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        $installPath = $this->pluginConfig->get('cms-package-dir');
        if (
            !self::$deprecationShown
            && $this->composer->getPackage()->getName() !== 'typo3/cms'
            && $installPath !== $this->composer->getConfig()->get('vendor-dir') . '/typo3/cms'
        ) {
            self::$deprecationShown = true;
            $this->io->writeError('<warning>Config option "cms-package-dir" has not been set or set to a value different from "{$vendor-dir}/typo3/cms".</warning>');
            $this->io->writeError(' <warning>This option will be removed without substitution with typo3/cms-composer-installers 2.0.</warning>');
            $this->io->writeError(' <warning>With 2.0 the typo3/cms package will always be installed in the vendor directory.</warning>');
            $this->io->writeError(' <warning>To get rid of this warning, use the following command to set the option to a not deprecated value:</warning>');
            $this->io->writeError(' <info>composer config extra.typo3/cms.cms-package-dir \'{$vendor-dir}/typo3/cms\'</info>');
        }
        return $this->pluginConfig->get('cms-package-dir');
    }
}
