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
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use TYPO3\CMS\Composer\Installer\Util\EventListener;

/**
 * The plugin that registers the installers (registered by extra key in composer.json)
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class Plugin implements PluginInterface {

	/**
	 * {@inheritDoc}
	 */
	public function activate(Composer $composer, IOInterface $io) {
		$filesystem = new Util\Filesystem();

		$textra = $composer->getPackage()->getExtra();
		if ( isset($textra['typo3_vendor_based']) && $textra['typo3_vendor_based'] ) {
			$composer->getInstallationManager()->addInstaller(
				new CoreVendorInstaller( $io, $composer, $filesystem )
			);
			$composer->getInstallationManager()->addInstaller(
				new ExtensionVendorInstaller( $io, $composer, $filesystem )
			);
			$composer->getEventDispatcher()->addSubscriber(
				new EventListener(  $composer, $filesystem )
			);
		}
		else {
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
		}
		$composer
			->getDownloadManager()
			->setDownloader(
				't3x',
				new Downloader\T3xDownloader($io, $composer->getConfig())
			);
	}

}

?>
