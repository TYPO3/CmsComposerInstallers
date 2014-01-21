<?php
namespace Netresearch\Composer\Installer\Typo3;

/*                                                                        *
 * This script belongs to the Composer-TYPO3-Installer package            *
 * (c) 2014 Netresearch GmbH & Co. KG                                     *
 * This copyright notice MUST APPEAR in all copies of the script!         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

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
		'Netresearch\Composer\Installer\Typo3\CoreInstaller\SymlinkDriver',
		'Netresearch\Composer\Installer\Typo3\CoreInstaller\CopyDriver'
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
	 * @param \Netresearch\Composer\Installer\Typo3\CoreInstaller\CoreInstallerInterface $driver
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
	 * @return \Netresearch\Composer\Installer\Typo3\CoreInstaller
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
		$this->firstInstallSetup();
		if (!$repo->hasPackage($package)) {
			$repo->addPackage(clone $package);
		}
	}

	/**
	 * Create the directories and files required for the first run
	 */
	protected function firstInstallSetup() {
		foreach (array('fileadmin', 'typo3conf', 'typo3temp', 'uploads') as $dir) {
			$this->filesystem->ensureDirectoryExists($this->cwd . '/' . $dir);
		}
		file_put_contents($this->cwd . '/typo3conf/FIRST_INSTALL', '');
		file_put_contents($this->cwd . '/typo3conf/ENABLE_INSTALL_TOOL', '');
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
		return $packageType === 'typo3cms-core';
	}
}
?>