<?php
namespace TYPO3\CMS\Composer\Installer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Christian Opitz <christian.opitz at netresearch.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Cache;
use Composer\Script\ScriptEvents;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * The plugin that registers the installers (registered by extra key in composer.json)
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents() {
		return array(
			ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoload'
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(Composer $composer, IOInterface $io) {
		$filesystem = new Filesystem();
		$composer
			->getInstallationManager()
			->addInstaller(
				new CoreInstaller($composer, $filesystem)
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

	/**
	 * @param \Composer\Script\Event $event
	 */
	public function postAutoload(\Composer\Script\Event $event) {
		$autoloadConnector = new \TYPO3\CMS\Composer\Plugin\Core\AutoloadConnector();
		$autoloadConnector->linkAutoloader($event);
	}

}
