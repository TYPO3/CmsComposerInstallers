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
use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\AutoloadConnector;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\WebDirectory;

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
     * Array of callables that are executed after autoload dump
     *
     * @var array
     */
    private static $installerScripts = [];

    /**
     * @param InstallerScript $script The callable that will be executed
     * @param int $priority Higher priority results in earlier execution
     */
    public static function addInstallerScript(InstallerScript $script, $priority = 50)
    {
        self::$installerScripts[$priority][] = $script;
    }

    /**
     * ScriptDispatcher constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return bool
     */
    public function executeScripts()
    {
        $io = $this->event->getIO();
        $this->registerLoader();

        if (empty(self::$installerScripts)) {
            // Fallback to traditional handling for compatibility
            self::addInstallerScript(new WebDirectory());
            self::addInstallerScript(new AutoloadConnector());
        }

        ksort(self::$installerScripts, SORT_NUMERIC);
        $io->writeError('<info>Executing TYPO3 installer scripts</info>', true, $io::VERBOSE);
        foreach (array_reverse(self::$installerScripts) as $scripts) {
            /** @var InstallerScript $script */
            foreach ($scripts as $script) {
                if (!$script->run($this->event)) {
                    break 2;
                }
            }
        }
        $this->unRegisterLoader();
        return true;
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
        if (!empty($map['psr-4']) && is_array($map['psr-4'])) {
            $this->registerInstallerScripts(array_keys($map['psr-4']));
        }
    }

    private function registerInstallerScripts(array $psr4Namespaces)
    {
        foreach ($psr4Namespaces as $psr4Namespace) {
            /** @var InstallerScriptsRegistration $scriptsRegistrationCandidate */
            $scriptsRegistrationCandidate = $psr4Namespace . 'Composer\\InstallerScripts';
            if (
                class_exists($scriptsRegistrationCandidate)
                && in_array(InstallerScriptsRegistration::class, class_implements($scriptsRegistrationCandidate), true)
            ) {
                $scriptsRegistrationCandidate::register($this->event);
            }
        }
    }

    private function unRegisterLoader()
    {
        $this->loader->unregister();
    }
}
