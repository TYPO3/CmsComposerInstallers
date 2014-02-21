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

/**
 * Installer for TYPO3 CMS
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
abstract class CoreInstallerAbstract {

	const TYPO3_DIR = 'typo3';
	const TYPO3_SRC_DIR = 'typo3_src';
	const TYPO3_INDEX_PHP = 'index.php';

	protected $cwd;

	protected $vendorDir;

	/**
	 * @var Filesystem
	 */
	protected $filesystem;

	/**
	 * @var IOInterface
	 */
	protected $io;

	/**
	 * @var Downloader\DownloadManager
	 */
	protected $downloadManager;

	/**
	 * @var Composer
	 */
	protected $composer;

	public function __construct(IOInterface $io, Composer $composer, Filesystem $filesystem = NULL) {
		$this->composer = $composer;
		$this->downloadManager = $composer->getDownloadManager();
		$this->io = $io;
		$this->filesystem = $filesystem ?: new Filesystem();

		$this->changeDirectory('.');
	}

	public function changeDirectory($cwd) {
		$this->cwd = realpath($cwd);
		if (!$this->cwd) {
			throw new Exception($cwd . 'doesn\'t exist');
		}
		$this->vendorDir = rtrim($this->cwd . '/' . $this->composer->getConfig()->get('vendor-dir'), '/');
	}

	protected function copyHtAccess($fromPath) {
		$htAccessFromPath = rtrim($fromPath, '/') . '/_.htaccess';
		$error = '<warning>_.htaccess could not be %s - please copy it manually to application root</warning>';
		if (!file_exists($htAccessFromPath)) {
			$this->io->write(sprintf($error, 'found'));
		}
		$htAccessToPath = $this->cwd . '/.htaccess';
		if (!@copy($htAccessFromPath, $htAccessToPath)) {
			$this->io->write(sprintf($error, 'copied'));
		}
	}

	public function isPossible() {
		return TRUE;
	}
}
?>