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

namespace TYPO3\CMS\ComposerTest\Installer;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerInterface;
use Composer\IO\IOInterface;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use TYPO3\CMS\Composer\Installer\CoreInstaller;
use TYPO3\CMS\Composer\Installer\ExtensionInstaller;
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

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        chdir($this->previousDirectory);
        if (is_dir($this->rootDirectory)) {
            $filesystem = new Filesystem();
            $filesystem->removeDirectory($this->rootDirectory);
        }
    }

    protected function createComposerConfig(): Config
    {
        $config = new Config();
        $config->merge([
            'config' => [
                'vendor-dir' => $this->rootDirectory . DIRECTORY_SEPARATOR . 'vendor',
                'bin-dir' => $this->rootDirectory . DIRECTORY_SEPARATOR . 'bin',
            ],
            'repositories' => ['packagist' => false],
        ]);

        return $config;
    }

    protected function createCoreInstaller(): InstallerInterface
    {
        return new CoreInstaller($this->io, $this->composer, 'typo3-cms-core');
    }

    protected function createExtensionInstaller(): InstallerInterface
    {
        return new ExtensionInstaller($this->io, $this->composer);
    }
}
