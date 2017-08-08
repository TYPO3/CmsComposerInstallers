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

use Composer\IO\IOInterface;
use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Core\IncludeFile\TokenInterface;

class IncludeFile
{
    const INCLUDE_FILE = '/typo3/autoload-include.php';
    const INCLUDE_FILE_TEMPLATE = '/res/php/autoload-include.tmpl.php';

    /**
     * @var TokenInterface[]
     */
    private $tokens;

    /**
     * @param TokenInterface[] $tokens
     */
    public function __construct(TokenInterface ...$tokens)
    {
        $this->tokens = $tokens;
    }

    public function register(Event $event)
    {
        $io = $event->getIO();
        $composer = $event->getComposer();

        $io->writeError('<info>Register typo3/cms-composer-installer file in root package autoload definition</info>', true, IOInterface::VERBOSE);

        // Generate and write the file
        $includeFile = $composer->getConfig()->get('vendor-dir') . self::INCLUDE_FILE;
        file_put_contents($includeFile, $this->getIncludeFileContent());

        // Register the file in the root package
        $rootPackage = $composer->getPackage();
        $autoloadDefinition = $rootPackage->getAutoload();
        $autoloadDefinition['files'][] = $includeFile;
        $rootPackage->setAutoload($autoloadDefinition);

        // Load it to expose the paths to further plugin functionality
        require $includeFile;
    }

    /**
     * Constructs the include file content
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getIncludeFileContent(): string
    {
        $includeFileTemplate = dirname(__DIR__, 3) . self::INCLUDE_FILE_TEMPLATE;
        $includeFileContent = file_get_contents($includeFileTemplate);
        foreach ($this->tokens as $token) {
            $includeFileContent = self::replaceToken($token->getName(), $token->getContent(), $includeFileContent);
        }
        return $includeFileContent;
    }

    /**
     * Replaces a token in the subject (PHP code)
     *
     * @param string $name
     * @param string $content
     * @param string $subject
     * @return string
     */
    private static function replaceToken($name, $content, $subject): string
    {
        return str_replace('\'{$' . $name . '}\'', $content, $subject);
    }
}
