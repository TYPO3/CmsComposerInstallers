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

use Composer\IO\IOInterface;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Util\Exception\SymlinkException;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * TYPO3 Core installer
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class CoreInstaller implements \Composer\Installer\InstallerInterface {

	const TYPO3_DIR			= 'typo3';
	const TYPO3_INDEX_PHP	= 'index.php';

	protected $symlinks = array();

	/**
	 * @var \Composer\Composer
	 */
	protected $composer;

	/**
	 * @var \Composer\Downloader\DownloadManager
	 */
	protected $downloadManager;

	/**
	 * @var Filesystem
	 */
	protected $filesystem;

	/**
	 * @var CoreInstaller\GetTypo3OrgService
	 */
	protected $getTypo3OrgService;

	/**
	 * @var IOInterface
	 */
	protected $io;

	/**
	 * @var Config
	 */
	protected $pluginConfig;

	/**
	 * @param \Composer\Composer $composer
	 * @param Filesystem $filesystem
	 */
	public function __construct(\Composer\Composer $composer, Filesystem $filesystem, CoreInstaller\GetTypo3OrgService $getTypo3OrgService, IOInterface $io) {
		$this->composer = $composer;
		$this->downloadManager = $composer->getDownloadManager();
		$this->filesystem = $filesystem;
		$this->io = $io;
		$this->getTypo3OrgService = $getTypo3OrgService;
		$this->initializeConfiguration();
		$this->initializeSymlinks();
	}

	/**
	 * Read plugin configuration
	 */
	protected function initializeConfiguration() {
		$this->pluginConfig = Config::load($this->composer);
	}

	/**
	 * Initialize symlinks with configuration
	 */
	protected function initializeSymlinks() {
		$webDir = $this->filesystem->normalizePath($this->pluginConfig->get('web-dir'));
		$this->filesystem->ensureDirectoryExists($webDir);
		$backendDir = $this->filesystem->normalizePath($this->pluginConfig->get('backend-dir'));
		$sourcesDir = $this->determineInstallPath();
		$this->symlinks = array(
			$sourcesDir . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP
				=> $webDir . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP,
			$sourcesDir . DIRECTORY_SEPARATOR . self::TYPO3_DIR
				=> $backendDir
		);
	}

	/**
	 * Returns if this installer can install that package type
	 *
	 * @param string $packageType
	 * @return boolean
	 */
	public function supports($packageType) {
		return $packageType === 'typo3-cms-core';
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
		return $repo->hasPackage($package)
			&& is_readable($this->getInstallPath($package))
			&& $this->filesystem->allFilesExist($this->symlinks);
	}

	/**
	 * Installs specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function install(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		$this->getTypo3OrgService->addDistToPackage($package);

		if ($this->filesystem->someFilesExist($this->symlinks)) {
			$this->filesystem->removeSymlinks($this->symlinks);
		}

		$this->installCode($package);

		try  {
			$this->filesystem->establishSymlinks($this->symlinks, FALSE);
		} catch (SymlinkException $e) {
			$this->io->writeError('<error>' . $e->getMessage() . '</error>');
		}

		if (!$repo->hasPackage($package)) {
			$repo->addPackage(clone $package);
		}
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

		if ($this->filesystem->someFilesExist($this->symlinks)) {
			$this->filesystem->removeSymlinks($this->symlinks);
		}

		$this->updateCode($initial, $target);

		$this->filesystem->establishSymlinks($this->symlinks, FALSE);

		$repo->removePackage($initial);
		if (!$repo->hasPackage($target)) {
			$repo->addPackage(clone $target);
		}
	}

	/**
	 * Uninstalls specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function uninstall(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		if (!$repo->hasPackage($package)) {
			throw new \InvalidArgumentException('Package is not installed: '.$package);
		}

		if ($this->filesystem->someFilesExist($this->symlinks)) {
			$this->filesystem->removeSymlinks($this->symlinks);
		}

		$this->removeCode($package);
		$repo->removePackage($package);
	}

	/**
	 * Returns the installation path of a package
	 *
	 * @param  \Composer\Package\PackageInterface $package
	 * @return string
	 */
	public function getInstallPath(\Composer\Package\PackageInterface $package) {
		return $this->determineInstallPath();
	}

	/**
	 * @return string
	 */
	protected function determineInstallPath() {
		return $this->pluginConfig->get('cms-package-dir');
	}

	/**
	 * @param \Composer\Package\PackageInterface $package
	 */
	protected function installCode(\Composer\Package\PackageInterface $package) {
		$downloadPath = $this->getInstallPath($package);
		$this->downloadManager->download($package, $downloadPath);
	}

	/**
	 * @param \Composer\Package\PackageInterface $initial
	 * @param \Composer\Package\PackageInterface $target
	 */
	protected function updateCode(\Composer\Package\PackageInterface $initial, \Composer\Package\PackageInterface $target) {
		// Currently the install path for all versions is the same.
		// In the future the install path for two core versions may differ.
		$initialDownloadPath = $this->getInstallPath($initial);
		$targetDownloadPath = $this->getInstallPath($target);
		if ($targetDownloadPath !== $initialDownloadPath) {
			// if the target and initial dirs intersect, we force a remove + install
			// to avoid the rename wiping the target dir as part of the initial dir cleanup
			if (substr($initialDownloadPath, 0, strlen($targetDownloadPath)) === $targetDownloadPath
				|| substr($targetDownloadPath, 0, strlen($initialDownloadPath)) === $initialDownloadPath
			) {
				$this->removeCode($initial);
				$this->installCode($target);

				return;
			}

			$this->filesystem->rename($initialDownloadPath, $targetDownloadPath);
		}
		$this->downloadManager->update($initial, $target, $targetDownloadPath);
	}

	/**
	 * @param \Composer\Package\PackageInterface $package
	 */
	protected function removeCode(\Composer\Package\PackageInterface $package) {
		$downloadPath = $this->getInstallPath($package);
		$this->downloadManager->remove($package, $downloadPath);
	}
}

?>