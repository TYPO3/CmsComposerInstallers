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

namespace TYPO3\CMS\ComposerTest;

use Composer\Package\Package;
use Composer\Util\Silencer;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public static function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms-composer-installer-test';
        if (Silencer::call('mkdir', $root, 0777)) {
            return realpath($root);
        }

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $version
     * @return Package
     */
    public static function createPackage($name, $type = 'library', $version = 'dev-develop')
    {
        $package = new Package($name, $version, $version);
        $package->setType($type);

        return $package;
    }
}
