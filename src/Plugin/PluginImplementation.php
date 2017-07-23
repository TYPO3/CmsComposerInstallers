<?php
namespace TYPO3\CMS\Composer\Plugin;

/*
 * This file was taken from the typo3 console plugin package.
 * (c) Helmut Hummel <info@helhum.io>
 *
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
use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Config as PluginConfig;
use TYPO3\CMS\Composer\Plugin\Core\AutoloadConnector;
use TYPO3\CMS\Composer\Plugin\Core\IncludeFile;
use TYPO3\CMS\Composer\Plugin\Core\IncludeFile\BaseDirToken;
use TYPO3\CMS\Composer\Plugin\Core\IncludeFile\ComposerModeToken;
use TYPO3\CMS\Composer\Plugin\Core\IncludeFile\WebDirToken;
use TYPO3\CMS\Composer\Plugin\Core\ScriptDispatcher;
use TYPO3\CMS\Composer\Plugin\Core\WebDirectory;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

class PluginImplementation
{
    /**
     * @var ScriptDispatcher
     */
    private $scriptDispatcher;

    /**
     * @var IncludeFile
     */
    private $includeFile;

    /**
     * @var AutoloadConnector
     */
    private $autoLoadConnector;

    /**
     * @var WebDirectory
     */
    private $webDirectory;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * PluginImplementation constructor.
     *
     * @param Event $event
     * @param ScriptDispatcher $scriptDispatcher
     * @param IncludeFile $includeFile
     * @param AutoloadConnector $autoLoadConnector
     */
    public function __construct(
        Event $event,
        ScriptDispatcher $scriptDispatcher = null,
        WebDirectory $webDirectory = null,
        IncludeFile $includeFile = null,
        AutoloadConnector $autoLoadConnector = null
    ) {
        $io = $event->getIO();
        $this->composer = $event->getComposer();
        $fileSystem = new Filesystem();
        $pluginConfig = PluginConfig::load($this->composer);

        $this->scriptDispatcher = $scriptDispatcher ?: new ScriptDispatcher($event);
        $this->autoLoadConnector = $autoLoadConnector ?: new AutoloadConnector($io, $this->composer, $fileSystem);
        $this->webDirectory = $webDirectory ?: new WebDirectory($io, $this->composer, $fileSystem, $pluginConfig);
        $this->includeFile = $includeFile
            ?: new IncludeFile(
                $io,
                $this->composer,
                [
                    new BaseDirToken($io, $pluginConfig),
                    new WebDirToken($io, $pluginConfig),
                    new ComposerModeToken($io, $pluginConfig),
                ],
                $fileSystem
            );
    }

    public function preAutoloadDump()
    {
        if ($this->composer->getPackage()->getName() === 'typo3/cms') {
            // Nothing to do typo3/cms is root package
            return;
        }
        $this->includeFile->register();
    }

    public function postAutoloadDump()
    {
        if (!$this->scriptDispatcher->executeScripts()) {
            // Fallback to traditional handling for compatibility
            $this->autoLoadConnector->linkAutoLoader();
            $this->webDirectory->ensureSymlinks();
        }
    }
}
