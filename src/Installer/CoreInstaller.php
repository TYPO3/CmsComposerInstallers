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

    public function __construct(
        IOInterface $io,
        Composer $composer,
        $type = 'typo3-cms-core',
        \Composer\Util\Filesystem $filesystem = null,
        BinaryInstaller $binaryInstaller = null
    ) {
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
        $this->pluginConfig = Config::load($composer);
        $installPath = $this->pluginConfig->get('cms-package-dir');
        if (
            $composer->getPackage()->getName() !== 'typo3/cms'
            && $installPath !== $this->composer->getConfig()->get('vendor-dir') . '/typo3/cms'
        ) {
            $this->io->writeError('<warning>Config option cms-package-dir has been deprecated.</warning>');
            $this->io->writeError(' <warning>It will be removed with typo3/cms-composer-installers 2.0.</warning>');
            $this->io->writeError(' <warning>Set it to "{$vendor-dir}/typo3/cms" to get rid of this warning.</warning>');
        }
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->pluginConfig->get('cms-package-dir');
    }
}
