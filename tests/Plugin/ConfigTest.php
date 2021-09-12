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

use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\ComposerTest\TestCase;

class ConfigTest extends TestCase
{
    public function pathConfigDataProvider(): array
    {
        $defaultConfig = [
            'app-dir' => '/root',
            'web-dir' => '/root/public',
            'root-dir' => '/root/public',
        ];

        return [
            'empty config, results in default config' => [
                [
                ],
                $defaultConfig,
            ],
            'default config specified' => [
                [
                    'typo3/cms' => [
                        'app-dir' => '{$base-dir}',
                        'web-dir' => '{$app-dir}/public',
                        'root-dir' => '{$web-dir}',
                    ],
                ],
                $defaultConfig,
            ],
            'app sub of base' => [
                [
                    'typo3/cms' => [
                        'app-dir' => 'app',
                    ],
                ],
                [
                    'app-dir' => '/root/app',
                    'web-dir' => '/root/app/public',
                    'root-dir' => '/root/app/public',
                ],
            ],
            'web sub of app' => [
                [
                    'typo3/cms' => [
                        'web-dir' => 'web',
                    ],
                ],
                [
                    'app-dir' => '/root',
                    'web-dir' => '/root/web',
                    'root-dir' => '/root/web',
                ],
            ],
            'root sub of app' => [
                [
                    'typo3/cms' => [
                        'root-dir' => 'root-dir',
                    ],
                ],
                [
                    'app-dir' => '/root',
                    'web-dir' => '/root/public',
                    'root-dir' => '/root/root-dir',
                ],
            ],
            'root sub of app, web sub of root' => [
                [
                    'typo3/cms' => [
                        'root-dir' => 'root-dir',
                        'web-dir' => 'root-dir/web',
                    ],
                ],
                [
                    'app-dir' => '/root',
                    'web-dir' => '/root/root-dir/web',
                    'root-dir' => '/root/root-dir',
                ],
            ],
            'root and web sub of app' => [
                [
                    'typo3/cms' => [
                        'root-dir' => 'not-web',
                        'web-dir' => 'web',
                    ],
                ],
                [
                    'app-dir' => '/root',
                    'web-dir' => '/root/web',
                    'root-dir' => '/root/not-web',
                ],
            ],
            'app not sub of composer, results in default config' => [
                [
                    'typo3/cms' => [
                        'app-dir' => '../foo',
                    ],
                ],
                $defaultConfig,
            ],
            'root not sub of app, results in default config' => [
                [
                    'typo3/cms' => [
                        'app-dir' => 'app-dir',
                        'root-dir' => '.',
                    ],
                ],
                $defaultConfig,
            ],
            'app sub of web, results in default config' => [
                [
                    'typo3/cms' => [
                        'app-dir' => 'web/app',
                        'web-dir' => 'web',
                    ],
                ],
                $defaultConfig,
            ],
            'app sub of root, results in default config' => [
                [
                    'typo3/cms' => [
                        'app-dir' => 'root/app',
                        'root-dir' => 'root',
                    ],
                ],
                $defaultConfig,
            ],
            'web not sub dir of app, results in default config' => [
                [
                    'typo3/cms' => [
                        'app-dir' => 'app-dir',
                        'web-dir' => '.',
                    ],
                ],
                $defaultConfig,
            ],
            'root sub of web, results in default config' => [
                [
                    'typo3/cms' => [
                        'root-dir' => 'web/root',
                        'web-dir' => 'web',
                    ],
                ],
                $defaultConfig,
            ],
        ];
    }

    /**
     * @test
     *
     * @param $rootConfig
     * @param $expectedRootConfig
     * @dataProvider pathConfigDataProvider
     */
    public function pathConfigIsValidatedAndResetIfInvalid($rootConfig, $expectedRootConfig): void
    {
        $rootPackage = new RootPackage('test/test', '1.0.0.0', '1.0.0');
        $rootPackage->setExtra($rootConfig);
        $validatedConfig = Config::handleRootPackageExtraConfig(new NullIO(), $rootPackage);

        $config = new Config('/root');
        $config->merge($validatedConfig);
        $processedConfig = [
            'app-dir' => $config->get('app-dir'),
            'web-dir' => $config->get('web-dir'),
            'root-dir' => $config->get('root-dir'),
        ];

        self::assertSame($expectedRootConfig, $processedConfig);
    }
}
