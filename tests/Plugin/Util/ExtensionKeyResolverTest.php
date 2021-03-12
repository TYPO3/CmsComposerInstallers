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

use Composer\IO\BufferIO;
use TYPO3\CMS\Composer\Plugin\Util\ExtensionKeyResolver;
use TYPO3\CMS\ComposerTest\TestCase;

class ExtensionKeyResolverTest extends TestCase
{
    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(array $packageData, string $expectedExtensionKey)
    {
        /** @var Package $package */
        $package = $this->createPackage($packageData['name'], $packageData['type']);
        $package->setExtra($packageData['extra'] ?? []);
        $extensionKey = ExtensionKeyResolver::resolve($package);
        $this->assertSame($expectedExtensionKey, $extensionKey);
    }

    public function resolveDataProvider()
    {
        return [
            'extensionkey' => [
                'packageData' => [
                    'name' => 'somevendor/somepackage-extension',
                    'type' => 'typo3-cms-extension',
                    'extra' => [
                        'typo3/cms' => [
                            'extension-key' => 'extension',
                        ],
                    ],
                ],
                'expectedExtensionKey' => 'extension',
            ],
            'no-extensionkey' => [
                'packageData' => [
                    'name' => 'somevendor/somepackage-extra',
                    'type' => 'typo3-cms-extension',
                ],
                'expectedExtensionKey' => 'somepackage_extra',
            ],
            'installername' => [
                'packageData' => [
                    'name' => 'somevendor/somepackage-installername',
                    'type' => 'typo3-cms-extension',
                    'extra' => [
                        'installer-name' => 'installername',
                    ],
                ],
                'expectedExtensionKey' => 'installername',
            ],
        ];
    }

    public function testDeprecationMessage()
    {
        /** @var Package $package */
        $package = $this->createPackage('somevendor/somepackage-extension', 'typo3-cms-extension');

        $io = new BufferIO();
        $extensionKey = ExtensionKeyResolver::resolve($package, $io);
        $output = $io->getOutput();

        $this->assertSame('somepackage_extension', $extensionKey);
        $this->assertTrue(false !== strpos($output, 'The TYPO3 extension package "somevendor/somepackage-extension", does not define an extension key in its composer.json.'));
        $this->assertTrue(false !== strpos($output, 'Specifying the extension key will be mandatory in future versions of TYPO3'));
    }
}
