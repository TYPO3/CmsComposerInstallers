<?php
namespace TYPO3\CMS\Composer\Plugin\Core\IncludeFile;

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

interface TokenInterface
{
    /**
     * The name of the token that shall be replaced
     *
     * @return string
     */
    public function getName();

    /**
     * The content the token should be replaced with
     *
     * @return string
     */
    public function getContent();
}
