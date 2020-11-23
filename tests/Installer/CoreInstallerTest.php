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

    public function testUpdate()
    {
        $installer = $this->createCoreInstaller();

        /** @var Package $package */
        $initial = $this->createPackageWithFiles(
            $installer,
            'typo3/cms',
            'typo3-cms-core',
            'dev-develop',
            [
                'index.php' => '<?php echo "typo3 frontend";',
                'typo3/index.php' => '<?php echo "typo3 backend";',
            ]
        );

        /** @var Package $package */
        $target = $this->createPackage(
            'typo3/cms',
            'typo3-cms-core',
            'dev-master'
        );

        $this->repository
            ->expects($this->once())
            ->method('hasPackage')
            ->will($this->returnValue(true));

        if (!defined('Composer\Composer::RUNTIME_API_VERSION') || version_compare(Composer::RUNTIME_API_VERSION, '2.0.0') < 0) {
            $this->downloadManager
                ->method('update')
                ->with($initial, $target, '/tmp/cms-composer-installer-test/typo3_src');
        } else {
            $this->downloadManager
                ->method('update')
                ->with($initial, $target, '/tmp/cms-composer-installer-test/typo3_src')
                ->will($this->returnValue(\React\Promise\resolve()));
        }

        $installer->update($this->repository, $initial, $target);
    }
}
