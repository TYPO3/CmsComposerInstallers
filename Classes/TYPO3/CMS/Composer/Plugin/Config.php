<?php
namespace TYPO3\CMS\Composer\Plugin;

/**
 * Enter descriptions here
 */
class Config {

	const RELATIVE_PATHS = 1;

	/**
	 * @var array
	 */
	public static $defaultConfig = array(
		'web-dir' => '.',
		'backend-dir' => '{$web-dir}/typo3',
		'config-dir' => '{$web-dir}/typo3conf',
		'temporary-dir' => '{$web-dir}/typo3temp',
		'cache-dir' => '{$temporary-dir}/Cache',
		'cms-package-dir' => 'typo3_src',
		'composer-mode' => true,
	);

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $baseDir;


	/**
	 * @param string $baseDir
	 */
	public function __construct($baseDir = null) {
		$this->baseDir = $baseDir;
		// load defaults
		$this->config = static::$defaultConfig;
	}

	/**
	 * Merges new config values with the existing ones (overriding)
	 *
	 * @param array $config
	 */
	public function merge(array $config) {
		// Override defaults with given config
		if (!empty($config['typo3/cms']) && is_array($config['typo3/cms'])) {
			foreach ($config['typo3/cms'] as $key => $val) {
				$this->config[$key] = $val;
			}
		}
	}

	/**
	 * Returns a setting
	 *
	 * @param  string $key
	 * @param  int $flags Options (see class constants)
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public function get($key, $flags = 0) {
		switch ($key) {
			case 'web-dir':
			case 'backend-dir':
			case 'configuration-dir':
			case 'temporary-dir':
			case 'cache-dir':
			case 'cms-package-dir':
				$val = rtrim($this->process($this->config[$key], $flags), '/\\');
				return ($flags & self::RELATIVE_PATHS == 1) ? $val : $this->realpath($val);
			default:
				if (!isset($this->config[$key])) {
					return NULL;
				}
				return $this->process($this->config[$key], $flags);
		}
	}

	/**
	 * @param int $flags Options (see class constants)
	 * @return array
	 */
	public function all($flags = 0) {
		$all = array();
		foreach (array_keys($this->config) as $key) {
			$all['config'][$key] = $this->get($key, $flags);
		}

		return $all;
	}

	/**
	 * @return array
	 */
	public function raw() {
		return array(
			'config' => $this->config,
		);
	}

	/**
	 * Checks whether a setting exists
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function has($key) {
		return array_key_exists($key, $this->config);
	}

	/**
	 * Replaces {$refs} inside a config string
	 *
	 * @param  string $value a config string that can contain {$refs-to-other-config}
	 * @param  int $flags Options (see class constants)
	 * @return string
	 */
	protected function process($value, $flags) {
		$config = $this;

		if (!is_string($value)) {
			return $value;
		}

		return preg_replace_callback('#\{\$(.+)\}#',
			function ($match) use ($config, $flags) {
				return $config->get($match[1], $flags);
			},
			$value);
	}

	/**
	 * Turns relative paths in absolute paths without realpath()
	 *
	 * Since the dirs might not exist yet we can not call realpath or it will fail.
	 *
	 * @param  string $path
	 * @return string
	 */
	protected function realpath($path) {
		if (substr($path, 0, 1) === '/' || substr($path, 1, 1) === ':') {
			return $path;
		}

		return $this->baseDir . '/' . $path;
	}

	/**
	 * @return string
	 */
	public function getBaseDir() {
		return $this->baseDir;
	}

	/**
	 * @param \Composer\Composer $composer
	 * @return Config
	 */
	static public function load(\Composer\Composer $composer) {
		static $config;
		if ($config === NULL) {
			$baseDir = static::extractBaseDir($composer->getConfig());
			$config = new static($baseDir);
			$rootPackageExtraConfig = $composer->getPackage()->getExtra();
			if (is_array($rootPackageExtraConfig)) {
				$config->merge($rootPackageExtraConfig);
			}
			$config->merge(
				array(
					'typo3/cms' => array(
						'vendor-dir' => $composer->getConfig()->get('vendor-dir')
					)
				)
			);
		}
		return $config;
	}

	/**
	 * @param \Composer\Config $config
	 * @return mixed
	 */
	static protected function extractBaseDir(\Composer\Config $config) {
		$reflectionClass = new \ReflectionClass($config);
		$reflectionProperty = $reflectionClass->getProperty('baseDir');
		$reflectionProperty->setAccessible(true);
		return $reflectionProperty->getValue($config);
	}

}
