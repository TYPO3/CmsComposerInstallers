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

use Netresearch\Composer\Installer\Typo3\CoreInstaller;

class CopyDriverTest extends TestCase {
	/**
	 * @var CoreInstaller\CopyDriver
	 */
	protected $driver;

	public function setUp() {
		parent::setUp();
		$this->prepareCwd();
		$this->driver = $this->createDriver();
		$this->driver->changeDirectory($this->cwd);
	}

	protected function createDriver() {
		return new CoreInstaller\CopyDriver($this->io, $this->composer, $this->filesystem);
	}

	public function testInstall() {
		$this->driver->install($this->getPackageMock());
		foreach ($this->typo3Files as $file) {
			$path = $this->cwd . '/' . ltrim($file, '_');
			$this->assertFileExists($path);
			$this->assertEquals($file, file_get_contents($path));
		}
	}

	public function testIsInstalled() {
		$package = $this->getPackageMock();
		$this->driver->install($package);

		$this->assertTrue($this->driver->isInstalled($package));

		$this->filesystem->remove($this->cwd . '/typo3');

		$this->assertFalse($this->driver->isInstalled($package));
	}

	public function testUninstall() {
		$package = $this->getPackageMock();
		$this->driver->install($package);
		$this->driver->uninstall($package);

		foreach ($this->typo3Files as $file) {
			if ($file == '_.htaccess') {
				// .htaccess can/should stay
				continue;
			}
			$this->assertFalse(file_exists($this->cwd . '/' . $file));
		}
	}

	public function testUpdate() {
		$this->markTestIncomplete('Update is implemented but not tested as there are no updates yet');
	}
}
?>