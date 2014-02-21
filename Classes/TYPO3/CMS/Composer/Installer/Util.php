<?php
namespace TYPO3\CMS\Composer\Installer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Christian Opitz <christian.opitz at netresearch.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Various utility functions
 * 
 * @author Christian Opitz <christian.opitz at netresearch.de>
 */
class Util {
	/**
	 * Already had the case, that PHPs realpath didn't canonicalize paths correctly
	 *
	 * @param string $path
	 * @return boolean
	 */
	public static function realpath($path) {
		$ds = DIRECTORY_SEPARATOR;

		// whether $path is unix or not
		$unipath = strlen($path) == 0 || $path{0} != '/';
		// attempts to detect if path is relative in which case, add cwd
		if (strpos($path, ':') === FALSE && $unipath) {
			$path = getcwd() . $ds . $path;
		}
		// resolve path parts (single dot, double dot and double delimiters)
		$parts = array_filter(explode($ds, str_replace(array('/', '\\'), $ds, $path)), 'strlen');
		$absolutes = array();
		foreach ($parts as $part) {
			if ('.' == $part) {
				continue;
			}
			if ('..' == $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}
		$path = implode($ds, $absolutes);
		// put initial separator that could have been lost
		$path = !$unipath ? '/' . $path : $path;

		if (!file_exists($path)) {
			return FALSE;
		}
		// resolve any symlinks
		while (is_link($path)) {
			$target = readlink($path);
			$path = $target[0] == $ds ? $target : dirname($path) . $ds . $target;
		}

		return $path;
	}
}