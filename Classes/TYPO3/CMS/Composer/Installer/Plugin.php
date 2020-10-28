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

namespace TYPO3\CMS\Composer\Installer;

use Composer\Cache;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * The plugin that registers the installers (registered by extra key in composer.json)
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class Plugin implements PluginInterface
{
    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $filesystem = new Util\Filesystem();
        $composer
            ->getInstallationManager()
            ->addInstaller(
                new CoreInstaller(
                    $composer,
                    $filesystem,
                    new CoreInstaller\GetTypo3OrgService($io)
                )
            );
        $composer
            ->getInstallationManager()
            ->addInstaller(
                new ExtensionInstaller($composer, $filesystem)
            );

        $cache = null;
        if ($composer->getConfig()->get('cache-files-ttl') > 0) {
            $cache = new Cache($io, $composer->getConfig()->get('cache-files-dir'), 'a-z0-9_./');
        }

        $composer
            ->getDownloadManager()
            ->setDownloader(
                't3x',
                new Downloader\T3xDownloader($io, $composer->getConfig(), null, $cache)
            );
    }
}
