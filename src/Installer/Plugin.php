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
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\BinaryInstaller;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\HttpDownloader;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Core\AutoloadConnector;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * The plugin that registers the installers (registered by extra key in composer.json)
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @author Helmut Hummel <info@helhum.io>
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoload',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $filesystem = new Filesystem();
        $binaryInstaller = new BinaryInstaller($io, rtrim($composer->getConfig()->get('bin-dir'), '/'), $composer->getConfig()->get('bin-compat'), $filesystem);
        $pluginConfig = Config::load($composer);
        $composer
            ->getInstallationManager()
            ->addInstaller(
                new CoreInstaller($io, $composer, $filesystem, $pluginConfig, $binaryInstaller)
            );
        $composer
            ->getInstallationManager()
            ->addInstaller(
                new ExtensionInstaller($io, $composer, $filesystem, $pluginConfig, $binaryInstaller)
            );

        $cache = null;
        if ($composer->getConfig()->get('cache-files-ttl') > 0) {
            $cache = new Cache($io, $composer->getConfig()->get('cache-files-dir'), 'a-z0-9_./');
        }

        if (!defined('Composer\Composer::RUNTIME_API_VERSION') || version_compare(Composer::RUNTIME_API_VERSION, '2.0.0') < 0) {
            $t3xDownloader = new Downloader\T3xDownloader($io, $composer->getConfig(), null, $cache);
        } else {
            $httpDownloader = new HttpDownloader($io, $composer->getConfig());
            $t3xDownloader = new Downloader\T3xDownloader2($io, $composer->getConfig(), $httpDownloader, null, $cache);
        }
        $composer->getDownloadManager()->setDownloader('t3x', $t3xDownloader);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @param Event $event
     */
    public function postAutoload(Event $event)
    {
        $autoloadConnector = new AutoloadConnector();
        $autoloadConnector->linkAutoloader($event);
    }
}
