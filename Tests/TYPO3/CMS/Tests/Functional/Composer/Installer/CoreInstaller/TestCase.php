<?php
namespace TYPO3\CMS\Tests\Functional\Composer\Installer\CoreInstaller;

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
 * Base test case for all core installer tests
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \Composer\Util\Filesystem
	 */
	protected $filesystem;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $io;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $composer;

	protected $cwd;

	protected $typo3Files = array(
		'typo3/index.php',
		'index.php',
		'_.htaccess'
	);

	public function setUp() {
		$this->filesystem = new \Composer\Util\Filesystem;

		$this->getMock('Composer\Util\Filesystem');

		$configStub = $this->getMock('Composer\Config', array('get'));
		$configStub
			->expects($this->any())
			->method('get')
			->will($this->returnValueMap(array(
				array('vendor-dir', 'vendor')
			)));

		$downloadManagerStub = $this->getMock('Composer\Downloader\DownloaderInterface');
		$downloadManagerStub
			->expects($this->any())
			->method('download')
			->will($this->returnCallback(array($this, 'downloadManagerDownload')));

		$composerStub = $this->getMock('Composer\Composer', array('getConfig', 'getDownloadManager'));
		$composerStub
			->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($configStub));
		$composerStub
			->expects($this->any())
			->method('getDownloadManager')
			->will($this->returnValue($downloadManagerStub));

		$this->composer = $composerStub;
		$this->io = $this->getMock('Composer\IO\IOInterface');
	}

	public function tearDown() {
		parent::tearDown();
		if ($this->cwd) {
			$this->filesystem->remove($this->cwd);
		}
	}


	protected function prepareCwd() {
		$this->cwd = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'typo3-copydriver-test';
		$this->filesystem->remove($this->cwd);
		$this->filesystem->ensureDirectoryExists($this->cwd);
	}


	public function downloadManagerDownload($package, $path) {
		$this->filesystem->ensureDirectoryExists($path);
		foreach ($this->typo3Files as $file) {
			$dir = dirname($file);
			if ($dir) {
				$this->filesystem->ensureDirectoryExists($path . '/' . $dir);
			}
			file_put_contents($path . '/' . $file, $this->getFixtureContent($file, $package));
		}
	}

	/**
	 * @return \Composer\Package\Package
	 */
	protected function getPackageMock($version = '1.0.0') {
		return new \Composer\Package\Package('typo3/cms', $version . '.0', $version);
	}

	protected function getFixtureContent($file, \Composer\Package\Package $package) {
		return $file . '-' . $package->getName() . '-' . $package->getVersion();
	}
}
?>