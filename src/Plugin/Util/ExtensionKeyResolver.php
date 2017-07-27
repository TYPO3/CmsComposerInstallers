<?php
declare(strict_types=1);
namespace TYPO3\CMS\Composer\Plugin\Util;

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

use Composer\Package\PackageInterface;

/**
 * Resolves an extension key from a package
 */
class ExtensionKeyResolver
{
    /**
     * Resolves the extension key from replaces or package name
     *
     * @param PackageInterface $package
     * @throws \RuntimeException
     * @return string
     */
    public static function resolve(PackageInterface $package): string
    {
        if (strpos($package->getType(), 'typo3-cms-') === false) {
            throw new \RuntimeException(sprintf('Tried to resolve an extension key from non extension package "%s"', $package->getName()), 1501195043);
        }
        foreach ($package->getReplaces() as $packageName => $version) {
            if (strpos($packageName, '/') === false) {
                $extensionKey = trim($packageName);
                break;
            }
        }
        if (empty($extensionKey)) {
            list(, $extensionKey) = explode('/', $package->getName(), 2);
            $extensionKey = str_replace('-', '_', $extensionKey);
        }
        $extra = $package->getExtra();
        if (!empty($extra['installer-name'])) {
            $extensionKey = $extra['installer-name'];
        }
        if (!empty($extra['typo3/cms']['extension-key'])) {
            $extensionKey = $extra['typo3/cms']['extension-key'];
        }
        return $extensionKey;
    }
}
