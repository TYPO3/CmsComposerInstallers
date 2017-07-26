<?php
namespace TYPO3\CMS\Composer\Plugin\Core\InstallerScripts;

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
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Semver\Constraint\EmptyConstraint;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Setting up TYPO3 web directory by creating symlinks
 */
class WebDirectory implements InstallerScript
{
    const TYPO3_DIR = 'typo3';
    const TYPO3_INDEX_PHP = 'index.php';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var array
     */
    private $symlinks = [];

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $pluginConfig;

    public function run(Event $event): bool
    {
        $this->io = $event->getIO();
        $this->filesystem = new Filesystem();
        $this->composer = $event->getComposer();
        $this->pluginConfig = Config::load($this->composer);

        $this->initializeSymlinks();
        if ($this->filesystem->someFilesExist($this->symlinks)) {
            $this->filesystem->removeSymlinks($this->symlinks);
        }
        $this->filesystem->establishSymlinks($this->symlinks, false);
        return true;
    }

    /**
     * Initialize symlinks with configuration
     */
    private function initializeSymlinks()
    {
        if ($this->pluginConfig->get('prepare-web-dir') === false) {
            $this->io->writeError('<warning>Config option prepare-web-dir has been deprecated.</warning>');
            $this->io->writeError(' <warning>It will be removed with typo3/cms-composer-installers 2.0.</warning>');
            return;
        }
        $this->io->writeError('<info>Establishing links to TYPO3 entry scripts in web directory.</info>', true, IOInterface::VERBOSE);

        $relativeWebDir = $this->pluginConfig->get('web-dir', $this->pluginConfig::RELATIVE_PATHS);
        if (empty($relativeWebDir) || $relativeWebDir === '.') {
            $this->io->writeError('<warning>Setting web-dir to the composer root directory is highly discouraged for security reasons.</warning>');
            $this->io->writeError(' <warning>The default value for this option will change to "web" as of typo3/cms-composer-installers 2.0.</warning>');
        }
        $webDir = $this->filesystem->normalizePath($this->pluginConfig->get('web-dir'));
        $this->filesystem->ensureDirectoryExists($webDir);
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('typo3/cms', new EmptyConstraint());

        $sourcesDir = $this->composer->getInstallationManager()->getInstallPath($package);
        $backendDir = $webDir . DIRECTORY_SEPARATOR . self::TYPO3_DIR;
        $this->symlinks = [
            $sourcesDir . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP => $webDir . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP,
            $sourcesDir . DIRECTORY_SEPARATOR . self::TYPO3_DIR => $backendDir,
        ];
    }
}
