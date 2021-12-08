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
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Composer\Plugin\Util\ExtensionKeyResolver;

class ExtensionKeyResolverTest extends TestCase
{
    /**
     * @dataProvider resolveDataProvider
     * @test
     */
    public function extensionKeyIsResolvedCorrectly(array $packageData, string $expectedExtensionKey): void
    {
        $package = new Package($packageData['name'], 'dev-develop', 'dev-develop');
        $package->setType($packageData['type']);
        $package->setExtra($packageData['extra'] ?? []);
        $extensionKey = ExtensionKeyResolver::resolve($package);
        self::assertSame($expectedExtensionKey, $extensionKey);
    }

    public function resolveDataProvider(): \Generator
    {
        yield 'extension' => [
            'packageData' => [
                'name' => 'somevendor/somepackage-extension',
                'type' => 'typo3-cms-extension',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'extension_with_key',
                    ],
                ],
            ],
            'expectedExtensionKey' => 'extension_with_key',
        ];

        yield 'composer package' => [
            'packageData' => [
                'name' => 'somevendor/somepackage-extension',
                'type' => 'library',
                'extra' => [
                    'typo3/cms' => [
                        'extension-key' => 'extension_with_key',
                    ],
                ],
            ],
            'expectedExtensionKey' => 'extension_with_key',
        ];

        yield 'composer package without extra' => [
            'packageData' => [
                'name' => 'somevendor/somepackage-extension',
                'type' => 'library',
            ],
            'expectedExtensionKey' => 'somevendor/somepackage-extension',
        ];
    }

    /**
     * @test
     */
    public function extensionKeyResolvingThrowsExceptionIfKeyNotSetInExtra(): void
    {
        $this->expectException(\RuntimeException::class);
        $package = new Package('foo/bar', 'dev-develop', 'dev-develop');
        $package->setType('typo3-cms-extension');
        ExtensionKeyResolver::resolve($package);
    }
}
