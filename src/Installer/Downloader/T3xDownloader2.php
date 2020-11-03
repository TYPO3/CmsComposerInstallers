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

namespace TYPO3\CMS\Composer\Installer\Downloader;

use Composer\Downloader\ArchiveDownloader;
use Composer\Downloader\ChangeReportInterface;
use Composer\Package\PackageInterface;
use TYPO3\CMS\Composer\Util\T3xDownloaderUtility;

/**
 * TYPO3 CMS Extension Downloader
 * Extracts the TYPO3 CMS T3X Format
 */
class T3xDownloader2 extends ArchiveDownloader implements ChangeReportInterface
{
    /**
     * @param string $file path to the archive file
     * @param string $path path where the extension should be extracted to
     */
    protected function extract(PackageInterface $package, $file, $path)
    {
        T3xDownloaderUtility::extract($file, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalChanges(PackageInterface $package, $path)
    {
        return T3xDownloaderUtility::getLocalChanges($package, $path);
    }
}
