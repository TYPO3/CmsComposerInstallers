<?php
namespace TYPO3\CMS\Composer\Plugin\Util;

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

/**
 * An additional wrapper around filesystem
 */
class Filesystem extends \Composer\Util\Filesystem
{
    /**
     * @param array $files
     * @return bool
     */
    public function allFilesExist(array $files)
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $files
     * @return bool
     */
    public function someFilesExist(array $files)
    {
        foreach ($files as $file) {
            if (file_exists($file) || is_link($file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $links
     * @param bool $copyOnFailure
     * @param bool $makeRelative
     */
    public function establishSymlinks(array $links, $copyOnFailure = true, $makeRelative = true)
    {
        foreach ($links as $source => $target) {
            $this->symlink($source, $target, $copyOnFailure, $makeRelative);
        }
    }

    /**
     *
     */
    public function removeSymlinks(array $links)
    {
        foreach ($links as $target) {
            $this->remove($target);
        }
    }

    /**
     * @param string $source
     * @param string $target
     * @param bool $copyOnFailure
     * @param bool $makeRelative Create a relative link instead of an absolute
     */
    public function symlink($source, $target, $copyOnFailure = true, $makeRelative = true)
    {
        if (!file_exists($source)) {
            throw new \InvalidArgumentException('The symlink source "' . $source . '" is not available.');
        }
        if (file_exists($target)) {
            throw new \InvalidArgumentException('The symlink target "' . $target . '" already exists.');
        }
        // As Windows needs a relative path with backslashes, we ensure the proper directory separator is used
        $symlinkSource = strtr($makeRelative ? $this->findShortestPath($target, $source) : $source, '/', DIRECTORY_SEPARATOR);
        $symlinkTarget = strtr($target, '/', DIRECTORY_SEPARATOR);
        $symlinkSuccessfull = @symlink($symlinkSource, $symlinkTarget);
        $additionalErrorInformation = '';
        if (!$symlinkSuccessfull && !stristr(PHP_OS, 'darwin') && !stristr(PHP_OS, 'cygwin') && stristr(PHP_OS, 'win')) {
            // Try fallback to mklink for Windows, because symlink can't handle relative paths starting with "..\"
            $output = null;
            $parameter = is_dir($source) ? ' /D' : '';
            $symlinkSuccessfull = $this->getProcess()->execute('mklink' . $parameter . ' ' . escapeshellarg($symlinkTarget) . ' ' . escapeshellarg($symlinkSource), $output) === 0;
            if (!$symlinkSuccessfull) {
                $additionalErrorInformation = PHP_EOL . ' ' . PHP_EOL . 'Windows system detected - please ensure you are running the Composer command with administrator rights to be able to create symlinks.';
            }
        }
        if (!$symlinkSuccessfull && !$copyOnFailure) {
            throw new \RuntimeException('Symlinking target "' . $symlinkTarget . '" to source "' . $symlinkSource . '" ("' . $source . '")  failed.' . $additionalErrorInformation, 1430494084);
        } elseif (!$symlinkSuccessfull && $copyOnFailure) {
            try {
                $this->copy($symlinkSource, $symlinkTarget);
            } catch (\Exception $exception) {
                throw new \RuntimeException('Neither symlinking nor copying target "' . $symlinkTarget . '" to source "' . $symlinkSource . '" ("' . $source . '") worked.' . $additionalErrorInformation, 1430494090);
            }
        }
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return void
     */
    public function copy($source, $target)
    {
        if (!file_exists($source)) {
            throw new \RuntimeException('The source "' . $source . '" does not exist and cannot be copied.');
        }
        if (is_file($source)) {
            $this->ensureDirectoryExists(dirname($target));
            $this->copyFile($source, $target);
            return;
        } elseif (is_dir($source)) {
            $this->copyDirectory($source, $target);
            return;
        }
        throw new \RuntimeException('The source "' . $source . '" is neither a file nor a directory.');
    }

    /**
     * @param string $source
     * @param string $target
     */
    protected function copyFile($source, $target)
    {
        $copySuccessful = @copy($source, $target);
        if (!$copySuccessful) {
            throw new \RuntimeException('The source "' . $source . '" could not be copied to target "' . $target . '".');
        }
    }

    /**
     * @param string $source
     * @param string $target
     */
    protected function copyDirectory($source, $target)
    {
        $iterator = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $this->ensureDirectoryExists($target);

        foreach ($recursiveIterator as $file) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $recursiveIterator->getSubPathName();
            if ($file->isDir()) {
                $this->ensureDirectoryExists($targetPath);
            } else {
                $this->copyFile($file->getPathname(), $targetPath);
            }
        }
    }
}
