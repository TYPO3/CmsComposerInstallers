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

use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Config;
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

    /**
     * @param Event $event
     */
    public function linkAutoloader(Event $event)
    {
        $composer = $event->getComposer();
        $composerConfig = $composer->getConfig();
        $localRepository = $composer->getRepositoryManager()->getLocalRepository();

        foreach ($localRepository->getCanonicalPackages() as $package) {
            if ($package->getType() === 'typo3-cms-core') {
                $defaultVendorDir = \Composer\Config::$defaultConfig['vendor-dir'];

                $packagePath = $composer->getInstallationManager()->getInstallPath($package);
                $jsonFile = new \Composer\Json\JsonFile($packagePath . DIRECTORY_SEPARATOR . 'composer.json', new \Composer\Util\RemoteFilesystem($event->getIO()));
                $packageJson = $jsonFile->read();
                $packageVendorDir = !empty($packageJson['config']['vendor-dir']) ? $this->filesystem->normalizePath($packageJson['config']['vendor-dir']) : $defaultVendorDir;

                $autoloaderSourceDir = $composerConfig->get('vendor-dir');
                $autoloaderTargetDir = $packagePath . DIRECTORY_SEPARATOR . $packageVendorDir;
                $autoloaderFileName = 'autoload.php';

                $this->filesystem->ensureDirectoryExists($autoloaderTargetDir);
                $this->filesystem->remove($autoloaderTargetDir . DIRECTORY_SEPARATOR . $autoloaderFileName);
                try {
                    $this->filesystem->symlink(
                        $autoloaderSourceDir . DIRECTORY_SEPARATOR . $autoloaderFileName,
                        $autoloaderTargetDir . DIRECTORY_SEPARATOR . $autoloaderFileName,
                        false
                    );
                } catch (\RuntimeException $e) {
                    if ($e->getCode() !== 1430494084) {
                        throw $e;
                    }
                    $code = array(
                        '<?php',
                        'return require ' . $this->filesystem->findShortestPathCode(
                            $autoloaderTargetDir . DIRECTORY_SEPARATOR . $autoloaderFileName,
                            $autoloaderSourceDir . DIRECTORY_SEPARATOR . $autoloaderFileName
                        ) . ';'
                    );
                    file_put_contents($autoloaderTargetDir . DIRECTORY_SEPARATOR . $autoloaderFileName, implode(chr(10), $code));
                }
                $this->insertComposerModeConstant($event);
                break;
            }
        }
    }

    /**
     * @param Event $event
     */
    protected function insertComposerModeConstant(Event $event)
    {
        $composer = $event->getComposer();
        $pluginConfig = Config::load($composer);
        if (!$pluginConfig->get('composer-mode')) {
            return;
        }
        $composerConfig = $composer->getConfig();
        $vendorDir = $composerConfig->get('vendor-dir');
        $autoloadFile = $vendorDir . '/autoload.php';
        $io = $event->getIO();
        if (!file_exists($autoloadFile)) {
            throw new \RuntimeException(sprintf(
                'Could not adjust autoloader: The file %s was not found.',
                $autoloadFile
            ));
        }

        $io->write('<info>Inserting TYPO3_COMPOSER_MODE constant</info>');

        $contents = file_get_contents($autoloadFile);
        $constant = "if (!defined('TYPO3_COMPOSER_MODE')) {\n";
        $constant .= "	define('TYPO3_COMPOSER_MODE', TRUE);\n";
        $constant .= "}\n\n";

        // Regex modifiers:
        // "m": \s matches newlines
        // "D": $ matches at EOF only
        // Translation: insert before the last "return" in the file
        $contents = preg_replace('/\n(?=return [^;]+;\s*$)/mD', "\n" . $constant, $contents);
        file_put_contents($autoloadFile, $contents);
    }
}
