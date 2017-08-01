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

use Composer\EventDispatcher\ScriptExecutionException;
use Composer\IO\NullIO;
use Composer\Script\Event;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * @deprecated will be removed with 2.0
 */
class AutoloadConnector
{
    /**
     * @param Event $event
     */
    public function linkAutoloader(Event $event)
    {
        $io = $event->getIO();
        $process = new ProcessExecutor(new NullIO());

        $finder = new PhpExecutableFinder();
        $phpPath = $finder->find();
        if (!$phpPath) {
            throw new \RuntimeException('Failed to locate PHP binary to execute composer dump-autoload');
        }
        $exec = $phpPath . '  ' . realpath($_SERVER['argv'][0]) . ' dump-autoload';
        if (0 !== ($exitCode = $process->execute($exec))) {
            $io->writeError('<error>Could not gracefully recover from typo3/cms-composer-installers upgrade. Please re-run the composer command.</error>');
            throw new ScriptExecutionException('Error Output: '.$process->getErrorOutput(), $exitCode);
        }
        $io->writeError('<info>Gracefully upgraded to typo3/cms-composer-installers 1.4.x</info>', true, $io::VERBOSE);
    }
}
