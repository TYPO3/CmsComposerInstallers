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

use Composer\Package\Package;

class ExtensionInstallerTest extends InstallerTestCase
{
    /**
     * @dataProvider extensionInstallationSupportedDataprovider
     */
    public function testExtensionInstallationSupported(string $packageType)
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
     * @dataProvider extensionInstallationNotSupportedDataProvider
     */
    public function testExtensionInstallationNotSupported(string $packageType)
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
     * @dataProvider extensionInstallPathDataProvider
     */
    public function testExtensionInstallPath(array $packageData, string $expectedPath)
    {
        $installer = $this->createExtensionInstaller();

        /** @var Package $package */
        $package = $this->createPackage($packageData['name'], $packageData['type']);
        $package->setExtra($packageData['extra']);

        $installPath = $installer->getInstallPath($package);
        $this->assertSame($expectedPath, $installPath);
    }

    public function extensionInstallPathDataProvider()
    {
        return [
            [
                'packageData' => [
                    'name' => 'typo3/cms-core',
                    'type' => 'typo3-cms-framework',
                    'extra' => [
                        'typo3/cms' => [
                            'extension-key' => 'core',
                        ],
                    ],
                ],
                'expectedPath' => '/public/typo3/sysext/core',
            ],
            [
                'packageData' => [
                    'name' => 'somevendor/somepackage-extension',
                    'type' => 'typo3-cms-extension',
                    'extra' => [
                        'typo3/cms' => [
                            'extension-key' => 'extension',
                        ],
                    ],
                ],
                'expectedPath' => '/public/typo3conf/ext/extension',
            ],
        ];
    }
}
