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

use TYPO3\CMS\Composer\Installer\CoreInstaller;

/**
 * Test for the copy driver (also used as base for the symlink driver test)
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
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

	protected function assertFilesCorrectlyCopied($package, $htaccess = TRUE) {
		foreach ($this->typo3Files as $file) {
			if (!$htaccess && ltrim($file, '_') == '.htaccess') {
				continue;
			}
			$path = $this->cwd . '/' . ltrim($file, '_');
			$this->assertFileExists($path);
			$this->assertEquals($this->getFixtureContent($file, $package), file_get_contents($path));
		}
	}

	public function testInstall() {
		$package = $this->getPackageMock();
		$this->driver->install($package);
		$this->assertFilesCorrectlyCopied($package);
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
		$initial = $this->getPackageMock();
		$this->driver->install($initial);
		$this->assertFilesCorrectlyCopied($initial);
		$target = $this->getPackageMock('1.1.0');
		$this->driver->update($initial, $target);
		$this->assertFilesCorrectlyCopied($target, FALSE);
	}
}
?>