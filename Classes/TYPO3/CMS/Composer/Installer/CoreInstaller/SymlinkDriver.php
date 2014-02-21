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

use Composer\Package\PackageInterface;
use TYPO3\CMS\Composer\Installer\Util;

/**
 * Driver to use, when the FS supports symlinks
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class SymlinkDriver extends CoreInstallerAbstract implements CoreInstallerInterface {

	public function getInstallPath(PackageInterface $package) {
		$path = $this->vendorDir;
		$path .= '/' . $package->getPrettyName();

		$targetDir = $package->getTargetDir();
		if ($targetDir) {
			$path .= '/' . $targetDir;
		}
		return $path;
	}

	/**
	 * Also under linux symlinks are not always supported for example
	 * when using it in smbfs mounted folder - test that
	 *
	 * @staticvar NULL $isSymlinkPossible
	 * @return boolean
	 */
	public function isPossible() {
		static $isSymlinkPossible = NULL;
		if ($isSymlinkPossible !== NULL) {
			return $isSymlinkPossible;
		}

		$typo3SrcDir = $this->cwd . '/' . self::TYPO3_SRC_DIR;
		if (file_exists($typo3SrcDir) && is_link($typo3SrcDir)) {
			return TRUE;
		}

		$testLink = $this->cwd . '/' . self::TYPO3_DIR;
		$this->filesystem->ensureDirectoryExists($typo3SrcDir);

		try {
			$this->makeSymlink($testLink, $typo3SrcDir);
			$isSymlinkPossible = TRUE;
		} catch (SymlinkException $e) {
			$isSymlinkPossible = FALSE;
		}

		$this->filesystem->remove($testLink);
		$this->filesystem->remove($typo3SrcDir);

		return $isSymlinkPossible;
	}

	protected function makeSymlink($link, $target) {
		if (!file_exists($target)) {
			throw new SymlinkException($link, $target, 'Target doesn\'t exist');
		}
		$relativeTarget = $this->filesystem->findShortestPath($link, $target);
		exec(
			'cd ' . escapeshellarg(dirname($link)) . '; ' .
			'ln -s ' . escapeshellarg($relativeTarget) . ' ' . escapeshellarg(basename($link)),
			$output,
			$return
		);
		if ($return > 0) {
			throw new SymlinkException($link, $target, implode("\n", $output));
		}
	}

	protected function linkMatchesTarget($link, $target) {
		return Util::realpath($link) === Util::realpath($target);
	}


	protected function linkTargets(array $linkTargets) {
		foreach ($linkTargets as $link => $target) {
			if (file_exists($link)) {
				if (!$this->linkMatchesTarget($link, $target)) {
					$this->filesystem->remove($link);
				} else {
					continue;
				}
			}
			$this->makeSymlink($link, $target);
		}
	}

	protected function getLinkTargets($package) {
		$typo3SrcPath = $this->cwd . '/' . self::TYPO3_SRC_DIR;
		$installPath = $this->getInstallPath($package);
		return array(
			$this->cwd . '/' . self::TYPO3_SRC_DIR => $installPath,
			$this->cwd . '/' . self::TYPO3_DIR => $typo3SrcPath . '/' . self::TYPO3_DIR,
			$this->cwd . '/' . self::TYPO3_INDEX_PHP => $typo3SrcPath . '/' . self::TYPO3_INDEX_PHP
		);
	}

	public function isInstalled(\Composer\Package\PackageInterface $package) {
		foreach ($this->getLinkTargets($package) as $link => $target) {
			if (!is_link($link) || !$this->linkMatchesTarget($link, $target)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	public function install(PackageInterface $package) {
		$installPath = $this->getInstallPath($package);
		$this->downloadManager->download($package, $installPath);

		$this->linkTargets($this->getLinkTargets($package));
		$this->copyHtAccess($installPath);
	}

	public function update(PackageInterface $initial, PackageInterface $target) {
		$installPath = $this->getInstallPath($target);
		$downloadPath = $installPath . '-new-' . microtime();

		$this->downloadManager->download($target, $downloadPath);

		if (file_exists($installPath)) {
			$deprecatedPath = $installPath . '-old-' . microtime();
			$this->filesystem->rename($installPath, $deprecatedPath);

			try {
				$this->filesystem->rename($downloadPath, $installPath);
			} catch (\Exception $e) {
				// Roll back before throwing the exception
				$this->filesystem->rename($deprecatedPath, $installPath);
				$this->filesystem->remove($downloadPath);
				throw new \Exception(
					$e->getMessage() . ' (update was rolled back)',
					$e->getCode(),
					$e
				);
			}

			$this->filesystem->remove($deprecatedPath);
		}

		$this->linkTargets($this->getLinkTargets($target));
	}

	public function uninstall(\Composer\Package\PackageInterface $package) {
		$path = $this->getInstallPath($package);
		foreach (array_reverse($this->getLinkTargets($package)) as $link => $target) {
			$this->filesystem->remove($link);
			$this->filesystem->remove($target);
		}

		while ($path != $this->vendorDir) {
			$nextPath = dirname($path);
			if ($nextPath != $this->vendorDir || !glob($path . '/*')) {
				$this->filesystem->remove($path);
			}
			$path = $nextPath;
		}
	}
}
?>