<?php

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\ComposerTest\Installer\Downloader;

use Composer\Composer;
use Composer\Downloader\ChangeReportInterface;
use Composer\Downloader\DownloaderInterface;
use Composer\IO\BufferIO;
use Composer\Util\HttpDownloader;
use TYPO3\CMS\Composer\Installer\Downloader\T3xDownloader;
use TYPO3\CMS\Composer\Installer\Downloader\T3xDownloader2;
use TYPO3\CMS\ComposerTest\Installer\InstallerTestCase;

class T3xDownloaderTest extends InstallerTestCase
{
    public function testDownloaderCompatibility()
    {
        $io = new BufferIO();
        if (version_compare(Composer::RUNTIME_API_VERSION, '2.0.0') < 0) {
            $t3xDownloader = new T3xDownloader($io, $this->composer->getConfig());
        } else {
            $httpDownloader = new HttpDownloader($io, $this->composer->getConfig());
            $t3xDownloader = new T3xDownloader2($io, $this->composer->getConfig(), $httpDownloader);
        }
        $this->assertInstanceOf(ChangeReportInterface::class, $t3xDownloader);
        $this->assertInstanceOf(DownloaderInterface::class, $t3xDownloader);
    }
}
