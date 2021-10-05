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

namespace TYPO3\CMS\Composer\Plugin\Util;

use Composer\Package\PackageInterface;

/**
 * Resolves an extension key from a package
 */
class ExtensionKeyResolver
{
    /**
     * Resolves the extension key from extra section
     *
     * @param PackageInterface $package
     * @throws \RuntimeException
     * @return string
     */
    public static function resolve(PackageInterface $package): string
    {
        $extra = $package->getExtra();
        if (empty($extra['typo3/cms']['extension-key'])) {
            throw new \RuntimeException(sprintf('Tried to resolve an extension key from non extension package "%s"', $package->getName()), 1501195043);
        }
        return $extra['typo3/cms']['extension-key'];
    }
}
