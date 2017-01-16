<?php
namespace TYPO3\CMS\Composer\Plugin\Core;

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

use Composer\Autoload\ClassLoader;
use Composer\IO\IOInterface;
use Composer\Script\Event;

class ScriptDispatcher
{
    /**
     * @var Event
     */
    private $event;

    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * ScriptDispatcher constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function executeScripts()
    {
        if (is_callable(['TYPO3\\CMS\\Core\\Composer\\InstallerScripts', 'setupTypo3'])) {
            $this->event->getIO()->writeError('<info>Executing typo3/cms package scripts</info>', true, IOInterface::VERBOSE);
            $this->registerLoader();
            \TYPO3\CMS\Core\Composer\InstallerScripts::setupTypo3($this->event);
            $this->unRegisterLoader();
        }
    }

    private function registerLoader()
    {
        $composer = $this->event->getComposer();
        $package = $composer->getPackage();
        $generator = $composer->getAutoloadGenerator();
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);
        $map = $generator->parseAutoloads($packageMap, $package);
        $this->loader = $generator->createLoader($map);
        $this->loader->register();
    }

    private function unRegisterLoader()
    {
        $this->loader->unregister();
    }
}
