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

class CoreInstallerTest extends InstallerTestCase
{
    /**
     * @dataProvider coreInstallationSupportedDataProvider
     */
    public function testCoreInstallationSupported(string $packageType)
    {
        $installer = $this->createCoreInstaller();
        $this->assertTrue($installer->supports($packageType));
    }

    public function coreInstallationSupportedDataProvider()
    {
        return [
            ['typo3-cms-core'],
        ];
    }

    /**
     * @dataProvider coreInstallationNotSupportedDataProvider
     */
    public function testCoreInstallationNotSupported(string $packageType)
    {
        $installer = $this->createCoreInstaller();
        $this->assertFalse($installer->supports($packageType));
    }

    public function coreInstallationNotSupportedDataProvider()
    {
        return [
            ['typo3-cms-extension'],
            ['typo3-cms-framework'],
            ['library'],
            ['project'],
            ['metapackage'],
            ['composer-plugin'],
        ];
    }

    public function testCoreInstallationException()
    {
        $installer = $this->createCoreInstaller();
        $package = $this->createPackage('typo3/cms', 'typo3-cms-core');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1513083851);
        $this->expectExceptionMessage('Installation stopped.');
        $installer->install($this->repository, $package);
    }
}
