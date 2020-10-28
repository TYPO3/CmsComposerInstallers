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

use Composer\IO\IOInterface;
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
     * @param IOInterface $io
     * @throws \RuntimeException
     * @return string
     */
    public static function resolve(PackageInterface $package, IOInterface $io = null): string
    {
        if (strpos($package->getType(), 'typo3-cms-') === false) {
            throw new \RuntimeException(sprintf('Tried to resolve an extension key from non extension package "%s"', $package->getName()), 1501195043);
        }
        $extra = $package->getExtra();
        if (!empty($extra['typo3/cms']['extension-key'])) {
            return $extra['typo3/cms']['extension-key'];
        }
        if ($io instanceof IOInterface) {
            $io->writeError(
                [
                    sprintf(
                        '<comment>TYPO3 Extension Package "%s", does not define extension key in composer.json.</comment>',
                        $package->getName()
                    ),
                    '<comment>Specifying the extension key will be mandatory in future versions of TYPO3 (see: https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ExtensionArchitecture/ComposerJson/Index.html#extra)</comment>',
                ]
            );
        }
        foreach ($package->getReplaces() as $link) {
            if (strpos($link->getTarget(), '/') === false) {
                $extensionKey = trim($link->getTarget());
                break;
            }
        }
        if (empty($extensionKey)) {
            list(, $extensionKey) = explode('/', $package->getName(), 2);
            $extensionKey = str_replace('-', '_', $extensionKey);
        }
        if (!empty($extra['installer-name'])) {
            $extensionKey = $extra['installer-name'];
        }

        return $extensionKey;
    }
}
