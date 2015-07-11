<?php
namespace TYPO3\CMS\Composer\Installer;

use Composer\Composer;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;

abstract class BaseInstaller implements InstallerInterface {
	protected $locations = array();
	protected $composer;

	/**
	 * Return the install path based on package type.
	 *
	 * @param  PackageInterface $package
	 * @param  string           $frameworkType
	 *
	 * @return string
	 */
	public function getInstallPath(PackageInterface $package) {
		$type = $package->getType();

		list($vendor, $name) = $this->getVendorAndPackageName($package);

		$availableVars = $this->inflectPackageVars(compact('name', 'vendor', 'type'));

		$extra = $package->getExtra();
		if (!empty($extra['installer-name'])) {
			$availableVars['name'] = $extra['installer-name'];
		}

		$templatedPath = '';

		if ($this->composer->getPackage()) {
			$extra = $this->composer->getPackage()->getExtra();
			if (!empty($extra['installer-paths'])) {
				$customPath = $this->mapCustomInstallPaths($extra['installer-paths'], $package->getPrettyName(), $type);
				if ($customPath !== FALSE) {
					$templatedPath = $this->templatePath($customPath, $availableVars);
				}
			}
		}

		if(empty($templatedPath)) {
			$locations = $this->getLocations();
			if (!isset($locations[$type])) {
				throw new \InvalidArgumentException(sprintf('Package type "%s" is not supported', $type));
			}

			$templatedPath = $this->templatePath($locations[$type], $availableVars);
		}

		$this->filesystem->ensureDirectoryExists($templatedPath);

		return realpath($templatedPath);
	}

	/**
	 * For an installer to override to modify the vars per installer.
	 *
	 * @param  array $vars
	 *
	 * @return array
	 */
	public function inflectPackageVars($vars) {
		return $vars;
	}

	/**
	 * Gets the installer's locations
	 *
	 * @return array
	 */
	public function getLocations() {
		return $this->locations;
	}

	/**
	 * @param PackageInterface $package
	 */
	protected function getVendorAndPackageName(PackageInterface $package) {
		$prettyName = $package->getPrettyName();
		if (strpos($prettyName, '/') !== FALSE) {
			list($vendor, $name) = explode('/', $prettyName);
		} else {
			$vendor = '';
			$name = $prettyName;
		}

		return array($vendor, $name);
	}

	/**
	 * Replace vars in a path
	 *
	 * @param  string $path
	 * @param  array  $vars
	 *
	 * @return string
	 */
	protected function templatePath($path, array $vars = array()) {
		if (strpos($path, '{') !== FALSE) {
			extract($vars);
			preg_match_all('@\{\$([A-Za-z0-9_]*)\}@i', $path, $matches);
			if (!empty($matches[1])) {
				foreach ($matches[1] as $var) {
					$path = str_replace('{$' . $var . '}', $$var, $path);
				}
			}
		}

		return $path;
	}

	/**
	 * Search through a passed paths array for a custom install path.
	 *
	 * @param  array  $paths
	 * @param  string $name
	 * @param  string $type
	 *
	 * @return string
	 */
	protected function mapCustomInstallPaths(array $paths, $name, $type) {
		foreach ($paths as $path => $names) {
			if (in_array($name, $names) || in_array('type:' . $type, $names)) {
				return $path;
			}
		}

		return FALSE;
	}

	/**
	 * Returns if this installer can install that package type
	 *
	 * @param string $packageType
	 *
	 * @return boolean
	 */
	public function supports($packageType) {
		return array_key_exists($packageType, $this->locations);
	}
}
