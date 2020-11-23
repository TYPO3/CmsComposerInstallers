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
use Composer\Package\Package;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem as TYPO3Filesystem;

class ExtensionInstallerTest extends InstallerTestCase
{
    /**
     * @dataProvider extensionInstallationSupportedDataprovider
     * @param string $packageType
     */
    public function testExtensionInstallationSupported($packageType)
    {
        $installer = $this->createExtensionInstaller();
        $this->assertTrue($installer->supports($packageType));
    }

    public function extensionInstallationSupportedDataprovider()
    {
        return [
            ['typo3-cms-extension'],
            ['typo3-cms-framework'],
        ];
    }

    /**
     * @param string $packageType
     * @dataProvider extensionInstallationNotSupportedDataProvider
     */
    public function testExtensionInstallationNotSupported($packageType)
    {
        $installer = $this->createExtensionInstaller();
        $this->assertFalse($installer->supports($packageType));
    }

    public function extensionInstallationNotSupportedDataProvider()
    {
        return [
            ['typo3-cms-core'],
            ['library'],
            ['project'],
            ['metapackage'],
            ['composer-plugin'],
        ];
    }

    /**
     * @param array $packageData
     * @param string $expectedPath
     * @dataProvider extensionInstallPathDataProvider
     */
    public function testExtensionInstallPath($packageData, $expectedPath)
    {
        $installer = $this->createExtensionInstaller();

        /** @var Package $package */
        $package = $this->createPackage($packageData['name'], $packageData['type']);
        if (isset($packageData['extra'])) {
            $package->setExtra($packageData['extra']);
        }

        $installPath = $installer->getInstallPath($package);
        $this->assertSame('/tmp/cms-composer-installer-test' . DIRECTORY_SEPARATOR . $expectedPath, $installPath);
    }

    public function extensionInstallPathDataProvider()
    {
        return [
            [
                'packageData' => [
                    'name' => 'vendor/extension',
                    'type' => 'typo3-cms-framework',
                ],
                'expectedPath' => 'typo3conf/ext/extension',
            ],
            [
                'packageData' => [
                    'name' => 'somevendor/somepackage-extension',
                    'type' => 'typo3-cms-extension',
                ],
                'expectedPath' => 'typo3conf/ext/somepackage_extension',
            ],
        ];
    }

    public function testUpdate()
    {
        $filesystem = $this->getMockBuilder(TYPO3Filesystem::class)
            ->getMock();
        $filesystem
            ->expects($this->once())
            ->method('rename')
            ->with('/ext/old', '/ext/new');

        /** @var Package $package */
        $initial = $this->createPackage('somevendor/somepackage-extension', 'typo3-cms-extension');
        $initial->setExtra([
            'typo3/cms' => [
                'extension-key' => 'old',
            ],
        ]);

        /** @var Package $package */
        $target = $this->createPackage('somevendor/somepackage-extension', 'typo3-cms-extension');
        $target->setExtra([
            'typo3/cms' => [
                'extension-key' => 'new',
            ],
        ]);

        $this->repository
            ->expects($this->exactly(3))
            ->method('hasPackage')
            ->will($this->onConsecutiveCalls(true, false, false));

        if (!defined('Composer\Composer::RUNTIME_API_VERSION') || version_compare(Composer::RUNTIME_API_VERSION, '2.0.0') < 0) {
            $this->downloadManager
                ->expects($this->once())
                ->method('update')
                ->with($initial, $target, '/ext/new');
        } else {
            $this->downloadManager
                ->expects($this->once())
                ->method('update')
                ->with($initial, $target, '/ext/new')
                ->will($this->returnValue(\React\Promise\resolve()));
        }

        $this->repository
            ->expects($this->once())
            ->method('removePackage')
            ->with($initial);

        $this->repository
            ->expects($this->once())
            ->method('addPackage')
            ->with($target);

        $installer = $this->createExtensionInstaller($filesystem);
        $installer->update($this->repository, $initial, $target);

        $this->setExpectedException('InvalidArgumentException');
        $installer->update($this->repository, $initial, $target);
    }
}
