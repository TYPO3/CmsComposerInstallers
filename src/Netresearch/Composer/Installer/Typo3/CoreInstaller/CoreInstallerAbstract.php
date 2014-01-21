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

/**
 * Installer for TYPO3 CMS
 *
 * @author Christian Opitz
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