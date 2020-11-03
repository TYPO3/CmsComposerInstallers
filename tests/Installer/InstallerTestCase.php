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

namespace TYPO3\CMS\ComposerTest\Installer;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerInterface;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use TYPO3\CMS\Composer\Installer\CoreInstaller;
use TYPO3\CMS\Composer\Installer\ExtensionInstaller;
use TYPO3\CMS\Composer\Plugin\Config as TYPO3Config;
use TYPO3\CMS\ComposerTest\TestCase;

class InstallerTestCase extends TestCase
{
    /**
     * @string
     */
    protected $previousDirectory;

    /**
     * @string
     */
    protected $rootDirectory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var InstalledRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var IOInterface
     */
    protected $io;

    protected function setUp()
    {
        $this->previousDirectory = getcwd();
        $this->rootDirectory = TestCase::getUniqueTmpDirectory();
        chdir($this->rootDirectory);

        $this->composer = new Composer();
        $this->composer->setConfig($this->createComposerConfig());

        /** @var InstallationManager */
        $installationManager = $this->createMock(InstallationManager::class);
        $this->composer->setInstallationManager($installationManager);

        /** @var DownloadManager */
        $downloadManager = $this->getMockBuilder(DownloadManager::class)->disableOriginalConstructor()->getMock();
        $this->composer->setDownloadManager($downloadManager);

        /** @var RootPackage|\PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createMock(RootPackageInterface::class);
        $this->composer->setPackage($package);

        $this->repository = $this->createMock(InstalledRepositoryInterface::class);
        $this->io = $this->createMock(IOInterface::class);
    }

    protected function tearDown()
    {
        chdir($this->previousDirectory);
        if (is_dir($this->rootDirectory)) {
            $filesystem = new Filesystem();
            $filesystem->removeDirectory($this->rootDirectory);
        }
    }

    /**
     * @return Config
     */
    protected function createComposerConfig()
    {
        $config = new Config(true, $this->rootDirectory);
        $config->merge([
            'config' => [
                'vendor-dir' => 'vendor',
                'bin-dir' => 'bin',
            ],
            'repositories' => ['packagist' => false],
        ]);

        return $config;
    }

    /**
     * @return InstallerInterface
     */
    protected function createCoreInstaller()
    {
        $filesystem = new \TYPO3\CMS\Composer\Plugin\Util\Filesystem();
        $binaryInstaller = new BinaryInstaller($this->io, rtrim($this->composer->getConfig()->get('bin-dir'), '/'), $this->composer->getConfig()->get('bin-compat'), $filesystem);
        $config = TYPO3Config::load($this->composer);
        return new CoreInstaller($this->io, $this->composer, $filesystem, $config, $binaryInstaller);
    }

    /**
     * @return InstallerInterface
     */
    protected function createExtensionInstaller()
    {
        $filesystem = new \TYPO3\CMS\Composer\Plugin\Util\Filesystem();
        $binaryInstaller = new BinaryInstaller($this->io, rtrim($this->composer->getConfig()->get('bin-dir'), '/'), $this->composer->getConfig()->get('bin-compat'), $filesystem);
        $config = TYPO3Config::load($this->composer);
        return new ExtensionInstaller($this->io, $this->composer, $filesystem, $config, $binaryInstaller);
    }

    /**
     * @param InstallerInterface $installer
     * @param string $name
     * @param string $type
     * @param string $version
     * @param array $files
     * @return Package
     */
    protected function createPackageWithFiles($installer, $name, $type = 'library', $version = 'dev-develop', array $files = [])
    {
        $package = $this->createPackage($name, $type, $version);
        $packageInstallationPath = $installer->getInstallPath($package);

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($packageInstallationPath);
        if (count($files) > 0) {
            foreach ($files as $filename => $fileContent) {
                $path = $filesystem->normalizePath($packageInstallationPath . DIRECTORY_SEPARATOR . $filename);
                $filesystem->ensureDirectoryExists(dirname($path));
                file_put_contents($path, $fileContent);
            }
        }

        return $package;
    }
}
