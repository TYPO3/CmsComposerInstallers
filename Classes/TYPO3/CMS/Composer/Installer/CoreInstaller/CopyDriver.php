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

/**
 * CopyDriver - used, when the FS doesn't support symlinks
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class CopyDriver extends CoreInstallerAbstract implements CoreInstallerInterface {

	protected function getFiles() {
		return array(self::TYPO3_DIR, self::TYPO3_INDEX_PHP);
	}

	public function install(PackageInterface $package) {
		$tempPath = $this->installOrUpdate($package);
		$this->copyHtAccess($tempPath);
		$this->filesystem->remove($tempPath);
	}

	public function update(PackageInterface $initial, PackageInterface $target) {
		$tempPath = $this->installOrUpdate($target, array($this, 'rollbackChanges'));
		$this->filesystem->remove($tempPath);
	}

	protected function installOrUpdate(PackageInterface $package, $rollbackCallback = NULL) {
		$backups = array();
		$backupAdd = '-old-' . microtime();

		$downloadPath = $this->getInstallPath($package);
		$tempPath = $downloadPath . '/' . self::TYPO3_SRC_DIR;

		$this->downloadManager->download($package, $tempPath);

		foreach ($this->getFiles() as $file) {
			$target = $downloadPath . '/' . $file;
			if (file_exists($target)) {
				if ($rollbackCallback) {
					$this->filesystem->rename(
						$target,
						$backups[$target] = $target . $backupAdd
					);
				} else {
					$this->filesystem->remove($target);
				}
			}
			try {
				$this->filesystem->rename($tempPath . '/' . $file, $target);
			} catch (\Exception $ex) {
				$msg = $rollbackCallback ? call_user_func($rollbackCallback, $backups) : '';
				throw new \Exception(
					$ex->getMessage() . $msg,
					$ex->getCode(),
					$ex
				);
			}
		}

		foreach ($backups as $target => $backup) {
			$this->filesystem->remove($backup);
		}

		return $tempPath;
	}


	protected function rollbackChanges($backups) {
		foreach ($backups as $target => $backup) {
			if (file_exists($target)) {
				$this->filesystem->remove($target);
			}
			$this->filesystem->rename($backup, $target);
		}
		return ' (updates rolled back)';
	}


	public function getInstallPath(PackageInterface $package) {
		return $this->cwd;
	}

	public function isInstalled(PackageInterface $package) {
		$installPath = $this->getInstallPath($package);
		foreach ($this->getFiles() as $file) {
			if (!file_exists($installPath . '/' . $file)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	public function uninstall(PackageInterface $package) {
		$installPath = $this->getInstallPath($package);
		foreach ($this->getFiles() as $file) {
			$this->filesystem->remove($installPath . '/' . $file);
		}
	}
}
?>