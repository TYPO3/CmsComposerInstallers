<?php
namespace TYPO3\CMS\Composer\Installer\Util;

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
use Composer\Package\PackageInterface;
use TYPO3\CMS\Composer\Installer\CoreVendorInstaller;
use TYPO3\CMS\Composer\Installer\ExtensionVendorInstaller;

/**
 * The plugin that registers the installers (registered by extra key in composer.json)
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class EventListener implements EventSubscriberInterface {

	/**
	 * @inheritdoc
	 */
	public static function getSubscribedEvents() {
		return array(
			'post-install-cmd' => 'symlinkExtensions',
			'post-update-cmd'  => 'symlinkExtensions'
		);
	}

	/**
	 * @var Composer
	 */
	protected $composer;

	/**
	 * @var Filesystem
	 */
	protected $filesystem;

	public function __construct( Composer $composer, Filesystem $filesystem ) {
		$this->composer = $composer;
		$this->filesystem = $filesystem;
	}

	public function symlinkExtensions() {
		$core = null;
		$extensions = array();
		$packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
		foreach ( $packages as $one ) {
			switch ( $one->getType() ) {
				case CoreVendorInstaller::TYPE_TYPO3_CORE:
					$core = $one;
					break;
				case ExtensionVendorInstaller::TYPE_TYPO3_EXT:
					$extensions[] = $one;
					break;
			}
		}

		if ( $core && $extensions ) {
			$core_ext_dir = $this->composer->getInstallationManager()->getInstallPath( $core ) . '/typo3/ext';
			$this->filesystem->emptyDirectory( $core_ext_dir, true );
			foreach ( $extensions as $one ) {
				$source = $this->composer->getInstallationManager()->getInstallPath( $one );
				$target = $core_ext_dir . '/' .  $this->getExtensionKey( $one );
				$link = $this->filesystem->findShortestPath( $target, $source, false );
				$this->filesystem->symlink( $link, $target, false );
			}
		}
	}

	protected function getExtensionKey( PackageInterface $package ) {
		$extensionKey = '';
		foreach ( $package->getReplaces() as $packageName => $version ) {
			if ( strpos( $packageName, '/' ) === FALSE ) {
				$extensionKey = trim($packageName);
				break;
			}
		}
		if ( empty( $extensionKey ) ) {
			list( , $extensionKey ) = explode( '/', $package->getName(), 2 );
			$extensionKey = str_replace( '-', '_', $extensionKey );
		}
		return $extensionKey;
	}

}
