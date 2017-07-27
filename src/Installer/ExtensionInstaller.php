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
use Composer\Downloader\DownloadManager;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\BinaryPresenceInterface;
use Composer\Installer\InstallerInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Util\ExtensionKeyResolver;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Enter descriptions here
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @author Helmut Hummel <info@helhum.io>
 */
class ExtensionInstaller implements InstallerInterface, BinaryPresenceInterface
{
    /**
     * @var string
     */
    protected $extensionDir;

    /**
     * @var string
     */
    protected $systemExtensionDir;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var DownloadManager
     */
    protected $downloadManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Config
     */
    protected $pluginConfig;

    /**
     * @var BinaryInstaller
     */
    protected $binaryInstaller;

    /**
     * @param IOInterface $io
     * @param Composer $composer
     * @param Filesystem $filesystem
     * @param Config $pluginConfig
     * @param BinaryInstaller $binaryInstaller
     */
    public function __construct(IOInterface $io, Composer $composer, Filesystem $filesystem, Config $pluginConfig, BinaryInstaller $binaryInstaller)
    {
        $this->composer = $composer;
        $this->downloadManager = $composer->getDownloadManager();

        $this->filesystem = $filesystem;
        $this->binaryInstaller = $binaryInstaller;
        $this->pluginConfig = $pluginConfig;
        if ($pluginConfig->get('extensions-in-vendor-dir')) {
            $this->systemExtensionDir = $this->extensionDir = $pluginConfig->get('vendor-dir');
            $io->writeError('<warning>Configuration option extensions-in-vendor-dir has been deprecated.</warning>');
            $io->writeError(' <warning>It will be removed with typo3/cms-composer-installers 2.0.</warning>');
        } else {
            $webDirectory = $this->filesystem->normalizePath($pluginConfig->get('root-dir'));
            $this->extensionDir = $webDirectory . '/typo3conf/ext';
            $this->systemExtensionDir = $webDirectory . '/typo3/sysext';
        }
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType)
    {
        return $packageType !== 'typo3-cms-core'
            && strncmp('typo3-cms-', $packageType, 10) === 0;
    }

    /**
     * Checks that provided package is installed.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $package package instance
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return $repo->hasPackage($package) && is_readable($this->getInstallPath($package));
    }

    /**
     * Installs specific package.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $downloadPath = $this->getInstallPath($package);
        // Remove the binaries if it appears the package files are missing
        if (!is_readable($downloadPath) && $repo->hasPackage($package)) {
            $this->binaryInstaller->removeBinaries($package);
        }
        $this->installCode($package);
        $this->binaryInstaller->installBinaries($package, $downloadPath);
        if (!$repo->hasPackage($package)) {
            $repo->addPackage(clone $package);
        }
    }

    /**
     * Updates specific package.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $initial already installed package version
     * @param PackageInterface $target updated version
     *
     * @throws \InvalidArgumentException if $initial package is not installed
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        if (!$repo->hasPackage($initial)) {
            throw new \InvalidArgumentException('Package is not installed: ' . $initial);
        }
        $this->binaryInstaller->removeBinaries($initial);
        $this->updateCode($initial, $target);
        $this->binaryInstaller->installBinaries($target, $this->getInstallPath($target));
        $repo->removePackage($initial);
        if (!$repo->hasPackage($target)) {
            $repo->addPackage(clone $target);
        }
    }

    /**
     * Uninstalls specific package.
     *
     * @param InstalledRepositoryInterface $repo repository in which to check
     * @param PackageInterface $package
     *
     * @throws \InvalidArgumentException if $package is not installed
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            throw new \InvalidArgumentException('Package is not installed: ' . $package);
        }

        $this->removeCode($package);
        $this->binaryInstaller->removeBinaries($package);
        $repo->removePackage($package);
    }

    /**
     * Returns the installation path of a package
     *
     * @param PackageInterface $package
     * @return string path
     */
    public function getInstallPath(PackageInterface $package)
    {
        if ($this->pluginConfig->get('extensions-in-vendor-dir')) {
            $extensionInstallDir = $package->getName();
        } else {
            $extensionInstallDir = ExtensionKeyResolver::resolve($package);
        }
        if ($package->getType() === 'typo3-cms-framework') {
            return $this->systemExtensionDir . DIRECTORY_SEPARATOR . $extensionInstallDir;
        }
        return $this->extensionDir . DIRECTORY_SEPARATOR . $extensionInstallDir;
    }

    /**
     * Make sure binaries are installed for a given package.
     *
     * @param PackageInterface $package Package instance
     */
    public function ensureBinariesPresence(PackageInterface $package)
    {
        $this->binaryInstaller->installBinaries($package, $this->getInstallPath($package), false);
    }

    /**
     * Re-install binary by removing previous one
     *
     * @param PackageInterface $package Package instance
     */
    public function installBinary(PackageInterface $package)
    {
        $this->binaryInstaller->removeBinaries($package);
        $this->binaryInstaller->installBinaries($package, $this->getInstallPath($package));
    }

    /**
     * @param PackageInterface $package
     */
    protected function installCode(PackageInterface $package)
    {
        $this->downloadManager->download($package, $this->getInstallPath($package));
    }

    /**
     * @param PackageInterface $initial
     * @param PackageInterface $target
     */
    protected function updateCode(PackageInterface $initial, PackageInterface $target)
    {
        $initialDownloadPath = $this->getInstallPath($initial);
        $targetDownloadPath = $this->getInstallPath($target);
        if ($targetDownloadPath !== $initialDownloadPath) {
            // if the target and initial dirs intersect, we force a remove + install
            // to avoid the rename wiping the target dir as part of the initial dir cleanup
            if (substr($initialDownloadPath, 0, strlen($targetDownloadPath)) === $targetDownloadPath
                || substr($targetDownloadPath, 0, strlen($initialDownloadPath)) === $initialDownloadPath
            ) {
                $this->removeCode($initial);
                $this->installCode($target);

                return;
            }

            $this->filesystem->rename($initialDownloadPath, $targetDownloadPath);
        }
        $this->downloadManager->update($initial, $target, $targetDownloadPath);
    }

    /**
     * @param PackageInterface $package
     */
    protected function removeCode(PackageInterface $package)
    {
        $this->downloadManager->remove($package, $this->getInstallPath($package));
    }
}
