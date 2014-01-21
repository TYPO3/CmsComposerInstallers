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

class SymlinkDriverTest extends CopyDriverTest {
	protected function createDriver() {
		return new CoreInstaller\SymlinkDriver($this->io, $this->composer, $this->filesystem);
	}

	public function testInstall() {
		parent::testInstall();

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
			$this->assertEquals(realpath($this->cwd . '/' . $target), realpath($link));
		}
	}
}
?>