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

/**
 * TYPO3 Core installer
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class CoreInstaller extends \Composer\Installer\LibraryInstaller {

	const TYPO3_SRC_DIR = 'typo3_src';
	const TYPO3_DIR = 'typo3';
	const TYPO3_INDEX_PHP	= 'index.php';

	/**
	 * @var CoreInstaller\GetTypo3OrgService
	 */
	protected $getTypo3OrgService;

	/**
	 * @param \Composer\IO\IOInterface $io
	 * @param \Composer\Composer $composer
	 * @param Util\Filesystem $filesystem
	 */
	public function __construct(\Composer\IO\IOInterface $io, \Composer\Composer $composer, Util\Filesystem $filesystem, CoreInstaller\GetTypo3OrgService $getTypo3OrgService) {
		parent::__construct($io, $composer, 'typo3-cms-core', $filesystem);

		$this->getTypo3OrgService = $getTypo3OrgService;
	}

	/**
	 * Checks that provided package is installed.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 *
	 * @return bool
	 */
	public function isInstalled(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		return parent::isInstalled($repo, $package) && $this->filesystem->allFilesExist($this->getSymlinks($package));
	}

	/**
	 * Installs specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function install(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		$this->getTypo3OrgService->addDistToPackage($package);

		$symlinks = $this->getSymlinks($package);

		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}

		parent::install($repo, $package);

		$this->filesystem->establishSymlinks($symlinks);
	}

	/**
	 * Updates specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $initial already installed package version
	 * @param \Composer\Package\PackageInterface $target updated version
	 */
	public function update(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $initial, \Composer\Package\PackageInterface $target) {
		$this->getTypo3OrgService->addDistToPackage($initial);
		$this->getTypo3OrgService->addDistToPackage($target);

		$symlinks = $this->getSymlinks($package);

		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}

		parent::update($repo, $initial, $target);

		$this->filesystem->establishSymlinks($symlinks);
	}

	/**
	 * Uninstalls specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function uninstall(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		parent::uninstall($repo, $package);

		$symlinks = $this->getSymlinks($package);

		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}
	}

	/**
	 * Returns the installation path of a package
	 * Returns the list of expected symlinks
	 *
	 * @param  \Composer\Package\PackageInterface $package
	 * @return string
	 * @param \Composer\Package\PackageInterface $package package instance
	 * @return array
	 */
	public function getInstallPath(\Composer\Package\PackageInterface $package) {
		$this->filesystem->ensureDirectoryExists(self::TYPO3_SRC_DIR);
		return realpath(self::TYPO3_SRC_DIR);
	}

	/**
	 * Returns the list of expected symlinks
	 *
	 * @param \Composer\Package\PackageInterface $package package instance
	 * @return array
	 */
	protected function getSymlinks(\Composer\Package\PackageInterface $package) {
		$installPath = $this->getInstallPath($package);

		return array(
			$installPath . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP => self::TYPO3_INDEX_PHP,
			$installPath . DIRECTORY_SEPARATOR . self::TYPO3_DIR => self::TYPO3_DIR,
		);
	}
}

?>
