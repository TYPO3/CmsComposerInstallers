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
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Creates a symlink of the central autoload.php file in the vendor directory of the TYPO3 core package
 * If symlinking is not possible, a proxy file is created, which requires the autoload file in the vendor directory
 * Nothing is done if the composer.json of typo3/cms is the root.
 *
 * @author Helmut Hummel <info@helhum.io>
 */
class AutoloadConnector implements InstallerScript
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $this->filesystem ?: new Filesystem();
    }

    public function run(Event $event): bool
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $io->writeError('<info>Writing TYPO3 autoload proxy</info>', true, IOInterface::VERBOSE);

        $composerConfig = $composer->getConfig();
        $localRepository = $composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('typo3/cms', new EmptyConstraint());

        $defaultVendorDir = \Composer\Config::$defaultConfig['vendor-dir'];

        $packagePath = $composer->getInstallationManager()->getInstallPath($package);
        $jsonFile = new \Composer\Json\JsonFile($packagePath . DIRECTORY_SEPARATOR . 'composer.json', new \Composer\Util\RemoteFilesystem($io));
        $packageJson = $jsonFile->read();
        $packageVendorDir = !empty($packageJson['config']['vendor-dir']) ? $this->filesystem->normalizePath($packageJson['config']['vendor-dir']) : $defaultVendorDir;

        $autoLoaderSourceDir = $composerConfig->get('vendor-dir');
        $autoLoaderTargetDir = "$packagePath/$packageVendorDir";
        $autoLoaderFileName = 'autoload.php';

        $this->filesystem->ensureDirectoryExists($autoLoaderTargetDir);
        $this->filesystem->remove("$autoLoaderTargetDir/$autoLoaderFileName");
        $code = [
            '<?php',
            'return require ' . $this->filesystem->findShortestPathCode(
                "$autoLoaderTargetDir/$autoLoaderFileName",
                "$autoLoaderSourceDir/$autoLoaderFileName"
            ) . ';',
        ];
        return false !== file_put_contents("$autoLoaderTargetDir/$autoLoaderFileName", implode(chr(10), $code));
    }
}
