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

class CoreInstallerTest extends InstallerTestCase
{
    /**
     * @param string $packageType
     * @dataProvider coreInstallationSupportedDataProvider
     */
    public function testCoreInstallationSupported($packageType)
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
     * @param string $packageType
     * @dataProvider coreInstallationNotSupportedDataProvider
     */
    public function testCoreInstallationNotSupported($packageType)
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

    public function testCoreInstallation()
    {
        $installer = $this->createCoreInstaller();
        $files = [
            'index.php' => '<?php echo "typo3 frontend";',
            'typo3/index.php' => '<?php echo "typo3 backend";',
        ];
        $package = $this->createPackageWithFiles($installer, 'typo3/cms', 'typo3-cms-core', 'dev-develop', $files);
        $installer->install($this->repository, $package);
    }
}
