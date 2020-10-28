<?php
declare(strict_types=1);

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

namespace TYPO3\CMS\ComposerTest\Installer;

use Composer\Plugin\PluginInterface;
use TYPO3\CMS\Composer\Installer\Plugin;

class PluginTest extends InstallerTestCase
{
    public function testPluginCompatibility()
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(PluginInterface::class, $plugin);
    }
}
