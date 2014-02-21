<?php
namespace TYPO3\CMS\Composer\Installer\CoreInstaller;

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
use Composer\Util\Filesystem;
use Composer\Package\PackageInterface;

/**
 * Interface for the drivers
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
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