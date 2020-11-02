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

use Composer\Package\Package;

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
                'expectedPath' => 'typo3/sysext/extension',
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
}
