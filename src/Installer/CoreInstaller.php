<?php
namespace TYPO3\CMS\Composer\Installer;

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

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

/**
 * typo3/cms installer
 *
 * We deny installation of typo3/cms via composer
 *
 * Instead individual packages should be used and / or typo3/minimal
 */
class CoreInstaller extends LibraryInstaller
{
    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @throws \RuntimeException
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        $this->io->writeError('<warning>Installation of typo3/cms is not possible any more for TYPO3 versions 9.0.0 and higher.</warning>');
        $this->io->writeError('<warning>Please require typo3/minimal instead for minimum required TYPO3 system extensions,</warning>');
        $this->io->writeError('<warning>and/or require individual system extensions like typo3/cms-extension-name</warning>');
        $this->io->writeError('<warning>E.g. composer require typo3/cms-tstemplate</warning>');

        throw new \RuntimeException('Installation stopped.', 1513083851);
    }
}
