<?php
namespace TYPO3\CMS\Tests\Functional\Composer\Installer;

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
 * Test for the core installer
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class CoreInstallerTest extends CoreInstaller\TestCase {
	/**
	 * @var TYPO3\CMS\Composer\Installer\CoreInstaller
	 */
	protected $installer;

	public function setUp() {
		parent::setUp();
		$this->installer = new \TYPO3\CMS\Composer\Installer\CoreInstaller($this->io, $this->composer);
	}

	public function testCorrectDriverIsSelected() {
		$mocks = array();
		for ($i = 0; $i < 2; $i++) {
			$mocks[] = $this->getDriverMock();
		}

		$mocks[0]
			->expects($this->exactly(2))
			->method('isPossible')
			->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mocks[1]
			->expects($this->exactly(1))
			->method('isPossible')
			->will($this->onConsecutiveCalls(TRUE, FALSE));

		$this->installer->setAvailableDrivers($mocks);

		// No driver set - getDriver searches possible driver
		$this->assertEquals($mocks[0], $this->installer->getDriver());

		$this->installer->setDriver();

		// No driver set - getDriver searches possible driver
		$this->assertEquals($mocks[0], $this->installer->getDriver());

		$this->installer->setDriver($mocks[0]);

		// Driver $mocks[0] set - getDriver won't search possible driver
		$this->assertEquals($mocks[0], $this->installer->getDriver());
	}

	/**
	 * @expectedException TYPO3\CMS\Composer\Installer\CoreInstaller\NoDriverFoundException
	 */
	public function testExceptionIsThrownWhenNoDriverWasSet() {
		$this->installer->setAvailableDrivers(array());
		$this->installer->getDriver();
	}

	/**
	 * @expectedException TYPO3\CMS\Composer\Installer\CoreInstaller\DriverMissingInterfaceException
	 */
	public function testExceptionIsThrownWhenWrongDriverClassWasSet() {
		$this->installer->setAvailableDrivers(array('stdClass'));
		$this->installer->getDriver();
	}

	public function testDriverIsInstantiatedFromString() {
		$mock = $this->getDriverMock();

		$this->installer->setAvailableDrivers(array(get_class($mock)));

		try {
			$this->installer->getDriver();
			$this->fail('No driver should be found');
		} catch (\TYPO3\CMS\Composer\Installer\CoreInstaller\NoDriverFoundException $ex) {
			$drivers = $this->installer->getAvailableDrivers();
			$this->assertInstanceOf(get_class($mock), $drivers[0], 'Driver wasn\'t instantiated');
		}
	}

	public function testInstall() {
		$driver = $this->getDriverMock();
		$driver->expects($this->once())->method('install');

		$repo = $this->getRepositoryMock();
		$repo->expects($this->once())->method('addPackage');
		$repo->expects($this->once())->method('hasPackage');

		$this->installer->setDriver($driver);
		$this->installer->install($repo, $this->getPackageMock());
	}

	public function testUninstall() {
		$driver = $this->getDriverMock();
		$driver
			->expects($this->once())
			->method('uninstall');

		$repo = $this->getRepositoryMock();
		$repo->expects($this->once())->method('hasPackage')->will($this->returnValue(TRUE));
		$repo->expects($this->once())->method('removePackage');

		$this->installer->setDriver($driver);
		$this->installer->uninstall($repo, $this->getPackageMock());
	}

	/**
	 * @expectedException TYPO3\CMS\Composer\Installer\CoreInstaller\PackageNotInstalledException
	 */
	public function testUninstallNotInstalledPackage() {
		$repo = $this->getRepositoryMock();
		$repo->expects($this->once())->method('hasPackage')->will($this->returnValue(FALSE));
		$this->installer->uninstall($repo, $this->getPackageMock());
	}

	public function testUpdate() {
		$driver = $this->getDriverMock();
		$driver
			->expects($this->once())
			->method('update');

		$repo = $this->getRepositoryMock();
		$repo->expects($this->exactly(2))->method('hasPackage')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$repo->expects($this->once())->method('removePackage');
		$repo->expects($this->once())->method('addPackage');

		$this->installer->setDriver($driver);
		$this->installer->update($repo, $this->getPackageMock(), $this->getPackageMock());
	}

	/**
	 * @expectedException TYPO3\CMS\Composer\Installer\CoreInstaller\PackageNotInstalledException
	 */
	public function testUpdateNotInstalledPackage() {
		$repo = $this->getRepositoryMock();
		$repo->expects($this->once())->method('hasPackage')->will($this->returnValue(FALSE));
		$this->installer->update($repo, $this->getPackageMock(), $this->getPackageMock());
	}

	public function testIsInstalled() {
		$driver = $this->getDriverMock();
		$driver
			->expects($this->once())
			->method('isInstalled')
			->will($this->returnValue(TRUE));

		$repo = $this->getRepositoryMock();
		$repo->expects($this->exactly(2))->method('hasPackage')->will($this->onConsecutiveCalls(FALSE, TRUE));

		$this->assertFalse($this->installer->isInstalled($repo, $this->getPackageMock()));

		$this->installer->setDriver($driver);
		$this->assertTrue($this->installer->isInstalled($repo, $this->getPackageMock()));
	}

	public function testGetInstallPath() {
		$driver = $this->getDriverMock();
		$driver
			->expects($this->once())
			->method('getInstallPath')
			->will($this->returnValue($path = 'some/phantasy/path'));

		$this->installer->setDriver($driver);
		$this->assertEquals($path, $this->installer->getInstallPath($this->getPackageMock()));
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getDriverMock() {
		return $this->getMockForAbstractClass(
			'TYPO3\CMS\Composer\Installer\CoreInstaller\CoreInstallerInterface'
		);
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getRepositoryMock() {
		return $this->getMock('Composer\Repository\InstalledRepositoryInterface');
	}
}
?>