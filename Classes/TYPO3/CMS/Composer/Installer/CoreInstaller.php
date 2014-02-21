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

use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * TYPO3 Core installer (delegates most FS related tasks to drivers)
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class CoreInstaller  extends CoreInstaller\CoreInstallerAbstract implements InstallerInterface
{
	/**
	 * @var CoreInstaller\CoreInstallerInterface
	 */
	protected $driver;

	/**
	 * @var array
	 */
	protected $availableDrivers = array(
		'TYPO3\CMS\Composer\Installer\CoreInstaller\SymlinkDriver',
		'TYPO3\CMS\Composer\Installer\CoreInstaller\CopyDriver'
	);

	/**
	 * Determine and return the driver
	 *
	 * @return CoreInstaller\CoreInstallerInterface
	 */
	public function getDriver() {
		if (!$this->driver) {
			$interface = __CLASS__ . '\CoreInstallerInterface';
			foreach ($this->availableDrivers as &$driver) {
				if (!is_subclass_of($driver, $interface)) {
					throw new CoreInstaller\DriverMissingInterfaceException($driver, $interface);
				}
				if (is_string($driver)) {
					$driver = new $driver($this->io, $this->composer, $this->filesystem);
				}
				/* @var $driver CoreInstaller\CoreInstallerInterface */
				if ($driver->isPossible()) {
					$this->driver = $driver;
					break;
				}
			}
			if (!$this->driver) {
				throw new CoreInstaller\NoDriverFoundException();
			}
		}
		return $this->driver;
	}

	/**
	 * Set or reset the driver
	 *
	 * @param \TYPO3\CMS\Composer\Installer\CoreInstaller\CoreInstallerInterface $driver
	 */
	public function setDriver(CoreInstaller\CoreInstallerInterface $driver = NULL) {
		$this->driver = $driver;
	}


	/**
	 * Get the available drivers
	 *
	 * @return array
	 */
	public function getAvailableDrivers() {
		return $this->availableDrivers;
	}

	/**
	 * Set the available drivers
	 *
	 * @param array $availableDrivers
	 * @return \TYPO3\CMS\Composer\Installer\CoreInstaller
	 */
	public function setAvailableDrivers(array $availableDrivers) {
		$this->availableDrivers = $availableDrivers;
		return $this;
	}

	/**
	 * Get the path, this package is installed to
	 *
	 * @param \Composer\Package\PackageInterface $package
	 * @return string
	 */
	public function getInstallPath(PackageInterface $package) {
		return $this->getDriver()->getInstallPath($package);
	}

	/**
	 * Install a package
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo
	 * @param \Composer\Package\PackageInterface $package
	 */
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {
		$this->getDriver()->install($package);
		if (!$repo->hasPackage($package)) {
			$repo->addPackage(clone $package);
		}
	}

	/**
	 * Determines if a package is installed
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo
	 * @param \Composer\Package\PackageInterface $package
	 * @return boolean
	 */
	public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package) {
		if (!$repo->hasPackage($package)) {
			return FALSE;
		}
		return $this->getDriver()->isInstalled($package);
	}

	/**
	 * Uninstalls a package
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo
	 * @param \Composer\Package\PackageInterface $package
	 * @throws \InvalidArgumentException
	 */
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package) {

		if (!$repo->hasPackage($package)) {
			throw new CoreInstaller\PackageNotInstalledException($package);
		}
		$repo->removePackage($package);

		$this->getDriver()->uninstall($package);
	}

	/**
	 * Updates a package
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo
	 * @param \Composer\Package\PackageInterface $initial
	 * @param \Composer\Package\PackageInterface $target
	 * @throws \InvalidArgumentException
	 */
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target) {
		if (!$repo->hasPackage($initial)) {
			throw new CoreInstaller\PackageNotInstalledException($initial);
		}

		$this->getDriver()->update($initial, $target);

		$repo->removePackage($initial);
		if (!$repo->hasPackage($target)) {
			$repo->addPackage(clone $target);
		}
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
}
?>