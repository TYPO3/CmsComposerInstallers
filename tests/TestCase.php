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

namespace TYPO3\CMS\ComposerTest;

use Composer\Package\Package;
use Composer\Util\Silencer;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . rand(1000, 9000));
            if (!file_exists($unique) && Silencer::call('mkdir', $unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    public static function createPackage(string $name, $type = 'library', string $version = 'dev-develop'): Package
    {
        $package = new Package($name, $version, $version);
        $package->setType($type);

        return $package;
    }
}
