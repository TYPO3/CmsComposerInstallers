<?php
declare(strict_types=1);
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
use Composer\Script\Event;
use Composer\Semver\Constraint\EmptyConstraint;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\AutoloadConnector;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\WebDirectory;

class ScriptDispatcher
{
    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * Array of callables that are executed after autoload dump
     *
     * @var InstallerScript[][]
     */
    private $installerScripts = [];

    /**
     * @param InstallerScript $script The callable that will be executed
     * @param int $priority Higher priority results in earlier execution
     */
    public function addInstallerScript(InstallerScript $script, $priority = 50)
    {
        $this->installerScripts[$priority][] = $script;
    }

    public function executeScripts(Event $event)
    {
        $io = $event->getIO();
        $this->registerLoader($event);
        $this->populateInstallerScripts($event);
        $io->writeError('<info>Executing TYPO3 installer scripts</info>', true, $io::VERBOSE);
        try {
            foreach ($this->installerScripts as $scripts) {
                foreach ($scripts as $script) {
                    $io->writeError(sprintf('<info>Executing "%s": </info>', get_class($script)), true, $io::DEBUG);
                    if (!$script->run($event)) {
                        $io->writeError(sprintf('<error>Executing "%s" failed.</error>', get_class($script)));
                    }
                }
            }
        } catch (StopInstallerScriptExecution $e) {
            // Just skip further script execution
        } finally {
            $this->unRegisterLoader();
        }
    }

    private function registerLoader(Event $event)
    {
        $map = $this->getAutoloadMap($event);
        $this->loader = $event->getComposer()->getAutoloadGenerator()->createLoader($map);
        $this->loader->register();
    }

    /**
     * @param Event $event
     */
    private function populateInstallerScripts(Event $event)
    {
        $composer = $event->getComposer();
        $this->registerInstallerScripts($event);
        if (// Fallback to traditional handling for compatibility
            empty($this->installerScripts) // But not if typo3/cms is root package or typo3/cms is not found at all
            && null !== $composer->getRepositoryManager()
                                 ->getLocalRepository()
                                 ->findPackage('typo3/cms', new EmptyConstraint())) {
            $this->addInstallerScript(new WebDirectory());
            $this->addInstallerScript(new AutoloadConnector());
        }
        ksort($this->installerScripts, SORT_NUMERIC);
        $this->installerScripts = array_reverse($this->installerScripts);
    }

    private function registerInstallerScripts(Event $event)
    {
        $map = $this->getAutoloadMap($event);
        if (empty($map['psr-4']) || !is_array($map['psr-4'])) {
            return;
        }
        $psr4Namespaces = array_keys($map['psr-4']);
        foreach ($psr4Namespaces as $psr4Namespace) {
            /** @var InstallerScriptsRegistration $scriptsRegistrationCandidate */
            $scriptsRegistrationCandidate = $psr4Namespace . 'Composer\\InstallerScripts';
            if (
                class_exists($scriptsRegistrationCandidate)
                && in_array(InstallerScriptsRegistration::class, class_implements($scriptsRegistrationCandidate), true)
            ) {
                $scriptsRegistrationCandidate::register($event, $this);
            }
        }
    }

    /**
     * @param Event $event
     * @return array
     */
    private function getAutoloadMap(Event $event): array
    {
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $generator = $composer->getAutoloadGenerator();
        $packages = $composer->getRepositoryManager()
                             ->getLocalRepository()
                             ->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);
        return $generator->parseAutoloads($packageMap, $package);
    }

    private function unRegisterLoader()
    {
        $this->loader->unregister();
    }
}
