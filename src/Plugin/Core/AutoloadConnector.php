<?php
namespace TYPO3\CMS\Composer\Plugin\Core;

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
use Composer\Semver\Constraint\EmptyConstraint;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Creates a symlink of the central autoload.php file in the vendor directory of the TYPO3 core package
 * If symlinking is not possible, a proxy file is created, which requires the autoload file in the vendor directory
 * Nothing is done if the composer.json of typo3/cms is the root.
 *
 * @author Helmut Hummel <info@helhum.io>
 */
class AutoloadConnector
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(IOInterface $io = null, Composer $composer = null, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->filesystem = $this->filesystem ?: new Filesystem();
    }

    public function linkAutoLoader($event = null)
    {
        if ($this->composer === null) {
            // Old plugin called this method, let's be graceful
            $this->composer = $event->getComposer();
            $this->io = $event->getIO();
            $this->io->writeError('<warning>TYPO3 Composer Plugin incomplete update detected.</warning>');
            $this->io->writeError('<warning>To fully upgrade to the new TYPO3 Composer Plugin, call "composer update" again.</warning>');
        }

        if ($this->composer->getPackage()->getName() === 'typo3/cms') {
            // Nothing to do typo3/cms is root package
            return;
        }

        $this->io->writeError('<info>Writing TYPO3 autoload proxy</info>', true, IOInterface::VERBOSE);

        $composerConfig = $this->composer->getConfig();
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('typo3/cms', new EmptyConstraint());

        $defaultVendorDir = \Composer\Config::$defaultConfig['vendor-dir'];

        $packagePath = $this->composer->getInstallationManager()->getInstallPath($package);
        $jsonFile = new \Composer\Json\JsonFile($packagePath . DIRECTORY_SEPARATOR . 'composer.json', new \Composer\Util\RemoteFilesystem($this->io));
        $packageJson = $jsonFile->read();
        $packageVendorDir = !empty($packageJson['config']['vendor-dir']) ? $this->filesystem->normalizePath($packageJson['config']['vendor-dir']) : $defaultVendorDir;

        $autoLoaderSourceDir = $composerConfig->get('vendor-dir');
        $autoLoaderTargetDir = "$packagePath/$packageVendorDir";
        $autoLoaderFileName = 'autoload.php';

        $this->filesystem->ensureDirectoryExists($autoLoaderTargetDir);
        $this->filesystem->remove("$autoLoaderTargetDir/$autoLoaderFileName");
        $code = array(
            '<?php',
            'return require ' . $this->filesystem->findShortestPathCode(
                "$autoLoaderTargetDir/$autoLoaderFileName",
                "$autoLoaderSourceDir/$autoLoaderFileName"
            ) . ';'
        );
        file_put_contents("$autoLoaderTargetDir/$autoLoaderFileName", implode(chr(10), $code));
    }
}
