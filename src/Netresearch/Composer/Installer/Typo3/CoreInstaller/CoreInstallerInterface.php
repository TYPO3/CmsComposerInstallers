<?php
namespace Netresearch\Composer\Installer\Typo3\CoreInstaller;

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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Package\PackageInterface;

interface CoreInstallerInterface
{
	public function __construct(IOInterface $io, Composer $composer, Filesystem $filesystem);

	public function getInstallPath(PackageInterface $package);

	public function install(PackageInterface $package);

	public function isInstalled(PackageInterface $package);

	public function uninstall(PackageInterface $package);

	public function update(PackageInterface $initial, PackageInterface $target);

	public function isPossible();
}
?>