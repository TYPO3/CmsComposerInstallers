<?php
namespace TYPO3\CMS\Composer\Installer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Hans-Peter Oeri <hp@oeri.ch>
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
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use \TYPO3\CMS\Composer\Installer\Util\Filesystem;
use TYPO3\CMS\Composer\Installer\CoreInstaller\GetTypo3OrgService;

/**
 * TYPO3 Core Vendor Directory installer
 *
 * @author Hans-Peter Oeri <hp@oeri.ch>
 */
class CoreVendorInstaller extends LibraryInstaller {

	const TYPE_TYPO3_CORE = 'typo3-cms-core';
	const DIR_TYPO3       = 'typo3';

	/**
	 * @var CoreInstaller\GetTypo3OrgService
	 */
	protected $getTypo3OrgService;

	/**
	 * @var string
	 */
	protected $web_dir;

	public function __construct( IOInterface $io, Composer $composer, Filesystem $filesystem = null, GetTypo3OrgService $getTypo3OrgService = null ) {
		$filesystem = $filesystem ?: new Filesystem();
		parent::__construct( $io, $composer, self::TYPE_TYPO3_CORE, $filesystem );

		$this->getTypo3OrgService = $getTypo3OrgService ?: new GetTypo3OrgService( $io );

		$textra = $composer->getPackage()->getExtra();
		$this->web_dir = getcwd() . DIRECTORY_SEPARATOR .
			(isset($textra['typo3_web_dir']) ? $textra['typo3_web_dir'] : '') . DIRECTORY_SEPARATOR;
	}

	public function isInstalled( InstalledRepositoryInterface $repo, PackageInterface $package ) {
		return parent::isInstalled( $repo, $package ) &&
			$this->filesystem->allFilesExist( array( $this->web_dir . self::DIR_TYPO3 ) );
	}

	protected function installCode( PackageInterface $package ) {
		$this->getTypo3OrgService->addDistToPackage($package);
		parent::installCode( $package );

		$this->filesystem->ensureDirectoryExists( $this->web_dir );
		$source_dir = $this->getInstallPath( $package ) . DIRECTORY_SEPARATOR . self::DIR_TYPO3;
		$link = $this->filesystem->findShortestPath( $this->web_dir, $source_dir, false );
		$this->filesystem->symlink( $link, $this->web_dir . self::DIR_TYPO3, false );
	}

	protected function removeCode( PackageInterface $package ) {
		$this->filesystem->remove( $this->web_dir . self::DIR_TYPO3 );
		parent::removeCode( $package );
	}

}
