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

class ActiveExtensionsTest extends TestCase
{
    const TYPO3_PATH = '/tmp/typo3-cms-installer/';

    public function setUp(): void
    {
        include_once self::TYPO3_PATH . 'vendor/composer/ActiveExtensions.php';
        parent::setUp();
    }

    public function testGetName(): void
    {
        $name = \TYPO3\CMS\Core\Package\ActiveExtensions::getName('core');
        self::assertSame('typo3/cms-core', $name);
    }

    public function testGetExtensionKey(): void
    {
        $extKey = \TYPO3\CMS\Core\Package\ActiveExtensions::getExtensionKey('typo3/cms-core');
        self::assertSame('core', $extKey);

        $extKey = \TYPO3\CMS\Core\Package\ActiveExtensions::getExtensionKey('georgringer/news');
        self::assertSame('news', $extKey);
    }

    public function testGetPath(): void
    {
        $path = \TYPO3\CMS\Core\Package\ActiveExtensions::getPath('core');
        self::assertFileExists($path);

        $path = \TYPO3\CMS\Core\Package\ActiveExtensions::getPath('news');
        self::assertFileExists($path);
    }

    public function testGetVersion(): void
    {
        $regex = '/^(?<version>[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)(?<prerelease>-[0-9a-zA-Z.]+)?(?<build>\+[0-9a-zA-Z.]+)?$/';
        $version = \TYPO3\CMS\Core\Package\ActiveExtensions::getVersion('core');
        $match = preg_match($regex, $version);
        self::assertNotEmpty($match);

        $version = \TYPO3\CMS\Core\Package\ActiveExtensions::getVersion('news');
        $match = preg_match($regex, $version);
        self::assertNotEmpty($match);
    }
}
