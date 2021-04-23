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

use PHPUnit\Framework\TestCase;

class PackageStatesTest extends TestCase
{
    const TYPO3_PATH = '/tmp/typo3-cms-installer/';

    public function testPackageStatesArray(): void
    {
        $packageStates = self::TYPO3_PATH . 'public/typo3conf/PackageStates.php';
        self::assertFileExists($packageStates);
        $packageStatesArray = require $packageStates;
        self::assertSame($this->packageStatesArraySample(), $packageStatesArray);
    }

    private function packageStatesArraySample(): array
    {
        return [
            'packages' => [
                'core' => [
                    'packagePath' => 'typo3/sysext/core',
                ],
                'beuser' => [
                    'packagePath' => 'typo3/sysext/beuser',
                ],
                'belog' => [
                    'packagePath' => 'typo3/sysext/belog',
                ],
                'extbase' => [
                    'packagePath' => 'typo3/sysext/extbase',
                ],
                'extensionmanager' => [
                    'packagePath' => 'typo3/sysext/extensionmanager',
                ],
                'felogin' => [
                    'packagePath' => 'typo3/sysext/felogin',
                ],
                'filelist' => [
                    'packagePath' => 'typo3/sysext/filelist',
                ],
                'fluid' => [
                    'packagePath' => 'typo3/sysext/fluid',
                ],
                'form' => [
                    'packagePath' => 'typo3/sysext/form',
                ],
                'frontend' => [
                    'packagePath' => 'typo3/sysext/frontend',
                ],
                'fluid_styled_content' => [
                    'packagePath' => 'typo3/sysext/fluid_styled_content',
                ],
                'impexp' => [
                    'packagePath' => 'typo3/sysext/impexp',
                ],
                'info' => [
                    'packagePath' => 'typo3/sysext/info',
                ],
                'install' => [
                    'packagePath' => 'typo3/sysext/install',
                ],
                'recordlist' => [
                    'packagePath' => 'typo3/sysext/recordlist',
                ],
                'backend' => [
                    'packagePath' => 'typo3/sysext/backend',
                ],
                'dashboard' => [
                    'packagePath' => 'typo3/sysext/dashboard',
                ],
                'rte_ckeditor' => [
                    'packagePath' => 'typo3/sysext/rte_ckeditor',
                ],
                'seo' => [
                    'packagePath' => 'typo3/sysext/seo',
                ],
                'setup' => [
                    'packagePath' => 'typo3/sysext/setup',
                ],
                'sys_note' => [
                    'packagePath' => 'typo3/sysext/sys_note',
                ],
                't3editor' => [
                    'packagePath' => 'typo3/sysext/t3editor',
                ],
                'tstemplate' => [
                    'packagePath' => 'typo3/sysext/tstemplate',
                ],
                'viewpage' => [
                    'packagePath' => 'typo3/sysext/viewpage',
                ],
                'news' => [
                    'packagePath' => 'typo3conf/ext/news',
                ],
            ],
            'version' => 5,
        ];
    }
}
