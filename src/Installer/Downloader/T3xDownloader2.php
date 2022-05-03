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
use React\Promise\PromiseInterface;
use TYPO3\CMS\Composer\Plugin\Util\T3xDownloaderUtility;
use function React\Promise\resolve;

/**
 * TYPO3 CMS Extension Downloader
 * Extracts the TYPO3 CMS T3X Format
 */
class T3xDownloader2 extends ArchiveDownloader implements ChangeReportInterface
{
    // the method parameters are carefully typed
    // composer <= 2.2 has no type annotations and runs with php 7.0+
    // composer >= 2.3 has type annotations and runs with php 7.2+
    // decreasing the strictness of the method signature is only allowed in php 7.2+
    // but the parent class only has type annotations with composer 2.3 and that composer runs with php 7.2+
    // so this should work in all composer versions that match their respective php version

    /**
     * {@inheritDoc}
     * @noinspection PhpHierarchyChecksInspection
     */
    protected function extract(PackageInterface $package, $file, $path): PromiseInterface
    {
        T3xDownloaderUtility::extract($package, $file, $path);
        return resolve();
    }

    /**
     * {@inheritDoc}
     * @noinspection PhpHierarchyChecksInspection
     */
    public function getLocalChanges(PackageInterface $package, $path): ?string
    {
        T3xDownloaderUtility::getLocalChanges($package, $path);
    }
}
