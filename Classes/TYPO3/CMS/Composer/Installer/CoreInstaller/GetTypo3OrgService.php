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

namespace TYPO3\CMS\Composer\Installer\CoreInstaller;

/**
 * A service that enriches the packages with information from get.typo3.org
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class GetTypo3OrgService
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param \Composer\IO\IOInterface $io
     * @param string $jsonUrl
     */
    public function __construct(\Composer\IO\IOInterface $io, $jsonUrl = 'https://get.typo3.org/json')
    {
        $this->file = new \Composer\Json\JsonFile($jsonUrl, new \Composer\Util\RemoteFilesystem($io));
    }

    protected function initializeData()
    {
        if (empty($this->data)) {
            $this->data = $this->file->read();
        }
    }

    /**
     * @param \Composer\Package\Package $package
     */
    public function addDistToPackage(\Composer\Package\Package $package)
    {
        $this->initializeData();
        $versionDigits = explode('.', $package->getPrettyVersion());
        if (count($versionDigits) === 3) {
            $branchVersion = $versionDigits[0] . '.' . $versionDigits[1];
            $patchlevelVersion = $versionDigits[0] . '.' . $versionDigits[1] . '.' . $versionDigits[2];
            if (isset($this->data[$branchVersion]) && isset($this->data[$branchVersion]['releases'][$patchlevelVersion])) {
                $releaseData = $this->data[$branchVersion]['releases'][$patchlevelVersion];
                if (isset($releaseData['checksums']['tar']['sha1']) && isset($releaseData['url']['tar'])) {
                    $package->setDistType('tar');
                    $package->setDistReference($patchlevelVersion);
                    $package->setDistUrl($releaseData['url']['tar']);
                    $package->setDistSha1Checksum($releaseData['checksums']['tar']['sha1']);
                }
            }
        }
    }
}
