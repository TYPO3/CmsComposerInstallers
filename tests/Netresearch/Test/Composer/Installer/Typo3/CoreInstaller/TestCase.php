<?php
namespace Netresearch\Test\Composer\Installer\Typo3\CoreInstaller;

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

class TestCase extends \PHPUnit_Framework_TestCase {
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
			file_put_contents($path . '/' . $file, $file);
		}
	}

	/**
	 * @return \Composer\Package\Package
	 */
	protected function getPackageMock() {
		return new \Composer\Package\Package('typo3/cms', '1.0.0.0', '1.0.0');
	}
}
?>