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
use TYPO3\CMS\Composer\Installer\Util;

/**
 * Test for the symlink driver (reuses tests from copy driver test)
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class SymlinkDriverTest extends CopyDriverTest {
	protected function createDriver() {
		return new CoreInstaller\SymlinkDriver($this->io, $this->composer, $this->filesystem);
	}

	public function testInstall() {
		$package = $this->getPackageMock();
		$this->driver->install($package);

		$fromPath = $this->cwd;
		$toPath = $this->driver->getInstallPath($this->getPackageMock());
		$relativeToPath = $this->filesystem->findShortestPath($fromPath, $toPath);

		$links = array(
			'typo3_src' => $relativeToPath,
			'typo3' => 'typo3_src/typo3',
			'index.php' => 'typo3_src/index.php'
		);

		foreach ($links as $link => $target) {
			$link = $this->cwd . '/' . $link;
			$this->assertTrue(is_link($link));
			// Assert that the links are relative
			$this->assertEquals($target, readlink($link));
			// Assert the canonicalized links match their targets
			$this->assertEquals(Util::realpath($this->cwd . '/' . $target), Util::realpath($link));
		}
	}
}
?>